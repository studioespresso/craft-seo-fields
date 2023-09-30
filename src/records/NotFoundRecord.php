<?php

namespace studioespresso\seofields\records;

use craft\db\ActiveRecord;

/***
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class NotFoundRecord extends ActiveRecord
{

    public $id;
    public $dateLastHit;
    public $urlPath;
    public $matchType;
    public $siteId;
    public $counter;
    public $handled;

    // Public Static Methods
    // =========================================================================
    public static function tableName()
    {
        return '{{%seofields_404}}';
    }
}
