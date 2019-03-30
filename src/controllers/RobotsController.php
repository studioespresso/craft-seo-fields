<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;

class RobotsController extends Controller
{
    public $allowAnonymous = ['actionRender'];

    public function actionIndex()
    {
        $primarySite = Craft::$app->sites->getPrimarySite();
        $this->redirect("seo-fields/robots/$primarySite->handle");
    }

    public function actionSettings($siteHandle = null)
    {
        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($siteHandle);
        return $this->renderTemplate('seo-fields/_robots', [
            'data' => $data,
            'robotsPerSite' => SeoFields::$plugin->getSettings()->robotsPerSite
        ]);
    }

    public function actionSave()
    {
        $data = [];
        if (Craft::$app->getRequest()->getBodyParam('id')) {
            $model = SeoFields::$plugin->defaultsService->getDataById(Craft::$app->getRequest()->getBodyParam('id'));
        } else {
            $model = new SeoDefaultsModel();
        }
        $data['enableRobots'] = Craft::$app->getRequest()->getBodyParam('enableRobots');
        $data['robots'] = Craft::$app->getRequest()->getBodyParam('robots');
        $data['siteId'] = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
         $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, Craft::$app->sites->currentSite->id);
    }

    public function actionRender()
    {
        if(SeoFields::$plugin->getSettings()->robotsPerSite) {
            $robots = SeoFields::$plugin->defaultsService->getRobotsForSite(Craft::$app->getSites()->getCurrentSite());
        } else {
            $robots = SeoFields::$plugin->defaultsService->getRobotsForSite(Craft::$app->getSites()->getPrimarySite());
        }
        try {
            $string = Craft::$app->getView()->renderString(Template::raw($robots->robots));
            $headers = Craft::$app->response->headers;
            $headers->add('Content-Type', 'text/plain; charset=utf-8');
            return $this->asRaw($string);
        } catch(\Exception $e) {

        }
        return;
    }
}
