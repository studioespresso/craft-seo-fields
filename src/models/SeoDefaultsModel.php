<?php

namespace studioespresso\seofields\models;

use craft\base\Model;

class SeoDefaultsModel extends Model
{
    public $defaultSiteTitle;

    public $titleSeperator;

    public $siteId;

    public $enableRobots;

    public $robots;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [
                [
                    'defaultSiteTitle',
                    'titleSeperator',
                    'siteId',
                    'enableRobots',
                    'robots'
                ],
                'safe',
            ],
        ];
    }
}