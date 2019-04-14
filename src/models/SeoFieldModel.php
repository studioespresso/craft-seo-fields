<?php

namespace studioespresso\seofields\models;

use Craft;
use craft\base\Model;
use craft\db\Query;
use craft\elements\Asset;
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

    public function getMetaDescription() {
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
        if($asset) {
            $asset = $asset;
        } elseif($this->facebookImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->facebookImage[0]);
        } elseif ($this->siteDefault->defaultImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
        if (!isset($asset)) {
            return false;
        }

        $transform = $this->_getPreviewTransform();
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
        if($asset) {
            $asset = $asset;
        } elseif($this->twitterImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->twitterImage[0]);
        } elseif ($this->siteDefault->defaultImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
        if (!isset($asset)) {
            return false;
        }

        $transform = $this->_getPreviewTransform();
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
        if(!$element) {
            return false;
        }
        $siteEntries =
            (new Query())->select(['siteId', 'uri', 'language'])
                ->from('{{%elements_sites}}')
                ->leftJoin('{{sites}}', 'sites.id = elements_sites.siteId')
                ->where('[[elementId]] = ' . $element->id)
                ->andWhere('enabled = true')
                ->all();
        $currentSite = Craft::$app->getSites()->getCurrentSite()->id;
        $sites = array_filter($siteEntries, function ($item) use ($currentSite) {
            if ($item['siteId'] != $currentSite) {
                return true;
            }
            return false;
        });
        if (empty($sites)) {
            return false;
        }

        $data = [];
        foreach ($sites as $site) {
            $data[] = [
                'url' => UrlHelper::siteUrl($site['uri'], null, null, $site['siteId']),
                'language' => $site['language']
            ];
        }

        return $data;
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

    private function _getPreviewTransform()
    {
        $transform = new AssetTransform();
        $transform->width = 1200;
        $transform->height = 590;
        $transform->mode = 'crop';
        return $transform;
    }
}