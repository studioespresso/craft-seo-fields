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

class SitemapController extends Controller
{
    public $allowAnonymous = ['actionRender', 'actionDetail'];

    public function actionIndex()
    {
        $primarySite = Craft::$app->sites->getPrimarySite();
        $this->redirect("seo-fields/sitemap/$primarySite->handle");
    }

    public function actionSettings($siteHandle = null)
    {
        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($siteHandle);
        return $this->renderTemplate('seo-fields/_sitemap', [
            'data' => $data,
            'sitemapPerSite' => SeoFields::$plugin->getSettings()->sitemapPerSite
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
        $data['sitemap'] = Craft::$app->getRequest()->getBodyParam('data');
        $data['siteId'] = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
        $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, Craft::$app->sites->currentSite->id);
        SeoFields::$plugin->sitemapSerivce->clearCaches();
    }

    public function actionRender()
    {
        if (SeoFields::$plugin->getSettings()->sitemapPerSite) {
            $data = SeoFields::getInstance()->sitemapSerivce->shouldRenderBySiteId(Craft::$app->getSites()->getCurrentSite());
        } else {
            $data = SeoFields::getInstance()->sitemapSerivce->shouldRenderBySiteId(Craft::$app->getSites()->getPrimarySite());
        }
        if (!$data) {
            throw new NotFoundHttpException(Craft::t('app', 'Page not found'), 404);
        }

        $xml = SeoFields::$plugin->sitemapSerivce->getSitemapIndex($data);

        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $this->asRaw($xml);

    }

    public function actionDetail($siteId, $type, $sectionId, $handle)
    {
        $xml = SeoFields::$plugin->sitemapSerivce->getSitemapData($siteId, $type, $sectionId, $handle);
        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $this->asRaw($xml);
    }
}
