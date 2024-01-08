<?php

namespace studioespresso\seofields\models;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\AssetQuery;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\ImageTransform;
use craft\web\View;
use Spatie\SchemaOrg\Schema;
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

    public function getSchema(Element $element = null)
    {
        if (!$element) {
            return null;
        }

        if (!$element->getShouldRenderSchema()) {
            return null;
        }

        try {
            $settings = $this->siteDefault->getSchema();
            switch (get_class($element)) {
                case Entry::class:
                    $schemaSettings = $settings['sections'];
                    $sectionId = $element->section->id;
                    $schemaClass = $schemaSettings[$sectionId];

                    /** @var $schema Schema */
                    $schema = Craft::createObject($schemaClass);
                    $schema->name($this->getPageTitle($element, false) ?? "");
                    $schema->description($this->getMetaDescription() ?? "");
                    $schema->url($element->getUrl() ?? "");
                    break;
                case Category::class:
                    $schemaSettings = $settings['groups'];
                    $sectionId = $element->section->id;
                    $schemaClass = $schemaSettings[$sectionId];

                    /** @var $schema Schema */
                    $schema = Craft::createObject($schemaClass);
                    $schema->name($this->getPageTitle($element, false) ?? "");
                    $schema->description($this->getMetaDescription() ?? "");
                    $schema->url($element->getUrl() ?? "");
                    break;
            }
            Craft::$app->getView()->registerScript(
                Json::encode($schema),
                View::POS_END, [
                    'type' => 'application/ld+json'
                ]
            );
        } catch (\Exception $e) {
            Craft::error($e, SeoFields::class);
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

    public function getOgTitle($element = null)
    {
        $title = ($element->getFacebookTitle() ?? $this->facebookTitle) ?? $this->getPageTitle($element, false);
        return $title . $this->getSiteNameWithSeperator();
    }

    public function getTwitterTitle($element = null)
    {
        $title = ($element->getTwitterTitle() ?? $this->twitterTitle) ?? $this->getPageTitle($element, false);
        return $title . $this->getSiteNameWithSeperator();
    }

    public function getMetaDescription()
    {
        return ($this->element->getMetaDescription() ?? $this->metaDescription) ?? $this->siteDefault->defaultMetaDescription;
    }

    public function getOgDescription()
    {
        return ($this->element->getFacebookDescription() ?? $this->facebookDescription) ?? $this->siteDefault->defaultMetaDescription;
    }

    public function getTwitterDescription()
    {
        return ($this->element->getTwitterDescription() ?? $this->twitterDescription) ?? $this->siteDefault->defaultMetaDescription;
    }

    public function getOgImage(Asset $asset = null)
    {
        if ($asset) {
            $asset = $asset;
        } elseif ($this->element->getFacebookImage()) {
            $asset = $this->element->getFacebookImage();
        } elseif ($this->facebookImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->facebookImage[0]);
        } elseif ($this->siteDefault->defaultImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
        if (!isset($asset)) {
            return false;
        }

        $transform = $this->_getPreviewTransform($asset);
        $transformed = $asset->setTransform($transform);
        return [
            'height' => $asset->getHeight($transform),
            'width' => $asset->getWidth($transform),
            'url' => $asset->getUrl($transform, true),
            'alt' => $asset->title,
        ];
    }

    public function getTwitterImage(Asset $value = null)
    {
        if ($value) {
            $asset = $value;
        } elseif ($this->element->getTwitterImage()) {
            $asset = $this->element->getTwitterImage();
        } elseif ($this->twitterImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->twitterImage[0]);
        } elseif ($this->siteDefault->defaultImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
        if (!isset($asset)) {
            return false;
        }

        $transform = $this->_getPreviewTransform($asset);
        $transformed = $asset->setTransform($transform);
        return [
            'height' => $asset->getHeight($transform),
            'width' => $asset->getWidth($transform),
            'url' => $asset->getUrl($transform, true),
            'alt' => $asset->title,
        ];
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
        $currentSite = Craft::$app->getSites()->getCurrentSite()->id;
        $sites = $siteEntries;
        if (empty($sites)) {
            return false;
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

    public function setMetaTitle($value = null)
    {
        $this->metaTitle = $value;
    }

    public function setMetaDescription($value)
    {
        $this->metaDescription = $value;
    }

    public function setFacebookTitle($value)
    {
        $this->facebookTitle = $value;
    }

    public function setFacebookDescription($value)
    {
        $this->facebookDescription = $value;
    }

    public function setFacebookImage($value)
    {
        if (is_object($value) && get_class($value) === AssetQuery::class) {
            $asset = $value->one()->id;
        } elseif (is_object($value) && get_class($value) === Asset::class) {
            $asset = $value->id;
        } else {
            $asset = $value;
        }
        $this->facebookImage = [$asset];
    }

    public function setTwitterTitle($value)
    {
        $this->twitterTitle = $value;
    }

    public function setTwitterDescription($value)
    {
        $this->twitterDescription = $value;
    }

    public function setTwitterImage($value)
    {
        if (is_object($value) && get_class($value) === AssetQuery::class) {
            $asset = $value->one()->id;
        } elseif (is_object($value) && get_class($value) === Asset::class) {
            $asset = $value->id;
        } else {
            $asset = $value;
        }
        $this->twitterImage = [$asset];
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
