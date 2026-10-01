<?php

namespace studioespresso\seofields\controllers;

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Writer\XLSX\Writer;
use studioespresso\seofields\controllers\concerns\ListsRecords;
use studioespresso\seofields\controllers\concerns\SeoCpScreen;
use studioespresso\seofields\records\RedirectRecord;
use studioespresso\seofields\SeoFields;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_redirect;
use function CraftCms\Cms\t;

class RedirectsController
{
    use ListsRecords;
    use RespondsWithFlash;
    use SeoCpScreen;

    private const IMPORT_FILE = 'seofields_redirects_import.csv';

    public function index(Request $request): CpScreenResponse
    {
        $site = Sites::isMultiSite() && $request->query('site') ? $this->site($request) : null;
        $this->registerListStyles();

        $query = RedirectRecord::query();
        if ($site) {
            $query->where(fn ($query) => $query->where('siteId', $site->id)->orWhereNull('siteId'));
        }

        return $this->screen(t('Redirects', category: 'seo-fields'), $site)
            ->toolbarTemplate('seo-fields/_redirect/_buttons', ['site' => $site?->handle])
            ->contentTemplate('seo-fields/_redirect/_content', [
                ...$this->listVariables($this->paginate($query, $request, ['pattern', 'redirect'], ['counter', 'dateLastHit', 'pattern', 'matchType', 'method'])
                    ->through(fn (RedirectRecord $row) => $row->only(['id', 'pattern', 'redirect', 'siteId', 'counter', 'matchType', 'dateLastHit', 'method'])), $request),
            ])
            ->inertiaPage('cp/Screen');
    }

    public function edit(Request $request, ?int $id = null): CpScreenResponse
    {
        $redirect = $id ? RedirectRecord::query()->findOrFail($id) : new RedirectRecord([
            'pattern' => $request->query('pattern'),
            'siteId' => $request->integer('site') ?: null,
            'sourceMatch' => 'path',
            'matchType' => 'exact',
            'method' => 301,
        ]);

        $title = $id ? t('Redirect', category: 'seo-fields') : t('New redirect', category: 'seo-fields');

        return $this->formScreen($title, null, $this->redirectForm(), [
            'siteId' => (string) ($redirect->siteId ?? 0),
            'pattern' => $redirect->pattern,
            'sourceMatch' => $redirect->sourceMatch ?? 'path',
            'redirect' => $redirect->redirect,
            'matchType' => $redirect->matchType ?? 'exact',
            'method' => (string) $redirect->method,
            'record' => $request->query('record'),
        ])->addCrumb(t('Redirects', category: 'seo-fields'), 'seo-fields/redirects');
    }

