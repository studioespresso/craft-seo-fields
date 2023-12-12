<?php

namespace studioespresso\seofields\models;

use craft\base\Model;

class SeoDefaultsModel extends Model
{
    public $id;

    public $defaultSiteTitle;

    public $defaultMetaDescription;

    public $defaultImage;

    public $titleSeperator;

    public $siteId;

    public $enableRobots;

    public $robots;

    public $schema;

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
                    'defaultMetaDescription',
                    'defaultImage',
                    'titleSeperator',
                    'siteId',
                    'enableRobots',
                    'robots',
                    'schema',
                    'sitemap',
                    'id',
                ],
                'safe',
            ],
        ];
    }
}
