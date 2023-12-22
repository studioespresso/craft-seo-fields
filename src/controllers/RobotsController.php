<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\SeoFields;

class RobotsController extends Controller
{
    protected array|bool|int $allowAnonymous = ['render'];

    public function actionIndex()
    {
        $siteHandle = $this->request->getRequiredQueryParam('site');
        $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        $sites = Craft::$app->getSites()->getEditableSites();
        $data = SeoFields::$plugin->defaultsService->getDataBySiteHandle($siteHandle);

        $settings = SeoFields::$plugin->getSettings();

        $crumbs = [
            [
                'label' => "Meta",
                'url' => UrlHelper::cpUrl('seo-fields'),
            ]
        ];

        if ($settings->robotsPerSite) {
            $crumbs[] = [
                'label' => $currentSite->name,
                'menu' => [
                    'label' => Craft::t('site', 'Select site'),
                    'items' => Cp::siteMenuItems($sites, $currentSite),
                ]
            ];
        }

        return $this->asCpScreen()
            ->title(Craft::t('seo-fields', 'Robots.txt'))
            ->crumbs($crumbs)
            ->action('seo-fields/robots/save')
            ->contentTemplate('seo-fields/_robots/_content', [
                'data' => $data,
                'site' => $currentSite,
                'robotsPerSite' => $settings->robotsPerSite,
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

    public function actionRender(): \yii\web\Response
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

    }
}
