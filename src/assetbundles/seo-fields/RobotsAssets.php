<?php

namespace studioespresso\seofields\assetbundles\robots;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class RobotsAssets extends AssetBundle
{
    // Public Methods
    // =========================================================================
    /**
     * Initializes the bundle.
     */
    public function init()
    {
        $this->sourcePath = "@studioespresso/seofields/assetbundles/seofields/dist";
        $this->depends = [
            CpAsset::class,
        ];
        $this->js = [
            'js/robots_codemirror.js',
        ];
        $this->css = [
            'css/robots.css',
        ];
        parent::init();
    }
}