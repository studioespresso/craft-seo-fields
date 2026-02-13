<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Template;
use craft\models\Site;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;

class LlmController extends Controller
{
    protected array|bool|int $allowAnonymous = ['render'];

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

        $sites = Craft::$app->getSites()->getEditableSites();

        $crumbs = ['label' => $this->site->name, ];
        if (Craft::$app->getIsMultiSite() && $settings->robotsPerSite) {
            $crumbs['menu'] = [
                'label' => Craft::t('site', 'Select site'),
                'items' => Cp::siteMenuItems($sites, $this->site),
            ];
        }

        return $this->asCpScreen()
            ->selectedSubnavItem('llm')

            ->title(Craft::t('seo-fields', 'LLM.txt'))
            ->crumbs([$crumbs])
            ->action('seo-fields/llm/save')
            ->contentTemplate('seo-fields/_llm/_content', [
                'data' => $data,
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
        $data['enableRobots'] = Craft::$app->getRequest()->getBodyParam('enableRobots');
        $data['robots'] = Craft::$app->getRequest()->getBodyParam('robots');
        $data['siteId'] = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
        $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, Craft::$app->sites->currentSite->id);
    }

    public function actionRender(): \yii\web\Response|null
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
        return null;
    }
}
