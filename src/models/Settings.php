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

use Craft;
use craft\base\Model;

/**
 * @author    Studio Espresso
 * @package   SEO Fields
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================
    public $pluginLabel = 'SEO';

    public $titleSeperator = '-';

    public $robotsPerSite = false;

    /**
     * @var bool
     * @deprecated Since x.x this was turned on my default and the setting will be remove on the next major release.
     */
    public $sitemapPerSite = false;

    public $fieldHandle = 'seo';

    public $createRedirectForUriChange = true;

    public $schemaOptions = [];

    public $notFoundLimit = 10000;

    public $logicallySeperatedSiteGroups = false;


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
            ['titleSeperator', 'string'],
        ];
    }

    public function getSitemapPerSite()
    {
        if ($this->sitemapPerSite) {
            Craft::$app->getDeprecator()->log(
                'studioespresso\seofields\models\Settings::getSitemapPerSite',
                'The settings getSitemapPerSite is deprecated and now defaults to true. It will be removed in the next major release.'
            );
        }
        return true;
    }
}
