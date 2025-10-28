<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use craft\web\Response;
use studioespresso\seofields\SeoFields;

class NotFoundController extends Controller
{
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

    public function actionIndex(): Response
    {
        $sites = Craft::$app->getSites()->getEditableSites();

        $crumbs = ['label' => $this->site->name, ];
        if (Craft::$app->getIsMultiSite()) {
            $crumbs['menu'] = [
                'label' => Craft::t('site', 'Select site'),
                'items' => Cp::siteMenuItems($sites, $this->site),
            ];
        }

        $viewOptions = [
            ['value' => 'all', 'label' => Craft::t('seo-fields', 'Show all 404\'s')],
            ['value' => 'unhandled', 'label' => Craft::t('seo-fields', 'Items without a redirect')],
            ['value' => 'handled', 'label' => Craft::t('seo-fields', 'Items with a redirect')],
        ];

        return $this->asCpScreen()
            ->title(Craft::t('seo-fields', '404 Overview'))
            ->selectedSubnavItem('notfound')
            ->additionalButtonsTemplate('seo-fields/_notfound/_buttons', ['viewOptions' => $viewOptions])
            ->crumbs([$crumbs])
            ->contentTemplate('seo-fields/_notfound/_content');
    }

    /**
     * @param $id
     * @throws \craft\errors\MissingComponentException
     */
    public function actionDelete()
    {
        $id = $this->request->getBodyParam('id');
        if (SeoFields::getInstance()->notFoundService->deletetById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('seo-fields', '404 removed'));
            return $this->asJson(['success' => true]);
        }
    }

    /**
     * @return \yii\web\Response
     * @todo Should this also take the current site into account?
     */
    public function actionClearAll()
    {
        SeoFields::getInstance()->notFoundService->deleteAll();
        return $this->redirect(UrlHelper::cpUrl('seo-fields/not-found'));
    }
}
