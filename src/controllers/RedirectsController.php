<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;

class RedirectsController extends Controller
{
    public function actionIndex()
    {
        $redirects = SeoFields::getInstance()->redirectService->getAllRedirects();
        return $this->renderTemplate('seo-fields/_redirect/_index', ['redirects' => $redirects]);
    }

    public function actionAdd()
    {
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'pattern' => Craft::$app->getRequest()->getParam('pattern') ?? null,
            'sites' => $this->getSitesMenu()
        ]);
    }

    public function actionEdit($id)
    {
        $redirect = SeoFields::getInstance()->redirectService->getRedirectById($id);
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'data' => $redirect,
            'sites' => $this->getSitesMenu()
        ]);
    }

    public function actionSave()
    {
        $id = Craft::$app->getRequest()->getBodyParam('redirectId');
        if ($id) {
            $model = SeoFields::getInstance()->redirectService->getRedirectById($id);
        } else {
            $model = new RedirectModel();
        }
        
        $model->setAttributes(Craft::$app->getRequest()->getBodyParam('fields'));

        if ($model->validate()) {
            $saved = SeoFields::getInstance()->redirectService->saveRedirect($model);
            if ($saved) {
                Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Redirect saved'));
                $this->redirectToPostedUrl();
            }
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save redirect.'));
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'data' => $model,
            'sites' => $this->getSitesMenu()
        ]);

    }

    public function actionDelete($id)
    {
        if (SeoFields::getInstance()->redirectService->deleteRedirectById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Redirect removed'));
            $this->redirect(UrlHelper::cpUrl('seo-fields/redirects'));
        }
    }

    private function getSitesMenu()
    {
        $sites = [
            0 => Craft::t('seo-fields', 'All Sites'),
        ];

        if (Craft::$app->getIsMultiSite()) {
            $editableSites = Craft::$app->getSites()->getEditableSiteIds();
            foreach (Craft::$app->getSites()->getAllGroups() as $group) {
                $groupSites = Craft::$app->getSites()->getSitesByGroupId($group->id);
                $sites[$group->name]
                    = ['optgroup' => $group->name];
                foreach ($groupSites as $groupSite) {
                    if (in_array($groupSite->id, $editableSites, false)) {
                        $sites[$groupSite->id] = $groupSite->name;
                    }
                }
            }
        }
        return $sites;
    }
}
