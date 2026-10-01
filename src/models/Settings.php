<?php

/**
 * SEO Fields plugin for Craft CMS
 *
 * Fields for your SEO & OG data
 *
 * @link      https://studioespresso.co
 *
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\seofields\models;

use CraftCms\Cms\Plugin\PluginSettings;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class Settings extends PluginSettings
{
    public string $pluginLabel = 'SEO';

    public string $titleSeperator = '-';

    public bool $robotsPerSite = false;

    public string $fieldHandle = 'seo';

    public bool $createRedirectForUriChange = true;

    /** @var array<class-string, string> Extra Schema.org page types, keyed by class */
    public array $schemaOptions = [];

    /** @var array<class-string, string> Extra Schema.org site entity types, keyed by class */
    public array $siteEntityOptions = [];

    public ?int $notFoundLimit = 10000;

    public bool $logicallySeperatedSiteGroups = false;

    public function getRules(): array
    {
        return [
            'pluginLabel' => ['nullable', 'string'],
            'titleSeperator' => ['nullable', 'string'],
            'createRedirectForUriChange' => ['boolean'],
        ];
    }
}
