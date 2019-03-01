<?php

namespace studioespresso\seofields\models;

use Craft;
use craft\base\Model;
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
        if (!$this->facebookTitle) {
            return $element->title . $this->getSiteNameWithSeperator();
        } else {
            return $this->facebookTitle . $this->getSiteNameWithSeperator();
        }
    }

    public function getOgDescription()
    {
        return $this->facebookDescription ? $this->facebookDescription : $this->metaDescription;
    }

    public function getOgImage()
    {
        if($this->facebookImage) {
            return Craft::$app->getAssets()->getAssetById($this->facebookImage[0]);
        } elseif($this->siteDefault->defaultImage) {
            return Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
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
                    'facebookTitle',
                    'facebookDescription',
                    'facebookImage'
                ],
                'safe',
            ],
        ];
    }
}