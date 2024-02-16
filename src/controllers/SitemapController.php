<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;
use yii\web\NotFoundHttpException;

class SitemapController extends Controller
{
    protected array|bool|int $allowAnonymous = ['render', 'detail'];

    public function actionIndex()
    {
        $primarySite = Craft::$app->getSites()->getPrimarySite();
        $this->redirect("seo-fields/sitemap/$primarySite->handle");
    }

    public function actionSettings($siteHandle = null)
    {
        $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        Craft::$app->getSites()->getSiteByHandle($site);
        $query = new Query();
        $query->select('sectionId as id')
            ->from('{{%sections_sites}}')
            ->leftJoin('{{%sections}}', 'sections.id = sections_sites.sectionId')
            ->where(Db::parseParam('siteId', $site->id))
            ->andWhere(['sections.dateDeleted' => null]);
        $sections = [];
        foreach ($query->all() as $s) {
            $sections[] = Craft::$app->getSections()->getSectionById($s['id']);
        }

        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($siteHandle);
        return $this->renderTemplate('seo-fields/_sitemap', [
            'data' => $data,
            'sitemapPerSite' => SeoFields::$plugin->getSettings()->sitemapPerSite,
            'sections' => $sections,
            'selectedSite' => $site,
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
        SeoFields::$plugin->defaultsService->saveDefaults($model, $data['siteId']);
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

        $xml = SeoFields::$plugin->sitemapSerivce->getSitemapIndex(array_filter($data));

        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $this->asRaw($xml);
    }

    public function actionDetail($siteId, $type, $sectionId, $handle)
    {
        $xml = SeoFields::$plugin->sitemapSerivce->getSitemapData($siteId, $type, $sectionId);
        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $this->asRaw($xml);
    }
}
