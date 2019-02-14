<?php

namespace studioespresso\seofields\services;

use craft\base\Model;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\services\ProductTypes;
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
        if (!$sitemapSettings) {
            return false;
        }

        if (isset($sitemapSettings['sections'])) {
            $shouldRenderSections = array_filter($sitemapSettings['sections'], function ($section) use ($sitemapSettings) {
                if (isset($sitemapSettings['sections'][$section]['enabled'])) {
                    $site = Craft::$app->getSites()->getCurrentSite();

                    $sectionSites = Craft::$app->getSections()->getSectionById($section)->siteSettings;
                    if (isset($sectionSites[$site->id])) {
                        return true;
                    }
                } else {
                    return false;
                }
            }, ARRAY_FILTER_USE_KEY);
        }

        if (isset($sitemapSettings['products'])) {
            $shouldRenderProducts = array_filter($sitemapSettings['products'], function ($productType) use ($sitemapSettings) {
                if (isset($sitemapSettings['products'][$productType]['enabled'])) {
                    $productTypeService = new ProductTypes();
                    $site = Craft::$app->getSites()->getCurrentSite();
                    foreach ($productTypeService->getProductTypeSites($productType) as $productTypeSite) {
                        if ($productTypeSite->siteId == $site->id) {
                            return true;
                        }
                        return false;
                    }
                } else {
                    return false;
                }
            }, ARRAY_FILTER_USE_KEY);
        }

        if ($shouldRenderSections || $shouldRenderProducts) {
            return $data;
        } else {
            return false;
        }
    }

    public function getSitemap($data)
    {
    }
}
