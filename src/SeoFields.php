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
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\SiteEvent;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\utilities\ClearCaches;
use craft\web\UrlManager;
use craft\web\View;
use studioespresso\seofields\fields\SeoField;
use studioespresso\seofields\models\Settings;
use studioespresso\seofields\services\DefaultsService;
use studioespresso\seofields\services\RenderService;
use studioespresso\seofields\services\SitemapService;
use studioespresso\seofields\variables\SeoFieldsVariable;
use yii\base\Event;

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
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            "defaultsService" => DefaultsService::class,
            "sitemapSerivce" => SitemapService::class,
            "renderService" => RenderService::class
        ]);

        Craft::$app->view->hook('seo-fields', function (array &$context) {
            return $this->renderService->renderMeta($context);
        });

        $this->_registerField();
        $this->_registerCpRoutes();
        $this->_registerFrontendRoutes();
        $this->_registerPermissions();
        $this->_registerSiteEvents();
        $this->_registerCacheOptions();
    }

    public function getCpNavItem()
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        $currentUser = Craft::$app->getUser()->getIdentity();
        // Only show sub-navs the user has permission to view
        if ($currentUser->can('seo-fields:defaults')) {
            $subNavs['defaults'] = [
                'label' => 'Defaults',
                'url' => 'seo-fields/defaults',
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
    protected function createSettingsModel()
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

    protected function afterInstall()
    {
        if (!Craft::$app->getRequest()->isConsoleRequest) {
            parent::afterInstall();
            Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('seo-fields'))->send();
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
                $event->permissions[Craft::t('seo-fields', 'SEO Fields')] = [
                    'seo-fields:default' => [
                        'label' => Craft::t('seo-fields', 'Defaults'),
                    ],
                    'seo-fields:robots' => [
                        'label' => Craft::t('seo-fields', 'Robots'),
                    ],
                    'seo-fields:sitemap' => [
                        'label' => Craft::t('seo-fields', 'Sitemap'),
                    ],
                ];
            }
        );
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
                        'sitemap_<siteId:\d>_<type:(sections|products)>_<sectionId:\d>_<handle:.*>.xml' => 'seo-fields/sitemap/detail'
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
                    'seo-fields/<controller:(defaults|robots|sitemap)>' => 'seo-fields/<controller>/index',
                    'seo-fields/<controller:(defaults|robots|sitemap)>/<siteHandle:{handle}>' => 'seo-fields/<controller>/settings',
                ]);
            }
        );
    }

    private function _registerSiteEvents()
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

}
