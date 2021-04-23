<?php

namespace studioespresso\seofields\services;

use craft\base\Element;
use craft\base\Model;
use craft\commerce\elements\Product;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\Plugin as Commerce;
use craft\commerce\services\ProductTypes;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\UrlManager;
use studioespresso\seofields\events\RegisterSeoElementEvent;
use studioespresso\seofields\events\RegisterSeoSitemapEvent;
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
        $shouldRenderCategories = false;
        $shouldRenderCustom = false;


        if (isset($sitemapSettings['entry'])) {
            $shouldRenderSections = $this->_shouldRenderEntries($sitemapSettings);
        }

        if (isset($sitemapSettings['category'])) {
            $shouldRenderCategories = $this->_shouldRenderCategories($sitemapSettings);
        }

        if (isset($sitemapSettings['product'])) {
            $shouldRenderProducts = $this->_shouldRenderProducts($sitemapSettings);
        }


        if ($shouldRenderSections || $shouldRenderProducts || $shouldRenderCategories || $shouldRenderCustom) {
            return [
                'products' => $shouldRenderProducts,
                'sections' => $shouldRenderSections,
                'categories' => $shouldRenderCategories,
                'custom' => $shouldRenderCustom,
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
        if (!Craft::$app->getConfig()->general->devMode) {
            $duration = null;
        } else {
            $duration = 1;
        }

        $xml = Craft::$app->getCache()->getOrSet(
            self::SITEMAP_CACHE_KEY . '_index_site' . $currentSite->id,
            function () use ($data, $currentSite) {
                $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
                $xml[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
                if (isset($data['sections'])) {
                    $xml[] = $this->_addSectionsToIndex($data['sections'], $currentSite);
                }
                if (isset($data['categories'])) {
                    $xml[] = $this->_addCategoriesToIndex($data['categories'], $currentSite);
                }
                if (isset($data['products'])) {
                    $xml[] = $this->_addProductsToIndex($data['products'], $currentSite);
                }

                $xml[] = '</sitemapindex>';
                $xml = implode('', $xml);
                return $xml;
            },
            $duration,
            $cacheDependency
        );
        return $xml;
    }

    public function getSitemapData($siteId, $type, $sectionId)
    {
        $settings = $this->getSettingsBySiteId($siteId);
        switch ($type) {
            case 'product':
                $data = Product::findAll([
                    'siteId' => $siteId,
                    'typeId' => $sectionId,
                ]);
                break;
            case 'category':
                $data = Category::findAll([
                    'siteId' => $siteId,
                    'groupId' => $sectionId,
                ]);
                break;
            case 'entry':
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
        if (!Craft::$app->getConfig()->general->devMode) {

            $data = Craft::$app->getCache()->getOrSet(
                self::SITEMAP_CACHE_KEY . "_" . $siteId . "_" . $sectionId,
                function () use ($data, $type, $settings, $sectionId) {
                    return $this->_addElementsToSitemap($data, $settings[$type][$sectionId]);
                },
                null,
                $cacheDependency
            );
        } else {
            $data = $this->_addElementsToSitemap($data, $settings[$type][$sectionId]);
        }

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

        if ($id) {
            $this->clearCaches([
                self::SITEMAP_CACHE_KEY . '_index_site' . $element->siteId,
                self::SITEMAP_CACHE_KEY . "_" . $element->siteId . "_" . $id
            ]);
        }
    }

    private function getSettingsBySiteId($siteId)
    {
        $settings = SeoFields::$plugin->defaultsService->getDataBySiteId($siteId);
        return Json::decodeIfJson($settings->sitemap);
    }

    private function _addElementsToSitemap($entries, $settings)
    {
        $data = [];
        $data[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $data[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xhtml="http://www.w3.org/1999/xhtml" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $currentSite = Craft::$app->getSites()->getCurrentSite();
        /** @var $entry Element */
        foreach ($entries as $entry) {
            $siteEntries =
                (new Query())->select(['siteId', 'uri', 'language'])
                    ->from('{{%elements_sites}} as elements_sites')
                    ->leftJoin('{{%sites}} as sites', 'sites.id = elements_sites.siteId')
                    ->where('[[elementId]] = ' . $entry->id)
                    ->andWhere('sites.enabled = true')
                    ->all();
            $sites = array_filter($siteEntries, function ($item) use ($currentSite) {
                if ($item['siteId'] != $currentSite->id) {
                    return true;
                }
                return false;
            });

            if ($entry->getUrl()) {
                $data[] = "<url>";
                $data[] = "<loc>" . $entry->getUrl() . "</loc>";
                $data[] = "<lastmod>" . $entry->dateUpdated->format("Y-m-d") . "</lastmod>";
                $data[] = "<changefreq>" . $settings['changefreq'] . "</changefreq>";
                $data[] = "<priority>" . $settings['priority'] . "</priority>";
                if ($sites) {
                    foreach ($sites as $site) {
                        $url = UrlHelper::siteUrl($site['uri'], null, null, $site['siteId']);
                        $data[] = "<xhtml:link rel='alternate' hreflang='{$site['language']}' href='{$url}'/>";
                    }
                }
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
            $type = Craft::$app->getSections()->getSectionById($id);
            $entry = Entry::findOne(['sectionId' => $id]);
            if ($entry) {
                $data[] = implode('', $this->_addItemToIndex($site, $type, $entry));
            }
        }
        return $data = implode('', $data);
    }

    private function _addCategoriesToIndex($groups, $site)
    {
        $data = [];
        foreach ($groups as $id => $settings) {
            $type = Craft::$app->getCategories()->getGroupById($id);
            $entry = Category::findOne(['groupId' => $type->id]);
            if ($entry) {
                $data[] = implode('', $this->_addItemToIndex($site, $type, $entry));
            }
        }

        return $data = implode('', $data);
    }

    private function _addProductsToIndex($productTypes, $site)
    {
        $data = [];
        foreach ($productTypes as $id => $settings) {
            $type = Commerce::getInstance()->productTypes->getProductTypeById($id);
            $entry = Product::findOne(['typeId' => $type->id]);
            if ($entry) {
                $data[] = implode('', $this->_addItemToIndex($site, $type, $entry));
            }
        }
        return $data = implode('', $data);
    }

    private function _addItemToIndex($site, $type, $entry)
    {
        $data = [];
        $class = explode('\\', get_class($entry));
        $elementName = strtolower(end($class));
        $data[] = '<sitemap><loc>';
        $data[] = UrlHelper::siteUrl(htmlentities('/sitemap_' . $site->id . '_' . $elementName . '_' . $type->id . '_' . strtolower($type->handle) . '.xml'), null, null, $site->id);
        $data[] = '</loc><lastmod>';
        $data[] = $entry->dateUpdated->format('Y-m-d');
        $data[] = '</lastmod></sitemap>';
        return $data;
    }

    private function _shouldRenderEntries($sitemapSettings)
    {
        $shouldRenderSections = array_filter($sitemapSettings['entry'], function ($sectionId) use ($sitemapSettings) {
            $section = Craft::$app->getSections()->getSectionById($sectionId);
            if(!$section) {
                return false;
            }
            if (isset($sitemapSettings['entry'][$sectionId]['enabled'])) {
                $site = Craft::$app->getSites()->getCurrentSite();
                $sectionSites = $section->siteSettings;
                if (isset($sectionSites[$site->id]) && $sectionSites[$site->id]->hasUrls) {
                    return true;
                }
            } else {
                return false;
            }
        }, ARRAY_FILTER_USE_KEY);

        return $shouldRenderSections;
    }

    private function _shouldRenderCategories($sitemapSettings)
    {
        $shouldRenderCategories = array_filter($sitemapSettings['category'], function ($group) use ($sitemapSettings) {
            if (isset($sitemapSettings['category'][$group]['enabled'])) {
                $site = Craft::$app->getSites()->getCurrentSite();
                $groupSites = Craft::$app->getCategories()->getGroupById($group)->siteSettings;
                if (isset($groupSites[$site->id]) && $groupSites[$site->id]->hasUrls) {
                    return true;
                }
            } else {
                return false;
            }
        }, ARRAY_FILTER_USE_KEY);
        return $shouldRenderCategories;
    }

    private function _shouldRenderProducts($sitemapSettings)
    {
        $shouldRenderProducts = array_filter($sitemapSettings['product'], function ($productType) use ($sitemapSettings) {
            if (isset($sitemapSettings['product'][$productType]['enabled'])) {
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
        return $shouldRenderProducts;
    }
}
