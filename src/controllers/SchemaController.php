<?php

namespace studioespresso\seofields\controllers;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Form\Controls\AssetSelect;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Http\Request;
use studioespresso\seofields\controllers\concerns\SeoCpScreen;
use studioespresso\seofields\SeoFields;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class SchemaController
{
    use RespondsWithFlash;
    use SeoCpScreen;

    public function edit(Request $request): CpScreenResponse
    {
        $site = $this->site($request);
        $data = SeoFields::getInstance()->defaultsService->getDataBySite($site);
        $schemaService = SeoFields::getInstance()->schemaService;

        $sectionFields = Sections::getAllSections()->map(fn ($section) => Field::make(
            t($section->name, category: 'site'),
            Choice::make("sections.$section->id")->options($this->options($schemaService->getDefaultOptions())),
        ))->values()->all();

        return $this->formScreen(t('Schema.org', category: 'seo-fields'), $site, Form::make([
            Group::make('schema-organization', [
                Field::make(t('Name', category: 'seo-fields'), Text::make('organizationName'))
                    ->instructions(t('Leave blank to use the default site title', category: 'seo-fields'))
                    ->width(50),
                Field::make(t('Site entity type', category: 'seo-fields'), Choice::make('siteEntity')->options($this->options($schemaService->getSiteEntityOptions())))
                    ->instructions(t("The Schema.org type that represents your site's owner or subject", category: 'seo-fields'))
                    ->width(50),
                Field::make(t('Organization logo', category: 'seo-fields'), AssetSelect::make('organizationLogo')
                    ->elementType(Asset::class)
                    ->criteria(['kind' => ['image']])
                    ->single()
                    ->selectionLabel(t('Select a logo', category: 'seo-fields'))),
                Field::make(t('Social profiles', category: 'seo-fields'), Table::make('sameAs')
                    ->columns(['url' => ['heading' => t('URL', category: 'seo-fields'), 'type' => 'url']])
                    ->allowAdd()
                    ->allowDelete()
                    ->allowReorder())
                    ->instructions(t("Add your organization's social profile URLs (Facebook, X, LinkedIn, Instagram, YouTube, etc.)", category: 'seo-fields')),
            ])->label(t('Organization & Website', category: 'seo-fields')),
            Group::make('schema-sections', $sectionFields)
                ->label(t('Section types', category: 'seo-fields'))
                ->instructions(t('The Schema.org type used for the entries of each section.', category: 'seo-fields')),
        ]), [
            'organizationName' => $data->organizationName,
            'siteEntity' => $data->siteEntity,
            'organizationLogo' => $data->organizationLogo ?? [],
            'sameAs' => array_map(fn ($url) => ['url' => $url], $data->sameAs ?? []),
            'sections' => $data->getSchema()['sections'] ?? [],
        ]);
    }

    public function store(Request $request): Response
    {
        $values = $request->validate([
            'organizationName' => ['nullable', 'string'],
            'siteEntity' => ['nullable', 'string'],
            'organizationLogo' => ['nullable', 'array'],
            'sameAs' => ['nullable', 'array'],
            'sameAs.*.url' => ['nullable', 'url'],
            'sections' => ['nullable', 'array'],
        ]);

        $site = $this->site($request);
        $service = SeoFields::getInstance()->defaultsService;
        $defaults = $service->getDataBySite($site);
        $defaults->setAttributes([
            'organizationName' => $values['organizationName'] ?? null,
            'siteEntity' => $values['siteEntity'] ?? null,
            'organizationLogo' => $values['organizationLogo'] ?? null,
            'sameAs' => array_values(array_filter(array_column($values['sameAs'] ?? [], 'url'))),
            'schema' => ['sections' => $values['sections'] ?? []],
        ]);
        $service->saveDefaults($defaults, $site->id);

        return $this->asSuccess(t('Schema settings saved.', category: 'seo-fields'));
    }

    /**
     * @param  array<class-string, string>  $options
     * @return list<array{label: string, value: string}>
     */
    private function options(array $options): array
    {
        return array_map(fn ($class, $label) => ['label' => $label, 'value' => $class], array_keys($options), $options);
    }
}
