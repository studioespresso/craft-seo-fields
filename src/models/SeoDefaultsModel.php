<?php

namespace studioespresso\seofields\models;

use craft\base\Model;

class SeoDefaultsModel extends Model
{
    public $id;

    public $defaultSiteTitle;

    public $defaultImage;

    public $titleSeperator;

    public $siteId;

    public $enableRobots;

    public $robots;

    public $sitemap;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [
                [
                    'defaultSiteTitle',
                    'defaultImage',
                    'titleSeperator',
                    'siteId',
                    'enableRobots',
                    'robots',
                    'sitemap',
                    'id'
                ],
                'safe',
            ],
        ];
    }
}