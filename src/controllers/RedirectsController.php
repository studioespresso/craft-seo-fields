<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;

class RedirectsController extends Controller
{
    public function actionIndex()
    {
        $redirects = SeoFields::getInstance()->redirectService->getAllRedirects();
        return $this->renderTemplate('seo-fields/_redirect/_index', ['redirects' => $redirects]);
    }

    public function actionAdd()
    {
        return $this->renderTemplate('seo-fields/_redirect/_add', ['pattern' => Craft::$app->getRequest()->getParam('pattern') ?? null]);
    }

    public function actionSave()
    {

    }
}
