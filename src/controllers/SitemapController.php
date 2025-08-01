<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\db\Query;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\models\Site;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;
use yii\web\NotFoundHttpException;

class SitemapController extends Controller
{
    protected array|bool|int $allowAnonymous = ['render', 'detail'];

    public Site|null $site = null;

    public function init(): void
    {
        if (Craft::$app->getRequest()->getQueryParam('site')) {
            $this->site = Craft::$app->getSites()->getSiteByHandle(Craft::$app->getRequest()->getQueryParam('site'));
        } else {
            $this->site = Craft::$app->getSites()->getPrimarySite();
        }
        parent::init();
    }

    public function actionIndex()
    {
        $sites = Craft::$app->getSites()->getEditableSites();
        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($this->site->handle);
        $settings = SeoFields::$plugin->getSettings();

        $query = new Query();

        $query->select('sectionId as id')
            ->from('{{%sections_sites}} as ss')
            ->leftJoin('{{%sections}} as s', 's.id = ss.sectionId')
            ->where(Db::parseParam('siteId', $this->site->id))
            ->andWhere(['s.dateDeleted' => null]);

        $sections = [];
        foreach ($query->all() as $s) {
            $sections[] = Craft::$app->getEntries()->getSectionById($s['id']);
        }

        $crumbs = ['label' => $this->site->name,];
        if (Craft::$app->getIsMultiSite() && $settings->sitemapPerSite) {
            $crumbs['menu'] = [
                'label' => Craft::t('site', 'Select site'),
                'items' => Cp::siteMenuItems($sites, $this->site),
            ];
        }

        return $this->asCpScreen()
            ->title(Craft::t('seo-fields', 'Sitemap.xml'))
            ->selectedSubnavItem('sitemap')
            ->crumbs([$crumbs])
            ->action('seo-fields/sitemap/save')
            ->additionalButtonsTemplate('seo-fields/_sitemap/_buttons', [
                'site' => $this->site,
            ])
            ->contentTemplate('seo-fields/_sitemap/_content', [
                'data' => $data,
                'sitemapPerSite' => $settings->sitemapPerSite,
                'sections' => $sections,
                'site' => $this->site,
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
        SeoFields::$plugin->sitemapService->clearCaches();
    }

    public function actionRender()
    {
        $data = SeoFields::getInstance()->sitemapService->shouldRenderBySiteId(Craft::$app->getSites()->getCurrentSite());
        // keeping this here to trigger the decrepation error is the user has that set
        SeoFields::$plugin->getSettings()->getSitemapPerSite() ;
        
        if (!$data) {
            throw new NotFoundHttpException(Craft::t('app', 'Page not found'), 404);
        }

        $xml = SeoFields::$plugin->sitemapService->getSitemapIndex(array_filter($data));

        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $this->asRaw($xml);
    }

    public function actionDetail($siteId, $type, $sectionId, $handle)
    {
        $xml = SeoFields::$plugin->sitemapService->getSitemapData($siteId, $type, $sectionId);
        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/xml; charset=utf-8');
        $this->asRaw($xml);
    }
}
