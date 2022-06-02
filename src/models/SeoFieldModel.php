<?php

namespace studioespresso\seofields\models;

use Craft;
use craft\base\Model;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\helpers\UrlHelper;
use craft\models\AssetTransform;
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

    /**
     * @var SeoDefaultsModel
     */
    public $siteDefault;

    public function getDefaults()
    {
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }
        $this->siteDefault = SeoFields::getInstance()->defaultsService->getDataBySite($site);
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

    public function getPageTitle($element = null)
    {
        if ($element && !$this->metaTitle) {
            return $element->title . $this->getSiteNameWithSeperator();
        }
        return $this->metaTitle . $this->getSiteNameWithSeperator();
    }

    public function getCanonical()
    {
        $request = Craft::$app->getRequest();
        return Craft::$app->getSites()->getCurrentSite()->baseUrl . $request->pathInfo
    }

    public function getOgTitle($element)
    {
        if ($this->facebookTitle) {
            return $this->facebookTitle . $this->getSiteNameWithSeperator();
        } else {
            return $this->getPageTitle($element);
        }
    }

    public function getTwitterTitle($element)
    {
        if ($this->twitterTitle) {
            return $this->twitterTitle . $this->getSiteNameWithSeperator();
        } else {
            return $this->getPageTitle($element);
        }
    }

    public function getMetaDescription()
    {
        return $this->metaDescription ? $this->metaDescription : $this->siteDefault->defaultMetaDescription;

    }

    public function getOgDescription()
    {
        return $this->facebookDescription ? $this->facebookDescription : $this->getMetaDescription();
    }

    public function getTwitterDescription()
    {
        return $this->twitterDescription ? $this->twitterDescription : $this->getMetaDescription();
    }

    public function getOgImage(Asset $asset = null)
    {
        if ($asset) {
            $asset = $asset;
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
            'url' => $asset->getUrl($transform),
            'alt' => $asset->title,
        ];
    }

    public function getTwitterImage(Asset $asset = null)
    {
        if ($asset) {
            $asset = $asset;
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
            'url' => $asset->getUrl($transform),
            'alt' => $asset->title,
        ];
    }

    public function getAlternate($element)
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
                    'language' => $site['language']
                ];
            }
        }

        return $data;
    }

    public function setMetaTitle($value)
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
                ],
                'safe',
            ],
        ];
    }

    private function _getPreviewTransform(Asset $asset)
    {
        $transform = new AssetTransform();
        $transform->width = 1200;
        $transform->height = 590;
        $transform->mode = 'crop';
        if ($asset->hasFocalPoint) {
            $transform->position = implode(',', $asset->focalPoint);
        }
        return $transform;
    }
}