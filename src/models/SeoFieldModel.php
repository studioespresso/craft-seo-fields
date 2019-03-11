<?php

namespace studioespresso\seofields\models;

use Craft;
use craft\base\Model;
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
    public $siteName;
    public $hideSiteName;

    /**
     * @var SeoDefaultsModel
     */
    public $siteDefault;

    public function init()
    {
        $this->siteDefault = $siteDefault = SeoFields::getInstance()->defaultsService->getDataBySite(Craft::$app->getSites()->getCurrentSite());
    }

    public function getSiteNameWithSeperator()
    {
        if($this->hideSiteName) {
           return false;
        }
        if ($this->siteName) {
            $siteName = $this->siteName;
        } else {
            $siteName = $this->siteDefault->defaultSiteTitle;
        }
        return ' ' . $this->siteDefault->titleSeperator . ' ' . $siteName;
    }

    public function getPageTitle($element = null)
    {
        if ($this->siteName) {
            $siteName = $this->siteName;
        } else {
            $siteName = $this->siteDefault->defaultSiteTitle;
        }

        if($element && !$this->metaTitle) {
            return $element->title . $this->getSiteNameWithSeperator();
        }

        return $this->metaTitle . $this->getSiteNameWithSeperator();
    }


    public function getOgTitle($element)
    {
        if ($this->siteName) {
            $siteName = $this->siteName;
        } else {
            $siteName = $this->siteDefault->defaultSiteTitle;
        }
        
        if ($this->facebookTitle) {
            return $this->facebookTitle . $this->getSiteNameWithSeperator();
        } elseif($this->metaTitle) {
            return $this->metaTitle . $this->getSiteNameWithSeperator();
        } else {
            return $element->title . $this->getSiteNameWithSeperator();
        }
    }

    public function getOgDescription()
    {
        return $this->facebookDescription ? $this->facebookDescription : $this->metaDescription;
    }

    public function getOgImage()
    {
        if($this->facebookImage) {
            $asset =  Craft::$app->getAssets()->getAssetById($this->facebookImage[0]);
        } elseif($this->siteDefault->defaultImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
        if(!$asset) {
            return false;
        }

        $transform = new AssetTransform();
        $transform->width = 1200;
        $transform->height = 590;
        $transform->mode = 'fit';

        $transformed = $asset->setTransform($transform);
        return [
            'height' => $asset->getHeight($transform),
            'width' => $asset->getWidth($transform),
            'url' => $asset->getUrl($transform),
        ];
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
                    'facebookImage'
                ],
                'safe',
            ],
        ];
    }
}