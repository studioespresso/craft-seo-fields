<?php
/**
 * SEO Fields plugin for Craft CMS 3.x
 *
 * Fields for your SEO & OG data
 *
 * @link      https://studioespresso.co
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\seofields;

use Craft;
use craft\base\Plugin;
use craft\db\Query;
use craft\events\ElementEvent;
use craft\events\EntryTypeEvent;
use craft\events\ExceptionEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\SectionEvent;
use craft\events\SiteEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\Sections;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\utilities\ClearCaches;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use studioespresso\seofields\events\RegisterSeoElementEvent;
use studioespresso\seofields\extensions\SeoFieldsExtension;
use studioespresso\seofields\fields\SeoField;
use studioespresso\seofields\models\Settings;
use studioespresso\seofields\records\NotFoundRecord;
use studioespresso\seofields\services\DefaultsService;
use studioespresso\seofields\services\NotFoundService;
use studioespresso\seofields\services\RedirectService;
use studioespresso\seofields\services\RenderService;
use studioespresso\seofields\services\SitemapService;
use studioespresso\seofields\variables\SeoFieldsVariable;
use yii\base\Event;
use yii\base\Exception;
use yii\console\Application as ConsoleApplication;
use yii\web\HttpException;

/**
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 *
 *
 * @property  SitemapService $sitemapSerivce
 * @property  DefaultsService $defaultsService
 * @property RenderService $renderService
 * @property RedirectService $redirectService
 * @property NotFoundService $notFoundService
 * @method    Settings getSettings()
 */
