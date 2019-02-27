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

    public function getPageTitle()
    {

        $siteDefault = SeoFields::getInstance()->defaultsService->getDataBySite(Craft::$app->getSites()->getCurrentSite());
        if($this->siteName) {
            $siteName = $this->siteName;
        } else {
            $siteName = $siteDefault->defaultSiteTitle;
        }
        return $this->metaTitle . ' ' . $siteDefault->titleSeperator . ' ' . $siteName;
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