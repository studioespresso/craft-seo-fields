<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

class RobotsController extends Controller
{

    public function actionIndex()
    {
        $primarySite = Craft::$app->sites->getPrimarySite();
        $this->redirect("seo-fields/robots/$primarySite->handle");
    }

    public function actionRobots($siteHandle = null)
    {
        $currentSite = Craft::$app->sites->getSiteByHandle($siteHandle);
        Craft::$app->sites->setCurrentSite($currentSite);
        $data = SeoFields::$plugin->defaultsService->getDataBySite($currentSite);
        return $this->renderTemplate('seo-fields/_robots', [
            'sites' => Craft::$app->sites->getEditableSites(),
            'data' => $data
        ]);
    }

    public function actionSave()
    {
        $data = [];
        if(Craft::$app->getRequest()->getBodyParam('id')) {
            $model = SeoFields::$plugin->defaultsService->getDataById(Craft::$app->getRequest()->getBodyParam('id'));
        } else {
            $model = new SeoDefaultsModel();
        }
        $data['enableRobots'] = Craft::$app->getRequest()->getBodyParam('enableRobots');
        $data['robots'] = Craft::$app->getRequest()->getBodyParam('robots');
        $data['siteId'] = Craft::$app->sites->currentSite->id;
        $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, Craft::$app->sites->currentSite->id);
    }
}
