<?php

namespace studioespresso\seofields\controllers;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Form\Controls\AssetSelect;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use studioespresso\seofields\controllers\concerns\SeoCpScreen;
use studioespresso\seofields\SeoFields;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

class DefaultsController
{
    use RespondsWithFlash;
    use SeoCpScreen;

    /** Sends the plugin's nav item to the first section the user may see */
    public function index(Request $request): RedirectResponse
    {
        $sections = [
            'defaults' => 'seo-fields:default',
            'not-found' => 'seo-fields:notfound',
            'redirects' => 'seo-fields:redirects',
            'schema' => 'seo-fields:schema',
            'robots' => 'seo-fields:robots',
            'sitemap' => 'seo-fields:sitemap',
        ];

        foreach ($sections as $section => $permission) {
            if (Gate::check($permission)) {
                return redirect(cp_url("seo-fields/$section", $request->query()));
            }
        }

        abort(403);
    }

    public function edit(Request $request): CpScreenResponse
    {
        $site = $this->site($request);
        $data = SeoFields::getInstance()->defaultsService->getDataBySite($site);

        return $this->formScreen(t('Meta', category: 'seo-fields'), $site, Form::make([
            Field::make(t('Default site title', category: 'seo-fields'), Text::make('defaultSiteTitle')),
            Field::make(t('Default meta description', category: 'seo-fields'), Textarea::make('defaultMetaDescription')->maxLength(300)),
            Field::make(t('Title seperator', category: 'seo-fields'), Text::make('titleSeperator')->size(3))
                ->instructions(t('A character used to seperate your entry title from the site name', category: 'seo-fields')),
            Field::make(t('Default meta image', category: 'seo-fields'), AssetSelect::make('defaultImage')
                ->elementType(Asset::class)
                ->criteria(['kind' => ['image']])
                ->single()
                ->viewMode(AssetSelect::VIEW_MODE_CARDS)
                ->selectionLabel(t('Select an image', category: 'seo-fields'))),
        ]), [
            ...$data->getMeta(),
            'defaultImage' => $data->defaultImage ?? [],
        ]);
    }

    public function store(Request $request): Response
    {
        $values = $request->validate([
            'defaultSiteTitle' => ['nullable', 'string'],
            'defaultMetaDescription' => ['nullable', 'string', 'max:300'],
            'titleSeperator' => ['nullable', 'string', 'max:10'],
            'defaultImage' => ['nullable', 'array'],
        ]);

        $site = $this->site($request);
        $service = SeoFields::getInstance()->defaultsService;
        $defaults = $service->getDataBySite($site);
        $defaults->setAttributes($values);
        $service->saveDefaults($defaults, $site->id);

        return $this->asSuccess(t('Defaults saved.', category: 'seo-fields'));
    }
}
