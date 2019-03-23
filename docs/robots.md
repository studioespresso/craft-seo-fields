# Robots.txt
When you install the plugin, it enabled the robots.txt by default with [this template](src/templates/_placeholder/_robots.twig). Based on the environment you've set in your site's ``.env`` file, it will only allow indexing on `live` or `production` environments and block indexing on all others.

You can modify the template with your own enviroments, conditions and settings in the CP.
