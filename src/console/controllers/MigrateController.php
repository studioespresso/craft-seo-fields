<?php

namespace studioespresso\seofields\console\controllers;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\Db;
use studioespresso\seofields\jobs\MigrateFieldDataJob;
use studioespresso\seofields\services\migrate\Ether;
use yii\console\Controller;

class MigrateController extends Controller
{
    public $newHandle = 'newSeo';
    public $oldHandle = 'seo';
    public $siteId;
    public $titleSeperator;
    public $fieldHandle = 'seo';
    public $metaTitle;
    public $metaDescription;

    public function options($actionId): array
    {
        switch ($actionId) {
            case 'ether':
                return ['oldHandle', 'newHandle', 'siteId', 'titleSeperator'];
            case 'fields':
                return ['fieldHandle', 'metaTitle', 'metaDescription'];
        }
        return [];
    }

    public function actionEther()
    {
        if (!Craft::$app->getPlugins()->isPluginInstalled('seo')) {
            $this->stdout("ether/seo not installed." . PHP_EOL, Console::FG_YELLOW);
            $this->stdout(PHP_EOL);
        }

        if (!version_compare(Craft::$app->getPlugins()->getPlugin('seo')->getVersion(), '3.6.0', '>=')) {
            $this->stdout("Migrating content from ether/seo to SEO Fields requires version 3.6.0 or higher for ether/seo" . PHP_EOL, Console::FG_YELLOW);
        }

        $etherMigration = new Ether();
        $etherMigration->migrate($this->oldHandle = 'seo', $this->newHandle = 'newSeo', $this->siteId, $this->titleSeperator);
    }

    public function actionFields()
    {
        $field = Craft::$app->getFields()->getFieldByHandle($this->fieldHandle);

        $query = new Query();
        $query->select('types.sectionId as sectionId');
        $query->addSelect('types.id as typeId');
        $query->from('{{%fieldlayoutfields}} layout');
        $query->where(Db::parseParam('fieldId', $field->id));
        $query->leftJoin('{{%entrytypes}} types', 'layout.layoutId = types.fieldLayoutId');

        App::maxPowerCaptain();
        foreach ($query->all() as $data) {
            $section = Craft::$app->getSections()->getSectionById($data['sectionId']);
            $type = Craft::$app->getSections()->getEntryTypeById($data['typeId']);
            $entries = Entry::findAll(['sectionId' => $data['sectionId'], 'typeId' => $data['typeId']]);
            $this->stdout("Processing entries in {$section->name} ($type->name)" . PHP_EOL, Console::FG_GREEN);
            foreach ($entries as $entry) {
                Craft::$app->getQueue()->push(new MigrateFieldDataJob([
                    'entryId' => $entry->id,
                    'fieldHandle' => $this->fieldHandle,
                    'metaTitle' => $this->metaTitle,
                    'metaDescription' => $this->metaDescription,
                ]));
            };
        }
    }
}
