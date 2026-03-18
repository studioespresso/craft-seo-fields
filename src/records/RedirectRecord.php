<?php

namespace studioespresso\seofields\records;

use craft\db\ActiveRecord;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 * @property string $dateLastHit
 * @property string $pattern
 * @property string $redirect
 * @property int $method
 * @property int|null $siteId
 * @property int $counter
 */
class RedirectRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================
    public static function tableName()
    {
        return '{{%seofields_redirects}}';
    }
}
