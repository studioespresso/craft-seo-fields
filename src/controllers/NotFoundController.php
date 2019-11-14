<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;

class NotFoundController extends Controller
{
    public function actionIndex()
    {
        $data = SeoFields::getInstance()->notFoundService->getAllNotFound('dateLastHit');
        return $this->renderTemplate('seo-fields/_notfound/_index', ['data' => $data]);
    }
}
