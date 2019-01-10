<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\web\Controller;

class DefaultController extends Controller
{
    public function actionIndex()
    {
        $this->redirect('seo-fields/defaults');
    }

    public function actionDefaults()
    {
        return $this->renderTemplate('seo-fields/_defaults');

    }
}