    public function store(Request $request, ?int $id = null): Response
    {
        $values = $request->validate([
            'siteId' => ['nullable', 'integer'],
            'pattern' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->input('sourceMatch') !== 'url' && preg_match('/^(https?:\/\/|\/\/)/i', trim($value))) {
                        $fail(t('Remove "http(s)://" and the domain, the old pattern should only contain the slug', category: 'seo-fields'));
                    }
                },
            ],
            'sourceMatch' => ['required', 'in:path,pathWithoutParams,url'],
            'redirect' => ['required', 'string', 'max:255'],
            'matchType' => ['required', 'in:exact,regexMatch'],
            'method' => ['required', 'in:301,302'],
        ]);

        $redirect = $id ? RedirectRecord::query()->findOrFail($id) : new RedirectRecord;
        $redirect->fill($values);
        SeoFields::getInstance()->redirectService->saveRedirect($redirect);

        if ($record = $request->integer('record')) {
            SeoFields::getInstance()->notFoundService->markAsHandled($record);
        }

        return $this->asSuccess(t('Redirect saved', category: 'seo-fields'), redirect: Url::cpUrl('seo-fields/redirects'));
    }

    public function delete(int $id): Response
    {
        RedirectRecord::query()->whereKey($id)->delete();

        return $this->asSuccess(t('Redirect removed', category: 'seo-fields'), redirect: url()->previous());
    }

    public function clearAll(): Response
    {
        RedirectRecord::query()->delete();

        return $this->asSuccess(t('All redirects removed', category: 'seo-fields'));
    }

    public function export(Request $request): Response
    {
        $path = Path::temp('redirects-'.now()->format('Y-m-d-His').'.xlsx');

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['Old url', 'Redirected to', 'Type', 'Site Name', 'Last hit on', 'Total hits']));

        $query = RedirectRecord::query();
        if ($site = $request->query('site')) {
            $query->where('siteId', Sites::getSiteByHandle($site)?->id);
        }

        foreach ($query->cursor() as $redirect) {
            $writer->addRow(Row::fromValues([
                $redirect->pattern,
                $redirect->redirect,
                $redirect->method,
                $redirect->siteId ? Sites::getSiteById($redirect->siteId)?->getName() : 'All Sites',
                $redirect->dateLastHit?->format('Y-m-d H:i') ?? '',
                $redirect->counter,
            ]));
        }
        $writer->close();

        return response()->download($path)->deleteFileAfterSend();
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);
        $request->file('file')->move(Path::temp(), self::IMPORT_FILE);

        return cp_redirect('seo-fields/redirects/import');
    }

    public function import(): CpScreenResponse|RedirectResponse
    {
        if (! file_exists($this->importFile())) {
            return cp_redirect('seo-fields/redirects');
        }

        $columns = [['label' => '---', 'value' => '']];
        foreach ($this->rows($this->importFile(), headersOnly: true) as $index => $header) {
            $columns[] = ['label' => (string) $header, 'value' => (string) $index];
        }

        return $this->formScreen(t('Import redirects', category: 'seo-fields'), null, Form::make([
            Field::make(t('Pattern / URL', category: 'seo-fields'), Choice::make('patternCol')->options($columns))->required(),
            Field::make(t('Redirect to', category: 'seo-fields'), Choice::make('redirectCol')->options($columns))->required(),
            Field::make(t('Site', category: 'seo-fields'), Choice::make('siteId')->options($this->siteOptions())),
            Field::make(t('Method', category: 'seo-fields'), Choice::make('method')->options([
                ['label' => t('301 (Permanent redirect)', category: 'seo-fields'), 'value' => '301'],
                ['label' => t('302 (Temporary redirect)', category: 'seo-fields'), 'value' => '302'],
            ]))->required(),
        ]), ['siteId' => '0', 'method' => '301'])->addCrumb(t('Redirects', category: 'seo-fields'), 'seo-fields/redirects');
    }

    public function runImport(Request $request): Response
    {
        $settings = $request->validate([
            'patternCol' => ['required', 'integer'],
            'redirectCol' => ['required', 'integer'],
            'siteId' => ['nullable', 'integer'],
            'method' => ['required', 'in:301,302'],
        ]);

        $results = SeoFields::getInstance()->redirectService->import($this->rows($this->importFile()), $settings);
        @unlink($this->importFile());
        session()->flash('seofields.import', $results);

        return $this->asSuccess(
            t('{count} redirects imported', ['count' => count($results['imported'])], 'seo-fields'),
            redirect: Url::cpUrl('seo-fields/redirects/import/results'),
        );
    }

    public function importResults(): CpScreenResponse|RedirectResponse
    {
        if (! $results = session('seofields.import')) {
            return cp_redirect('seo-fields/redirects');
        }

        return $this->screen(t('Import results', category: 'seo-fields'))
            ->addCrumb(t('Redirects', category: 'seo-fields'), 'seo-fields/redirects')
            ->contentTemplate('seo-fields/_redirect/_import_results', $results)
            ->inertiaPage('cp/Screen');
    }

    private function redirectForm(): Form
    {
        return Form::make([
            HiddenField::make('record'),
            ...(Sites::isMultiSite() ? [
                Field::make(t('Enable for site', category: 'seo-fields'), Choice::make('siteId')->options($this->siteOptions()))
                    ->instructions(t('For which site should this redirect be active?', category: 'seo-fields')),
            ] : [HiddenField::make('siteId')]),
            Field::make(t('Old pattern or URL to redirect', category: 'seo-fields'), Text::make('pattern')->maxLength(255))
                ->required()
                ->instructions(t('Enter a URL or pattern that should be matched. Depending on the options below, this matches the path (`/news`) or the full URL (`https://www.example.com/news`).', category: 'seo-fields')),
            Field::make(t('Which part of the old URL should be matched?', category: 'seo-fields'), Choice::make('sourceMatch')->options([
                ['label' => t('Path only', category: 'seo-fields'), 'value' => 'path'],
                ['label' => t('Path only (ignore parameters)', category: 'seo-fields'), 'value' => 'pathWithoutParams'],
                ['label' => t('Full URL', category: 'seo-fields'), 'value' => 'url'],
            ])),
            Field::make(t('URL to redirect to', category: 'seo-fields'), Text::make('redirect')->maxLength(255))->required(),
            Field::make(t('Match type', category: 'seo-fields'), Choice::make('matchType')->options([
                ['label' => t('Exact match', category: 'seo-fields'), 'value' => 'exact'],
                ['label' => t('Regex match', category: 'seo-fields'), 'value' => 'regexMatch'],
            ])),
            Field::make(t('Method', category: 'seo-fields'), Choice::make('method')->options([
                ['label' => '301', 'value' => '301'],
                ['label' => '302', 'value' => '302'],
            ]))->instructions(t('Select whether the redirect should be permanent or temporary.', category: 'seo-fields')),
        ]);
    }

    /** @return list<array{label: string, value: string, group?: string}> */
    private function siteOptions(): array
    {
        $options = [['label' => t('All Sites', category: 'seo-fields'), 'value' => '0']];
        foreach (Sites::getEditableSites() as $site) {
            $options[] = ['label' => $site->getName(), 'value' => (string) $site->id, 'group' => $site->getGroup()->getName()];
        }

        return $options;
    }

    private function importFile(): string
    {
        return Path::temp(self::IMPORT_FILE);
    }

    /** @return array<int, array<int, mixed>>|array<int, mixed> The rows after the header, or the header itself */
    private function rows(string $file, bool $headersOnly = false): array
    {
        $reader = new CsvReader;
        $reader->open($file);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if ($headersOnly) {
                    $reader->close();

                    return $row->toArray();
                }
                $rows[] = $row->toArray();
            }
        }
        $reader->close();

        return array_slice($rows, 1);
    }
}
