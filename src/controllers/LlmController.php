<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Json;
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

        $crumbs = ['label' => $this->site->name];
        if (Craft::$app->getIsMultiSite()) {
            $crumbs['menu'] = [
                'label' => Craft::t('site', 'Select site'),
                'items' => Cp::siteMenuItems($sites, $this->site),
            ];
        }

        $llmData = [];
        if ($data->llm) {
            $llmData = is_array($data->llm) ? $data->llm : Json::decodeIfJson($data->llm) ?? [];
        }

        // Build sections with entry types and their text fields for the description fallback config
        $sectionsData = [];
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $siteSettings = $section->getSiteSettings();
            if (!isset($siteSettings[$this->site->id]) || !$siteSettings[$this->site->id]->hasUrls) {
                continue;
            }

            $entryTypes = [];
            foreach ($section->getEntryTypes() as $entryType) {
                $fields = [];
                foreach ($entryType->getFieldLayout()->getCustomFields() as $field) {
                    if (!($field instanceof \craft\fields\PlainText) && !($field instanceof \craft\ckeditor\Field)) {
                        continue;
                    }
                    $fields[] = [
                        'handle' => $field->handle,
                        'name' => $field->name,
                    ];
                }
                $entryTypes[] = [
                    'id' => $entryType->id,
                    'name' => $entryType->name,
                    'fields' => $fields,
                ];
            }

            $sectionsData[] = [
                'name' => $section->name,
                'entryTypes' => $entryTypes,
            ];
        }

        return $this->asCpScreen()
            ->selectedSubnavItem('llm')
            ->title(Craft::t('seo-fields', 'LLM.txt'))
            ->crumbs([$crumbs])
            ->action('seo-fields/llm/save')
            ->contentTemplate('seo-fields/_llm/_content', [
                'data' => $data,
                'llmData' => $llmData,
                'site' => $this->site,
                'sectionsData' => $sectionsData,
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
        $data['enableLlm'] = Craft::$app->getRequest()->getBodyParam('enableLlm');
        $data['llm'] = Json::encode([
            'title' => Craft::$app->getRequest()->getBodyParam('llmTitle'),
            'summary' => Craft::$app->getRequest()->getBodyParam('llmSummary'),
            'descriptionFields' => Craft::$app->getRequest()->getBodyParam('descriptionFields', []),
        ]);
        $data['siteId'] = Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getPrimarySite()->id);
        $model->setAttributes($data);
        SeoFields::$plugin->defaultsService->saveDefaults($model, Craft::$app->sites->currentSite->id);
        SeoFields::$plugin->llmService->clearCaches();
    }

    public function actionRender(): \yii\web\Response
    {
        $site = Craft::$app->getSites()->getCurrentSite();
        $llmModel = SeoFields::$plugin->defaultsService->getLlmForSite($site);

        if (!$llmModel) {
            throw new \yii\web\NotFoundHttpException();
        }
        $llmData = is_array($llmModel->llm) ? $llmModel->llm : Json::decodeIfJson($llmModel->llm) ?? [];

        $markdown = SeoFields::$plugin->llmService->generateMarkdown($site, $llmData);

        $headers = Craft::$app->response->headers;
        $headers->add('Content-Type', 'text/markdown; charset=utf-8');
        return $this->asRaw($markdown);
    }
}
