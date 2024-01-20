<?php
/**
 * SEO Fields config.php
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'seo-fields.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    "pluginLabel" => "SEO",
    "titleSeperator" => "-",
    "robotsPerSite" => false,
    "sitemapPerSite" => false,
    "fieldHandle" => "seo",
    "notFoundLimit" => 10000,
    'schemaOptions' => []
];
