<?php

namespace studioespresso\seofields\controllers;

use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\MarkdownContent;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Http\Request;
use studioespresso\seofields\controllers\concerns\SeoCpScreen;
use studioespresso\seofields\SeoFields;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\renderString;
use function CraftCms\Cms\t;

class RobotsController
{
    use RespondsWithFlash;
    use SeoCpScreen;

    public function edit(Request $request): CpScreenResponse
    {
        $perSite = SeoFields::getInstance()->getSettings()->robotsPerSite;
        $site = $perSite ? $this->site($request) : Sites::getPrimarySite();
        $data = SeoFields::getInstance()->defaultsService->getDataBySite($site);

        return $this->formScreen(t('Robots.txt', category: 'seo-fields'), $perSite ? $site : null, Form::make([
            MarkdownContent::make('robots-intro', t("A robots.txt file tells search engine crawlers which pages or files the crawler can or can't request from your site. This is used mainly to avoid overloading your site with requests; it is not a mechanism for keeping a web page out of Google.", category: 'seo-fields')),
            Field::make(t('Enable robots.txt', category: 'seo-fields'), Lightswitch::make('enableRobots'))
                ->instructions(t('Let the plugin handle your robots.txt', category: 'seo-fields')),
            Field::make(t('Robots.txt content', category: 'seo-fields'), Textarea::make('robots')->rows(25)->monospace())
                ->instructions(t('Rendered as a Twig template.', category: 'seo-fields')),
        ]), [
            'enableRobots' => $data->enableRobots ?? true,
            'robots' => $data->robots,
        ]);
    }

    public function store(Request $request): Response
    {
        $values = $request->validate([
            'enableRobots' => ['boolean'],
            'robots' => ['nullable', 'string'],
        ]);

        $site = SeoFields::getInstance()->getSettings()->robotsPerSite ? $this->site($request) : Sites::getPrimarySite();
        $service = SeoFields::getInstance()->defaultsService;
        $defaults = $service->getDataBySite($site);
        $defaults->setAttributes($values);
        $service->saveDefaults($defaults, $site->id);

        return $this->asSuccess(t('Robots.txt saved.', category: 'seo-fields'));
    }

    /** Serves `/robots.txt` */
    public function render(): Response
    {
        $robots = SeoFields::getInstance()->defaultsService->getRobotsForSite(Sites::getCurrentSite());
        abort_unless($robots, 404);

        return response(renderString((string) $robots->robots), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
