# Robots.txt
### Default
When you install the plugin, it enabled the robots.txt by default, using [this template](src/templates/_placeholder/_robots.twig).

Based on the environment you've set in your site's ``.env`` file, it will only allow indexing on `live` or `production` environments and block indexing on all others.

You can modify the template with your own enviroments, conditions and settings in the CP.

### Multisite

By default, each site in your Craft install will get the same `robots.txt`. If you need the option to change these per site, you can add `"robotsPerSite" => true` to `config/seo-fields.php`.

With that set, refresh the robots settings page and you'll see a sites dropdown at the top so you can switch sites and save a sitemap for each.