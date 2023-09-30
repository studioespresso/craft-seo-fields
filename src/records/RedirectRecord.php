<?php

namespace studioespresso\seofields\records;

use craft\db\ActiveRecord;

/***
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class RedirectRecord extends ActiveRecord
{

    public $id;
    public $dateLastHit;
    public $counter;
    public $matchType;
    public $method;
    public $siteId;
    public $redirect;
    public $pattern;

    // Public Static Methods
    // =========================================================================
    public static function tableName()
    {
        return '{{%seofields_redirects}}';
    }
}
