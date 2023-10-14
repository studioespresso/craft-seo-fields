<?php
/**
 * SEO Fields plugin for Craft CMS 3.x
 *
 * Fields for your SEO & OG data
 *
 * @link      https://studioespresso.co
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\seofields\models;

use craft\base\Model;

/**
 * @author    Studio Espresso
 * @package   SEO Fields
 * @since     1.0.0
 */
class RedirectModel extends Model
{
    // Public Properties
    // =========================================================================
    public $id;

    public $pattern;

    public $sourceMatch;

    public $redirect;

    public $matchType;

    public $method = 301;

    public $siteId;

    public $counter = 0;

    public $dateLastHit;

    public $dateCreated;

    public $dateUpdated;

    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            [['pattern', 'redirect', 'method'], 'required'],
            [
                ['id', 'pattern', 'sourceMatch', 'redirect', 'matchType', 'method', 'siteId', 'counter', 'dateLastHit', 'dateLastHit', 'dateCreated', 'dateUpdated'], 'safe',
            ],
        ];
    }
}
