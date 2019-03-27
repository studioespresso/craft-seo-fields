<?php

namespace studioespresso\seofields\services;

use craft\base\Element;
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
use yii\caching\TagDependency;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class SitemapService extends Component
{

    const SITEMAP_CACHE_KEY = 'seofields_cache_sitemaps';

    public function shouldRenderBySiteId(Site $site)
    {
        $data = SeoFields::$plugin->defaultsService->getRecordForSiteId($site->id);
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

        $currentSite = Craft::$app->getSites()->getCurrentSite();
        $cacheDependency = new TagDependency([
            'tags' => [
                self::SITEMAP_CACHE_KEY,
                self::SITEMAP_CACHE_KEY . '_index_site' . $currentSite->id
            ]
        ]);

        $xml = Craft::$app->getCache()->getOrSet(
            self::SITEMAP_CACHE_KEY . '_index_site' . $currentSite->id,
            function () use ($data, $currentSite) {
                $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
                $xml[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
                if (isset($data['sections'])) {
                    $xml[] = $this->_addSectionsToIndex($data['sections'], $currentSite);
                }
                if (isset($data['products'])) {
                    $xml[] = $this->_addProductsToIndex($data['products'], $currentSite);
                }

                $xml[] = '</sitemapindex>';
                $xml = implode('', $xml);
                return $xml;
            },
            null,
            $cacheDependency
        );
        return $xml;
    }

    public function getSitemapData($siteId, $type, $sectionId)
    {
        $settings = $this->getSettingsBySiteId($siteId);
        switch ($type) {
            case 'products':
                $data = Product::findAll([
                    'siteId' => $siteId,
                    'typeId' => $sectionId,
                ]);
                break;
            case 'sections':
                $data = Entry::findAll([
                    'siteId' => $siteId,
                    'sectionId' => $sectionId
                ]);
                break;
        }

        $cacheDependency = new TagDependency([
            'tags' => [
                self::SITEMAP_CACHE_KEY,
                self::SITEMAP_CACHE_KEY . "_" . $siteId . "_" . $sectionId
            ]
        ]);

        $data = Craft::$app->getCache()->getOrSet(
            '',
            function () use ($data, $type, $settings, $sectionId) {
                return $this->_addEntriesToSitemap($data, $settings[$type][$sectionId]);
            },
            null,
            $cacheDependency
        );

        return $data;
    }

    public function clearCaches($tags = [self::SITEMAP_CACHE_KEY])
    {
        TagDependency::invalidate(
            Craft::$app->getCache(),
            $tags
        );
    }

    public function clearCacheForElement(Element $element)
    {
        $elementType = get_class($element);
        $typeHandle = explode('\\', $elementType);
        $typeHandle = end($typeHandle);
        switch (strtolower($typeHandle)) {
            case 'entry':
                $section = Craft::$app->getSections()->getSectionById($element->sectionId);
                $id = $section->id;
                break;
            default:
                return false;
                break;
        }

        if($id) {
            $this->clearCaches([
                self::SITEMAP_CACHE_KEY . '_index_site' . $element->siteId,
                self::SITEMAP_CACHE_KEY . "_" .$element->siteId . "_" . $id
            ]);
        }
    }

    private function getSettingsBySiteId($siteId)
    {
        $settings = SeoFields::$plugin->defaultsService->getDataBySiteId($siteId);
        return Json::decodeIfJson($settings->sitemap);
    }

    private function _addEntriesToSitemap($entries, $settings)
    {
        $data = [];
        $data[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($entries as $entry) {
            if ($entry->getUrl()) {
                $data[] = "<url>";
                $data[] = "<loc>" . $entry->getUrl() . "</loc>";
                $data[] = "<lastmod>" . $entry->dateUpdated->format('Y-m-d h:m:s') . "</lastmod>";
                $data[] = "<changefreq>" . $settings['changefreq'] . "</changefreq>";
                $data[] = "<priority>" . $settings['priority'] . "</priority>";
                $data[] = "</url>";
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
                $data[] = Craft::$app->getRequest()->getHostInfo() . htmlentities('/sitemap_' . $site->id . '_sections_' . $section->id . '_' . $section->handle . '.xml');
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
                $data[] = Craft::$app->getRequest()->getHostInfo() . htmlentities('/sitemap_' . $site->id . '_products_' . $type->id . '_' . $type->handle . '.xml');
                $data[] = '</loc><lastmod>';
                $data[] = $typeEntry->dateUpdated->format('Y-m-d h:m:s');
                $data[] = '</lastmod></sitemap>';
            }
        }
        return $data = implode('', $data);
    }
}
