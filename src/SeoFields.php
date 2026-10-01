<?php

/**
 * SEO Fields plugin for Craft CMS
 *
 * Fields for your SEO & OG data
 *
 * @link      https://studioespresso.co
 *
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\seofields;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Events\ElementSlugAndUriUpdated;
use CraftCms\Cms\Element\Events\ElementSlugAndUriUpdating;
use CraftCms\Cms\Entry\Events\EntryTypeDeleted;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\GarbageCollection\Events\RunningGarbageCollection;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Section\Events\SectionDeleted;
use CraftCms\Cms\Site\Events\SiteSaved;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\TemplateHooks;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\User\Data\Permission;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use studioespresso\seofields\behaviors\ElementSeoMacros;
use studioespresso\seofields\console\MigrateFieldsCommand;
use studioespresso\seofields\extensions\SeoFieldsExtension;
use studioespresso\seofields\fields\SeoField;
use studioespresso\seofields\http\HandleNotFound;
use studioespresso\seofields\models\Settings;
use studioespresso\seofields\services\DefaultsService;
use studioespresso\seofields\services\NotFoundService;
use studioespresso\seofields\services\RedirectService;
use studioespresso\seofields\services\RenderService;
use studioespresso\seofields\services\SchemaService;
use studioespresso\seofields\services\SitemapService;

use function CraftCms\Cms\t;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 *
 * @method Settings getSettings()
 */
class SeoFields extends Plugin
{
    public string $schemaVersion = '4.0.0';

    protected array $fieldTypes = [SeoField::class];

    protected array $commands = [MigrateFieldsCommand::class];

    protected array $publishables = [
        __DIR__.'/../resources/dist' => 'dist',
    ];

    public DefaultsService $defaultsService { get => $this->defaultsService ??= new DefaultsService; }

    public SitemapService $sitemapService { get => $this->sitemapService ??= new SitemapService; }

    public RenderService $renderService { get => $this->renderService ??= new RenderService; }

    public RedirectService $redirectService { get => $this->redirectService ??= new RedirectService; }

    public NotFoundService $notFoundService { get => $this->notFoundService ??= new NotFoundService; }

    public SchemaService $schemaService { get => $this->schemaService ??= new SchemaService; }

    /** Sections in the CP subnav: handle => [label, permission] */
    private const SECTIONS = [
        'defaults' => ['Meta', 'seo-fields:default'],
        'not-found' => ["404's", 'seo-fields:notfound'],
        'redirects' => ['Redirects', 'seo-fields:redirects'],
        'schema' => ['Schema.org', 'seo-fields:schema'],
        'robots' => ['Robots.txt', 'seo-fields:robots'],
        'sitemap' => ['Sitemap.xml', 'seo-fields:sitemap'],
    ];

    public function boot(): void
    {
        ElementSeoMacros::register();

        // ponytail: registered once per boot; per-request registration (like core's CP hooks) if this runs under Octane
        TemplateHooks::register('seo-fields', fn (array &$context) => $this->renderService->renderMeta($context, $this->getSettings()->fieldHandle));
        Twig::registerExtension(new SeoFieldsExtension);

        $this->app['router']->prependMiddlewareToGroup('craft.web', HandleNotFound::class);

        $this->registerListeners();
    }

    protected static function createSettings(): ?PluginSettings
    {
        return new Settings;
    }

    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        return Form::make([
            Field::make(t('Plugin sidebar label', category: 'seo-fields'), Text::make('pluginLabel')->size(25)),
            Field::make(t('Title seperator', category: 'seo-fields'), Text::make('titleSeperator')->size(3))
                ->instructions(t('A character used to seperate your `<title>` from the site name', category: 'seo-fields')),
            Field::make(t("Should redirects be created automatically when uri's change?", category: 'seo-fields'), Lightswitch::make('createRedirectForUriChange')),
        ]);
    }

    public function getCpNavItem(): NavItem|array|null
    {
        $subnav = [];
        foreach (self::SECTIONS as $handle => [$label, $permission]) {
            if (Gate::check($permission)) {
                $subnav[] = new NavItem()->label(t($label, category: 'seo-fields'))->href("seo-fields/$handle");
            }
        }

        return parent::getCpNavItem()
            ->label($this->getSettings()->pluginLabel)
            ->icon(null)
            ->iconSvg(file_get_contents($this->getBasePath().'/icon-mask.svg'))
            ->subnav($subnav);
    }

    protected function getPermissions(): array
    {
        return array_map(
            fn (array $section) => new Permission($section[1], t($section[0], category: 'seo-fields')),
            array_values(self::SECTIONS),
        );
    }

    protected function getCacheOptions(): array
    {
        return [
            'seofields_sitemaps' => [
                'label' => t('Sitemap caches (SEO Fields)', category: 'seo-fields'),
                'action' => fn () => $this->sitemapService->clearCaches(),
            ],
        ];
    }

    private function registerListeners(): void
    {
        Event::listen(SiteSaved::class, function (SiteSaved $event) {
            if ($event->isNew) {
                $this->defaultsService->copyDefaultsForSite($event->site, $event->oldPrimarySiteId ?? Sites::getPrimarySite()->id);
            }
        });

        Event::listen(ElementSaved::class, fn (ElementSaved $event) => $this->sitemapService->clearCacheForElement($event->element));
        Event::listen(ElementDeleted::class, fn (ElementDeleted $event) => $this->sitemapService->clearCacheForElement($event->element));
        Event::listen([SectionDeleted::class, EntryTypeDeleted::class], fn () => $this->sitemapService->clearCaches());

        Event::listen(RunningGarbageCollection::class, fn () => $this->notFoundService->cleanup());

        if ($this->getSettings()->createRedirectForUriChange) {
            Event::listen([ElementSaving::class, ElementSlugAndUriUpdating::class], function (ElementSaving|ElementSlugAndUriUpdating $event) {
                if ($this->shouldTrackUris($event->element)) {
                    $this->redirectService->trackElementUris($event->element);
                }
            });
            Event::listen([ElementSaved::class, ElementSlugAndUriUpdated::class], function (ElementSaved|ElementSlugAndUriUpdated $event) {
                if ($this->shouldTrackUris($event->element)) {
                    $this->redirectService->handleUriChange($event->element);
                }
            });
        }
    }

    private function shouldTrackUris($element): bool
    {
        return ! $element->propagating
            && ! ElementHelper::isDraftOrRevision($element)
            && ! ElementHelper::isTempSlug($element->slug);
    }
}
