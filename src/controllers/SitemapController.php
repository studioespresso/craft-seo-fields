<?php

namespace studioespresso\seofields\controllers;

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Form\Nodes\MarkdownContent;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;
use studioespresso\seofields\controllers\concerns\SeoCpScreen;
use studioespresso\seofields\SeoFields;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class SitemapController
{
    use RespondsWithFlash;
    use SeoCpScreen;

    private const CHANGEFREQ = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

    public function edit(Request $request): CpScreenResponse
    {
        $site = $this->site($request);
        $settings = SeoFields::getInstance()->defaultsService->getDataBySite($site)->getSitemap() ?? [];

        $changefreq = array_map(fn ($value) => ['label' => t($value, category: 'seo-fields'), 'value' => $value], self::CHANGEFREQ);
        $priority = array_map(fn ($value) => ['label' => match ($value) {
            '1.0' => t('1.0 (High)', category: 'seo-fields'),
            '0.5' => t('0.5 (Default)', category: 'seo-fields'),
            '0.0' => t('0.0 (Low)', category: 'seo-fields'),
            default => $value,
        }, 'value' => $value], array_map(fn ($i) => number_format($i / 10, 1), range(10, 0)));

        $nodes = [
            MarkdownContent::make('sitemap-intro', t('A sitemap tells search engines which pages on your site are important and when they were last updated. [View sitemap.xml]({url})', ['url' => Url::siteUrl('sitemap.xml', siteId: $site->id)], 'seo-fields')),
        ];

        $values = [];
        foreach ($this->sectionsForSite($site->id) as $section) {
            $sectionSettings = $settings['entry'][$section->id] ?? [];
            $values['entry'][$section->id] = [
                'enabled' => ! empty($sectionSettings['enabled']),
                'changefreq' => $sectionSettings['changefreq'] ?? 'weekly',
                'priority' => $sectionSettings['priority'] ?? '0.5',
            ];

            $nodes[] = Group::make("section-$section->id", [
                Field::make(t('Enabled?', category: 'seo-fields'), Lightswitch::make("entry.$section->id.enabled")),
                Field::make(t('Update frequency', category: 'seo-fields'), Choice::make("entry.$section->id.changefreq")->options($changefreq))->width(50),
                Field::make(t('Priority', category: 'seo-fields'), Choice::make("entry.$section->id.priority")->options($priority))->width(50)
                    ->instructions(t('The priority of this URL relative to other URLs on your site.', category: 'seo-fields')),
            ])->label(t($section->name, category: 'site'));
        }

        return $this->formScreen(t('Sitemap.xml', category: 'seo-fields'), $site, Form::make($nodes), $values);
    }

    public function store(Request $request): Response
    {
        $values = $request->validate([
            'entry' => ['nullable', 'array'],
            'entry.*.enabled' => ['boolean'],
            'entry.*.changefreq' => ['in:'.implode(',', self::CHANGEFREQ)],
            'entry.*.priority' => ['numeric', 'between:0,1'],
        ]);

        $site = $this->site($request);
        $service = SeoFields::getInstance()->defaultsService;
        $defaults = $service->getDataBySite($site);
        $defaults->setAttributes(['sitemap' => ['entry' => $values['entry'] ?? []]]);
        $service->saveDefaults($defaults, $site->id);
        SeoFields::getInstance()->sitemapService->clearCaches();

        return $this->asSuccess(t('Sitemap settings saved.', category: 'seo-fields'));
    }

    /** Serves `/sitemap.xml` */
    public function index(): Response
    {
        $sections = SeoFields::getInstance()->sitemapService->getSectionsToRender(Sites::getCurrentSite());
        abort_unless($sections, 404);

        return $this->xml(SeoFields::getInstance()->sitemapService->getSitemapIndex($sections));
    }

    /** Serves `/sitemap_{siteId}_entry_{sectionId}_{handle}.xml` */
    public function detail(int $siteId, int $sectionId): Response
    {
        $xml = SeoFields::getInstance()->sitemapService->getSitemapData($siteId, $sectionId);
        abort_unless($xml, 404);

        return $this->xml($xml);
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'text/xml; charset=utf-8']);
    }

    /** @return iterable<Section> */
    private function sectionsForSite(int $siteId): iterable
    {
        return Sections::getAllSections()->filter(fn ($section) => isset($section->getSiteSettings()[$siteId]));
    }
}