class SeoFields extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * SeoFields::$plugin
     *
     * @var SeoFields
     */
    public static $plugin;

    // Public Properties
    // =========================================================================
    public string $schemaVersion = "2.0.0";


    public const EVENT_SEOFIELDS_REGISTER_ELEMENT = "registerSeoElement";

    // Public Methods
    // =========================================================================
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            "defaultsService" => DefaultsService::class,
            "sitemapSerivce" => SitemapService::class,
            "renderService" => RenderService::class,
            "redirectService" => RedirectService::class,
            "notFoundService" => NotFoundService::class,
        ]);

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'studioespresso\seofields\console\controllers';
        }

        Craft::$app->view->hook('seo-fields', function (array &$context) {
            return $this->renderService->renderMeta($context);
        });

        $this->_registerField();
        $this->_registerCpRoutes();
        $this->_registerFrontendRoutes();
        $this->_registerPermissions();
        $this->_registerTwigExtension();
        $this->_registerCpListeners();
        $this->_registerSiteListeners();
        $this->_registerCacheOptions();
        $this->_registerCustomElements();
    }

    public function getCpNavItem(): ?array
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        $navItem['label'] = $this->getSettings()->pluginLabel;
        $currentUser = Craft::$app->getUser()->getIdentity();
        // Only show sub-navs the user has permission to view
        if ($currentUser->can('seo-fields:defaults')) {
            $subNavs['defaults'] = [
                'label' => 'Meta',
                'url' => 'seo-fields/defaults',
            ];
        }
        if ($currentUser->can('seo-fields:notfound')) {
            $subNavs['notfound'] = [
                'label' => "404's",
                'url' => 'seo-fields/not-found',
            ];
        }
        if ($currentUser->can('seo-fields:redirects')) {
            $subNavs['redirects'] = [
                'label' => "Redirects",
                'url' => 'seo-fields/redirects',
            ];
        }
        if ($currentUser->can('seo-fields:robots')) {
            $subNavs['robots'] = [
                'label' => 'Robots.txt',
                'url' => 'seo-fields/robots',
            ];
        }
        if ($currentUser->can('seo-fields:sitemap')) {
            $subNavs['sitemap'] = [
                'label' => 'Sitemap.xml',
                'url' => 'seo-fields/sitemap',
            ];
        }
        $navItem = array_merge($navItem, [
            'subnav' => $subNavs,
        ]);
        return $navItem;
    }

    // Protected Methods
    // =========================================================================
    // Protected Methods
    // =========================================================================
    protected function createSettingsModel(): ?craft\base\Model
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'seo-fields/_settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    protected function afterInstall(): void
    {
        if (!Craft::$app->getRequest()->isConsoleRequest) {
            parent::afterInstall();
            Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('seo-fields', ['showIntroduction' => true]))->send();
        }
    }

    private function _registerField()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SeoField::class;
            }
        );
    }

    private function _registerPermissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {

                // Register our custom permissions
                $permissions = [
                    "heading" => Craft::t('seo-fields', 'SEO Fields'),
                    "permissions" => [
                        'seo-fields:default' => [
                            'label' => Craft::t('seo-fields', 'Meta'),
                        ],
                        'seo-fields:notfound' => [
                            'label' => Craft::t('seo-fields', "404's"),
                        ],
                        'seo-fields:redirects' => [
                            'label' => Craft::t('seo-fields', "redirects"),
                        ],
                        'seo-fields:robots' => [
                            'label' => Craft::t('seo-fields', 'Robots'),
                        ],
                        'seo-fields:sitemap' => [
                            'label' => Craft::t('seo-fields', 'Sitemap'),
                        ],
                    ]
                ];
                $event->permissions[Craft::t('seo-fields', 'SEO Fields')] = $permissions;
            }
        );
    }

    private function _registerTwigExtension()
    {
        $request = Craft::$app->getRequest();
        if (!$request->isConsoleRequest) {
            Craft::$app->getView()->registerTwigExtension(new SeoFieldsExtension());
        }

    }

    private function _registerFrontendRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $robots = SeoFields::$plugin->defaultsService->getRobotsForSite(Craft::$app->getSites()->getCurrentSite());
                if ($robots) {
                    $event->rules = array_merge($event->rules, [
                        'robots.txt' => 'seo-fields/robots/render',
                    ]);
                }
                if (SeoFields::$plugin->getSettings()->sitemapPerSite) {
                    $shouldRender = SeoFields::getInstance()->sitemapSerivce->shouldRenderBySiteId(Craft::$app->getSites()->getCurrentSite());
                } else {
                    $shouldRender = SeoFields::getInstance()->sitemapSerivce->shouldRenderBySiteId(Craft::$app->getSites()->getPrimarySite());
                }
                if ($shouldRender) {
                    $event->rules = array_merge($event->rules, [
                        'sitemap.xml' => 'seo-fields/sitemap/render',
                        'sitemap_<siteId:\d+>_<type:(entry|product|category)>_<sectionId:\d+>_<handle:.*>.xml' => 'seo-fields/sitemap/detail'
                    ]);
                }
            }
        );
    }

    private function _registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                // Register our Control Panel routes
                $event->rules = array_merge($event->rules, [
                    'seo-fields' => 'seo-fields/defaults/index',
                    'seo-fields/<controller:(not-found)>/<siteHandle:{handle}>' => 'seo-fields/<controller>/index',
                    'seo-fields/<controller:(defaults|robots|sitemap|not-found|redirects)>' => 'seo-fields/<controller>/index',
                    'seo-fields/<controller:(redirects)>/<id:\d+>' => 'seo-fields/<controller>/<action>',
                    'seo-fields/<controller:(redirects|not-found)>/<action>/<id:\d+>' => 'seo-fields/<controller>/<action>',
                    'seo-fields/<controller:(redirects|not-found)>/<action>' => 'seo-fields/<controller>/<action>',
                    'seo-fields/<controller:(defaults|robots|sitemap)>/<siteHandle:{handle}>' => 'seo-fields/<controller>/settings',
                ]);
            }
        );
    }

    private function _registerCpListeners()
    {
        Event::on(
            Sites::class,
            Sites::EVENT_AFTER_SAVE_SITE,
            function (SiteEvent $event) {
                if ($event->isNew) {
                    SeoFields::$plugin->defaultsService->copyDefaultsForSite($event->site, $event->oldPrimarySiteId);
                }
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $event) {
                SeoFields::$plugin->sitemapSerivce->clearCacheForElement($event->element);
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_DELETE_ELEMENT,
            function (ElementEvent $event) {
                SeoFields::$plugin->sitemapSerivce->clearCacheForElement($event->element);
            }
        );

        Event::on(
            Sections::class,
            Sections::EVENT_AFTER_DELETE_SECTION,
            function (SectionEvent $event) {
                SeoFields::$plugin->sitemapSerivce->clearCaches();
            }
        );

        Event::on(
            Sections::class,
            Sections::EVENT_AFTER_DELETE_ENTRY_TYPE,
            function (EntryTypeEvent $event) {
                SeoFields::$plugin->sitemapSerivce->clearCaches();
            }
        );

        Event::on(Gc::class, Gc::EVENT_RUN, function () {
            try {
                $limit = SeoFields::$plugin->getSettings()->notFoundLimit;
                if (!is_int($limit)) {
                    return;
                }

                $query = NotFoundRecord::find();
                $query->offset($limit);
                $query->orderBy('dateLastHit ASC');
                foreach ($query->all() as $row) {
                    $row->delete();
                }
            } catch (Exception $e) {
                Craft::error($e->getMessage(), __CLASS__);
            }
        });
    }

    private function _registerSiteListeners()
    {
        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function (ExceptionEvent $event) {
                try {
                    if ($event->exception instanceof HttpException && $event->exception->statusCode === 404 && Craft::$app->getRequest()->getIsSiteRequest()) {
                        Craft::debug("404 exception, processing...", __CLASS__);
                        SeoFields::getInstance()->notFoundService->handleNotFoundException();
                    }
                } catch (Exception $e) {
                    Craft::error($e->getMessage(), __CLASS__);
                }
            }
        );
    }

    private function _registerCacheOptions()
    {
        Event::on(
            ClearCaches::class,
            ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            function (RegisterCacheOptionsEvent $event) {
                // Register our Control Panel routes
                $event->options = array_merge(
                    $event->options, [
                    [
                        "key" => 'seofields_sitemaps',
                        "label" => "Sitemap caches (SEO Fields)",
                        "action" => [SeoFields::$plugin->sitemapSerivce, 'clearCaches']
                    ]
                ]);
            }
        );
    }

    private function _registerCustomElements()
    {
        $elements = [];
        if (Craft::$app->getPlugins()->isPluginEnabled('calendar')) {
            $elements[] = \Solspace\Calendar\Elements\Event::class;
        }
        if (Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
            $elements[] = \craft\commerce\elements\Product::class;
        }

        if ($elements) {
            Event::on(SeoFields::class, SeoFields::EVENT_SEOFIELDS_REGISTER_ELEMENT,
                function (RegisterSeoElementEvent $event) use ($elements) {
                    $event->elements = array_merge($event->elements, $elements);
                }
            );
        }
    }

}
