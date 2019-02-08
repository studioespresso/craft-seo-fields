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
        $this->sourcePath = "@studioespresso/seofields/assetbundles/robots/dist";
        $this->depends = [
            CpAsset::class,
        ];
        $this->js = [
            'js/codemirror.js',
            'js/twig.js',
        ];
        $this->css = [
            'css/robots.css',
        ];
        parent::init();
    }
}