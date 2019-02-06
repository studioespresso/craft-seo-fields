<?php

namespace studioespresso\seofields\services;

use craft\base\Model;
use craft\helpers\Json;
use craft\models\Site;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

use Craft;
use craft\base\Component;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class SitemapService extends Component
{
    public function shouldRenderBySiteId(Site $site)
    {
        $data = SeoFields::$plugin->defaultsService->getRecordForSite($site);
        $sitemapSettings = Json::decode($data->sitemap);

        $shouldRender = array_filter($sitemapSettings, function ($section) use ($sitemapSettings) {
            if (isset($sitemapSettings[$section]['enabled'])) {
                $site = Craft::$app->getSites()->getCurrentSite();
                $sectionSites = Craft::$app->getSections()->getSectionById($section)->siteSettings;
                if (isset($sectionSites[$site->id])) {
                    return true;
                }
            } else {
                return false;
            }
        }, ARRAY_FILTER_USE_KEY);
        if ($shouldRender) {
            return $data;
        } else {
            return false;
        }
    }

    public function getSitemap($data)
    {
    }
}
