<?php

namespace studioespresso\seofields\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class RobotsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@studioespresso/seofields/assetbundles/dist";


        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];
        $this->js = [
            'js/robots_codemirror.min.js'
        ];

        $this->css = [
        ];

        parent::init();
    }
}