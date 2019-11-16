<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
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
        return $this->renderTemplate('seo-fields/_redirect/_add', ['pattern' => Craft::$app->getRequest()->getParam('pattern') ?? null]);
    }

    public function actionSave()
    {
        $id = Craft::$app->getRequest()->getBodyParam('redirectId');
        if ($id) {
            $model = SeoFields::getInstance()->redirectService->getRedirectById();
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
        return $this->renderTemplate('seo-fields/_redirect/_add', [
            'data' => $model
        ]);

    }

    public function actionDelete($id)
    {
        if(SeoFields::getInstance()->redirectService->deleteRedirectById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Redirect removed'));
            $this->redirect(UrlHelper::cpUrl('seo-fields/redirects'));
        }
    }
}
