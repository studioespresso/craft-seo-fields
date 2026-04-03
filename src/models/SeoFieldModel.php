<?php

namespace studioespresso\seofields\models;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\models\ImageTransform;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\WebPage;
use studioespresso\seofields\SeoFields;

class SeoFieldModel extends Model
{
    public $metaTitle;
    public $metaDescription;

    public $facebookTitle;
    public $facebookDescription;
    public $facebookImage;

    public $twitterTitle;
    public $twitterDescription;
    public $twitterImage;

    public $siteName;
    public $hideSiteName;
    public $siteId;
    public $canonical;

    public $schema;
    public $allowIndexing = 'yes';

    /**
     * @var SeoDefaultsModel
     */
    public $siteDefault;

    public $element;

    public function init(): void
    {
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }
        $this->siteDefault = SeoFields::getInstance()->defaultsService->getDataBySite($site);
    }

    public function getDefaults()
    {
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }
        $this->siteDefault = SeoFields::getInstance()->defaultsService->getDataBySite($site);
    }

    public function getSchema(?Element $element = null)
    {
        if (!$element) {
            return null;
        }

        /** @phpstan-ignore-next-line */
        if (!$element->getShouldRenderSchema()) {
            return null;
        }

        try {
            $schemaService = SeoFields::getInstance()->schemaService;
            $primarySite = Craft::$app->getSites()->getPrimarySite();
            $defaults = SeoFields::getInstance()->defaultsService->getDataBySite($primarySite);
            $settings = $defaults->getSchema();

            $graph = $schemaService->getGraph();

            $entityName = $defaults->organizationName ?: ($defaults->defaultSiteTitle ?: Craft::$app->getSystemName());

            $entityClass = $defaults->siteEntity ?: get_class(Schema::organization());
            $entityMethod = $schemaService->getGraphMethodName($entityClass);

            $entity = $graph->{$entityMethod}()
                ->setProperty('@id', '#organization')
                ->name($entityName)
                ->url(UrlHelper::siteUrl());

            if (!empty($defaults->organizationLogo)) {
                $logoAsset = Craft::$app->getAssets()->getAssetById(is_array($defaults->organizationLogo) ? $defaults->organizationLogo[0] : $defaults->organizationLogo);
                if ($logoAsset) {
                    $entity->logo($logoAsset->getUrl());
                }
            }

            if (!empty($defaults->sameAs) && is_array($defaults->sameAs)) {
                $entity->sameAs($defaults->sameAs);
            }

            $graph->webSite()
                ->setProperty('@id', '#website')
                ->name($entityName)
                ->publisher(['@id' => '#organization'])
                ->url(UrlHelper::siteUrl());

            $schemaClass = WebPage::class;

            switch (get_class($element)) {
                case Entry::class:
                    if (isset($settings['sections'])) {
                        $sectionId = $element->section->id;
                        $schemaClass = $settings['sections'][$sectionId] ?? WebPage::class;
                    }
                    break;
                case Category::class:
                    if (isset($settings['categories'])) {
                        $groupId = $element->group->id;
                        $schemaClass = $settings['categories'][$groupId] ?? WebPage::class;
                    }
                    break;
            }

            if (!empty($this->schema)) {
                $schemaClass = $this->schema;
            }

            $method = $schemaService->getGraphMethodName($schemaClass);
            $pageNode = $graph->{$method}()
                ->setProperty('@id', '#page')
                ->author(['@id' => '#organization'])
                ->isPartOf(['@id' => '#website'])
                ->url($element->getUrl() ?? "");
            $schemaService->setPageNode($pageNode);
            $schemaService->setPageDefaults($this, $element);
        } catch (\Exception $e) {
            \Craft::error($e, SeoFields::class);
            return null;
        }
    }

    public function getSiteNameWithSeperator()
    {
        $this->getDefaults();
        if ($this->hideSiteName) {
            return false;
        }
        if ($this->siteName) {
            $siteName = $this->siteName;
        } elseif ($this->siteDefault->defaultSiteTitle) {
            $siteName = $this->siteDefault->defaultSiteTitle;
        } else {
            $siteName = Craft::$app->getSystemName();
        }

        $seperator = $this->siteDefault->titleSeperator ? $this->siteDefault->titleSeperator : '-';
        return ' ' . $seperator . ' ' . $siteName;
    }


    public function getPageTitle($element = null, $includeSiteName = true)
    {
        if ($element) {
            $this->element = $element;
        }
        if ($element && $element->getSocialTitle()) {
            return $element->getSocialTitle() . ($includeSiteName ? $this->getSiteNameWithSeperator() : '');
        }
        if ($element && !$this->metaTitle) {
            return $element->title . ($includeSiteName ? $this->getSiteNameWithSeperator() : '');
        }
        return $this->metaTitle . ($includeSiteName ? $this->getSiteNameWithSeperator() : '');
    }

    public function getCanonical()
    {
        $request = Craft::$app->getRequest();
        return $request->hostInfo . '/' . $request->getPathInfo(true);
    }


    public function getMetaTitle($element)
    {
        $element = $element ?? $this->element;
        $title = $this->getPageTitle($element, false);

        if ($element->getMetaTitle()) {
            $title = $element->getMetaTitle();
        } elseif ($this->metaTitle) {
            $title = $this->metaTitle;
        }
        return $title;
    }

    public function getSocialTitle($element)
    {
        $title = $this->getPageTitle($element, false);

        if ($element->getSocialTitle()) {
            $title = $element->getSocialTitle();
        } elseif ($this->facebookTitle) {
            $title = $this->facebookTitle;
        }

        return $title . $this->getSiteNameWithSeperator();
    }

    public function getOgTitle($element = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getOgTitle', "getOgTitle has been replaced by `getSocialTitle` and will be removed in a later update");
        return $this->getSocialTitle($element);
    }

    public function getTwitterTitle($element = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterTitle', "getTwitterTitle has been replaced by `getSocialTitle` and will be removed in a later update");
        return $this->getSocialTitle($element);
    }

    public function getMetaDescription()
    {
        if ($this->element && $this->element->getSocialDescription()) {
            return $this->element->getSocialDescription();
        }

        if ($this->element->getMetaDescription()) {
            return $this->element->getMetaDescription();
        }

        if ($this->metaDescription) {
            return $this->metaDescription;
        }

        return $this->siteDefault->defaultMetaDescription;
    }

    public function getSocialDescription()
    {
        if ($this->element && $this->element->getSocialDescription()) {
            return $this->element->getSocialDescription();
        }

        if ($this->facebookDescription) {
            return $this->facebookDescription;
        }

        return $this->siteDefault->defaultMetaDescription;
    }

    public function getOgDescription()
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getOgDescription', "getOgDescription has been replaced by `getSocialDescription` and will be removed in a later update");
        return $this->getSocialDescription();
    }

    public function getTwitterDescription()
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterDescription', "getTwitterDescription has been replaced by `getSocialDescription` and will be removed in a later update");
        return $this->getSocialDescription();
    }


    public function getSocialImage(?Asset $asset = null)
    {
        if ($asset) {
            $asset = $asset;
        } elseif ($this->element->getSocialImage()) {
            $asset = $this->element->getSocialImage();
        } elseif ($this->facebookImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->facebookImage[0]);
        } elseif ($this->siteDefault->defaultImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
        if (!isset($asset)) {
            return false;
        }

        $transform = $this->_getPreviewTransform($asset);
        return [
            'height' => $asset->getHeight($transform),
            'width' => $asset->getWidth($transform),
            'url' => $asset->getUrl($transform, true),
            'alt' => $asset->title,
        ];
    }

    public function getOgImage(?Asset $asset = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getOgImage', "getOgImage has been replaced by `getSocialImage` and will be removed in a later update");
        return $this->getSocialImage($asset);
    }

    public function getTwitterImage(?Asset $asset = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterImage', "getTwitterImage has been replaced by `getSocialImage` and will be removed in a later update");
        return $this->getSocialImage($asset);
    }

    public function getAlternate($element = null)
    {
        if (!$element) {
            return false;
        }
        $siteEntries =
            (new Query())->select(['siteId', 'uri', 'language', 'sites.primary as primary'])
                ->from('{{%elements_sites}} as  elements')
                ->leftJoin('{{%sites}} as sites', 'sites.id = elements.siteId')
                ->where('[[elementId]] = ' . $element->id)
                ->andWhere('sites.enabled = 1')
                ->andWhere('sites.dateDeleted IS NULL')
                ->andWhere('elements.enabled = true')
                ->distinct(true)
                ->all();
        $seperatedSiteGroups = SeoFields::getInstance()->getSettings()->logicallySeperatedSiteGroups;
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        $sites = $siteEntries;
        if (empty($sites)) {
            return false;
        }

        if ($seperatedSiteGroups) {
            $currentSiteGroupId = $currentSite->groupId;
            $sites = array_filter($sites, function($siteEntry) use ($currentSiteGroupId) {
                $site = Craft::$app->getSites()->getSiteById($siteEntry['siteId']);
                return $site->groupId === $currentSiteGroupId;
            });
        }

        $data = [];
        foreach ($sites as $site) {
            if ($site['uri']) {
                $data[] = [
                    'url' => UrlHelper::siteUrl($site['uri'] === '__home__' ? '' : $site['uri'], null, null, $site['siteId']),
                    'language' => $site['language'],
                ];
            }
        }

        return $data;
    }


    /**
     * Override setAttributes to assign properties directly, bypassing deprecated setters.
     * This prevents deprecation warnings from firing during normalizeValue().
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        // These properties have deprecated setters that we don't want triggered by setAttributes()
        $directProperties = ['metaTitle', 'metaDescription', 'facebookTitle', 'facebookDescription', 'facebookImage', 'twitterTitle', 'twitterDescription', 'twitterImage'];

        if (is_array($values)) {
            foreach ($directProperties as $prop) {
                if (array_key_exists($prop, $values)) {
                    $this->$prop = $values[$prop];
                    unset($values[$prop]);
                }
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setMetaTitle($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setMetaTitle', "Overwriting SEO properties through `entry.seo.setMetaTitle` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setMetaDescription($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setMetaDescription', "Overwriting SEO properties through `entry.seo.setMetaDescription` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setFacebookTitle($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookTitle', "Overwriting SEO properties through `entry.seo.setFacebookTitle` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setFacebookDescription($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookDescription', "Overwriting SEO properties through `entry.seo.setFacebookDescription` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setFacebookImage($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookImage', "Overwriting SEO properties through `entry.seo.setFacebookImage` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setTwitterTitle($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterTitle', "Overwriting SEO properties through `entry.seo.setTwitterTitle` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setTwitterDescription($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterDescription', "Overwriting SEO properties through `entry.seo.setTwitterDescription` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @deprecated Overwriting SEO properties through this method no longer works.
     */
    public function setTwitterImage($value = null): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterImage', "Overwriting SEO properties through `entry.seo.setTwitterImage` no longer works. Please see the docs for an upgrading guide.");
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [
                [
                    'metaTitle',
                    'metaDescription',
                    'siteName',
                    'hideSiteName',
                    'facebookTitle',
                    'facebookDescription',
                    'facebookImage',
                    'twitterTitle',
                    'twitterDescription',
                    'twitterImage',
                    'allowIndexing',
                    'schema',
                ],
                'safe',
            ],
        ];
    }

    private function _getPreviewTransform(Asset $asset)
    {
        $transform = new ImageTransform();
        $transform->width = 1200;
        $transform->height = 590;
        $transform->mode = 'crop';
        if ($asset->hasFocalPoint) {
            $transform->position = implode(',', $asset->focalPoint);
        }
        return $transform;
    }
}
