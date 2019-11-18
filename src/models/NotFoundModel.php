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
use craft\validators\DateTimeValidator;
use studioespresso\seofields\records\RedirectRecord;

/**
 * @author    Studio Espresso
 * @package   SEO Fields
 * @since     1.0.0
 */
class NotFoundModel extends Model
{
    // Public Properties
    // =========================================================================
    public $id;

    public $url;

    public $siteId;

    public $counter = 0;

    public $dateLastHit;

    public $handled = false;

    public $redirect = null;

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
    public function rules()
    {
        return [
            [
                ['counter', 'url', 'dateLastHit', 'handled', 'siteId'], 'required'
            ],
            [
                ['id', 'counter', 'url', 'dateLastHit', 'handled', 'siteId','redirect', 'dateLastHit', 'dateCreated', 'dateUpdated'], 'safe'
            ],
            [['counter', 'siteId'], 'integer'],
            ['handled', 'boolean'],
            ['dateLastHit', DateTimeValidator::class],
        ];
    }

    public function getRedirect()
    {
        if (!$this->redirect) {
            return false;
        } else {
            $redirect = RedirectRecord::find(['id' => $this->redirect])->one();
            return $redirect;
        }
    }
}
