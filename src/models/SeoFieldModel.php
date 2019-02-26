<?php

namespace studioespresso\seofields\models;

use craft\base\Model;

class SeoFieldModel extends Model
{
    public $metaTitle;
    public $metaDescription;


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
                ],
                'safe',
            ],
        ];
    }
}