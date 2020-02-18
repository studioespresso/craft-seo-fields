<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;

class NotFoundController extends Controller
{
    public function actionIndex($siteHandle = null)
    {
        if($siteHandle) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
            Craft::$app->getSites()->setCurrentSite($site);
        }
        $data = SeoFields::getInstance()->notFoundService->getAllNotFound('counter', $siteHandle);
        return $this->renderTemplate('seo-fields/_notfound/_index', ['data' => $data]);
    }

    public function actionDelete($id)
    {
        if (SeoFields::getInstance()->notFoundService->deletetById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('seo-fields', '404 removed'));
            $this->redirect(UrlHelper::cpUrl('seo-fields/not-found'));
        }
    }

}
