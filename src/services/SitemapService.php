<?php

namespace studioespresso\seofields\services;

use craft\base\Model;
use craft\commerce\elements\Product;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\Plugin as Commerce;
use craft\commerce\services\ProductTypes;
use craft\elements\Entry;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\Json;
use craft\models\Site;
use craft\web\UrlManager;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

use Craft;
use craft\base\Component;
use yii\base\Event;

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
        $currentSite = Craft::$app->getSites()->getCurrentSite();
        if (isset($data['sections'])) {
            $xml[] = $this->_addSectionsToIndex($data['sections'], $currentSite);
        }
        if (isset($data['products'])) {
            $xml[] = $this->_addProductsToIndex($data['products'], $currentSite);
        }

        $xml[] = '</sitemapindex>';
        $xml = implode('', $xml);
        return $xml;
    }

    public function getSitemapData($siteId, $type, $sectionId)
    {
        switch ($type) {
            case 'products':
                $data = Product::findAll([
                    'siteId' => $siteId,
                    'typeId' => $sectionId,
                ]);
                break;
            case 'section':
                $data = Entry::findAll([
                    'siteId' => $siteId,
                    'sectionId' => $sectionId
                ]);
                break;
        }

        return $this->_addEntriesToIndex($data);
    }

    private function _addEntriesToIndex($entries) {
        $data = [];
        $data[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($entries as $entry) {
            if ($entry->getUrl()) {
                $data[] = '<url><loc>';
                $data[] = $entry->getUrl();
                $data[] = '</loc><lastmod>';
                $data[] = $entry->dateUpdated->format('Y-m-d h:m:s');
                $data[] = '</lastmod></url>';
            }
        }
        $data[] = '</urlset>';
        return $data = implode('', $data);
    }

    private function _addSectionsToIndex($sections, $site)
    {
        $data = [];
        foreach ($sections as $id => $settings) {
            $section = Craft::$app->getSections()->getSectionById($id);
            $sectionEntry = Entry::findOne(['sectionId' => $id]);
            if ($sectionEntry) {
                $data[] = '<sitemap><loc>';
                $data[] = Craft::$app->getRequest()->getBaseUrl() . htmlentities('/sitemap_' . $site->id . '_section_' . $section->id . '_' . $section->handle . '.xml');
                $data[] = '</loc><lastmod>';
                $data[] = $sectionEntry->dateUpdated->format('Y-m-d h:m:s');
                $data[] = '</lastmod></sitemap>';
            }
        }
        return $data = implode('', $data);
    }

    private function _addProductsToIndex($productTypes, $site)
    {
        $data = [];
        foreach ($productTypes as $id => $settings) {
            $type = Commerce::getInstance()->productTypes->getProductTypeById($id);
            $typeEntry = Product::findOne(['typeId' => $type->id]);
            if ($typeEntry) {
                $data[] = '<sitemap><loc>';
                $data[] = Craft::$app->getRequest()->getBaseUrl() . htmlentities('/sitemap_' . $site->id . '_products_' . $type->id . '_' . $type->handle. '.xml');
                $data[] = '</loc><lastmod>';
                $data[] = $typeEntry->dateUpdated->format('Y-m-d h:m:s');
                $data[] = '</lastmod></sitemap>';
            }
        }
        return $data = implode('', $data);
    }
}
