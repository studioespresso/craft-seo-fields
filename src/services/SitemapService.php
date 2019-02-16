<?php

namespace studioespresso\seofields\services;

use craft\base\Model;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\services\ProductTypes;
use craft\elements\Entry;
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

        $shouldRenderProducts = false;
        $shouldRenderSections = false;

        if (isset($sitemapSettings['sections'])) {
            $shouldRenderSections = array_filter($sitemapSettings['sections'], function ($section) use ($sitemapSettings) {
                if (isset($sitemapSettings['sections'][$section]['enabled'])) {
                    $site = Craft::$app->getSites()->getCurrentSite();
                    $sectionSites = Craft::$app->getSections()->getSectionById($section)->siteSettings;
                    if (isset($sectionSites[$site->id]) && $sectionSites[$site->id]->hasUrls) {
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
                        if ($productTypeSite->siteId == $site->id && $productTypeSite->hasUrls) {
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
            return [
                'products' => $shouldRenderProducts,
                'sections' => $shouldRenderSections
            ];
        } else {
            return false;
        }
    }

    public function getSitemapIndex($data)
    {
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if (isset($data['sections'])) {
            $xml[] = $this->_addSectionsToIndex($data['sections']);
        }

        $xml[] = '</sitemapindex>';
        $xml = implode('', $xml);
        return $xml;
    }

    private function _addSectionsToIndex($sections)
    {
        $data = [];
        foreach ($sections as $id => $settings) {
            $section = Craft::$app->getSections()->getSectionById($id);
            $sectionEntry = Entry::findOne(['sectionId' => $id]);
            if($sectionEntry) {
                $data[] = '<sitemap>';
                $data[] = '<loc>';
                $data[] = Craft::$app->getRequest()->getBaseUrl() . '/sitemap_' . $section->handle . '_' . $section->id . '.xml';
                $data[] = '</loc>';
                $data[] = '<lastmod>';
                $data[] = $sectionEntry->dateUpdated->format('Y-m-d h:m:s');
                $data[] = '</lastmod>';
                $data[] = '</sitemap>';
            }
        }

        return $data = implode('', $data);
    }
}
