<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;

class RobotsController extends Controller
{
    protected array|bool|int $allowAnonymous = ['render'];

    public function actionIndex()
    {
        $primarySite = Craft::$app->sites->getPrimarySite();
        $this->redirect("seo-fields/robots/$primarySite->handle");
    }

    public function actionSettings($siteHandle = null)
    {
        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($siteHandle);
        $primarySite = Craft::$app->sites->getPrimarySite();

        return $this->renderTemplate('seo-fields/_robots', [
            'data' => $data,
            'robotsPerSite' => SeoFields::$plugin->getSettings()->robotsPerSite,
            'selectedSite' => $siteHandle ? Craft::$app->sites->getSiteByHandle($siteHandle) : $primarySite,
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
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
        $data['enableRobots'] = Craft::$app->getRequest()->getBodyParam('enableRobots');
        $data['robots'] = Craft::$app->getRequest()->getBodyParam('robots');
        $data['siteId'] = $siteId;
        $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, $siteId);
    }

    public function actionRender()
    {
        if (SeoFields::$plugin->getSettings()->robotsPerSite) {
            $robots = SeoFields::$plugin->defaultsService->getRobotsForSite(Craft::$app->getSites()->getCurrentSite());
        } else {
            $robots = SeoFields::$plugin->defaultsService->getRobotsForSite(Craft::$app->getSites()->getPrimarySite());
        }
        try {
            $string = Craft::$app->getView()->renderString(Template::raw($robots->robots));
            $headers = Craft::$app->response->headers;
            $headers->add('Content-Type', 'text/plain; charset=utf-8');
            return $this->asRaw($string);
        } catch (\Exception $e) {
        }
        return;
    }
}
