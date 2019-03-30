<?php

namespace studioespresso\seofields\services;

use craft\base\Model;
use craft\helpers\Json;
use craft\models\Site;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

use Craft;
use craft\base\Component;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class DefaultsService extends Component
{
    // Public Methods
    // =========================================================================
    public function saveDefaults(SeoDefaultsModel $model, $siteId)
    {
        $record = DefaultsRecord::findOne(
            ['siteId' => $siteId]
        );
        if (!$record) {
            $record = new DefaultsRecord();
        }
        $record->setAttribute('defaultMeta', $model->toArray(['defaultSiteTitle', 'titleSeperator', 'defaultImage']));
        $record->setAttribute('siteId', $model->siteId);
        $record->setAttribute('enableRobots', $model->enableRobots);
        $record->setAttribute('robots', $model->robots);
        $record->setAttribute('sitemap', $model->sitemap);

        if ($record->validate()) {
            $record->save();
            return true;
        }
    }

    public function getDataById($id)
    {
        $record = DefaultsRecord::findOne(
            ['id' => $id]
        );
        if ($record) {
            $fields = array_merge(
                Json::decode($record->getAttribute("defaultMeta")) ?? [],
                $record->toArray()
            );
            $model = new SeoDefaultsModel();
            $model->setAttributes($fields);
            return $model;
        }
    }

    public function getDataBySiteId($siteId)
    {
        $record = $this->getRecordForSiteId($siteId);
        if ($record) {
            $model = new SeoDefaultsModel();
            $fields = array_merge(
                Json::decode($record->getAttribute("defaultMeta")) ?? [],
                [
                    'id' => $record->id,
                    'enableRobots' => $record->enableRobots,
                    'robots' => $record->robots,
                    'sitemap' => $record->sitemap
                ]);
            $model->setAttributes($fields);
            return $model;
        } else {
            return new SeoDefaultsModel();
        }
    }

    public function getDataBySiteHandle($handle) {
        $site = Craft::$app->sites->getSiteByHandle($handle);
        return $this->getDataBySiteId($site->id);
    }

    public function getDataBySite(Site $site)
    {
        return $this->getDataBySiteId($site->id);
    }

    public function getRobotsForSite(Site $site)
    {
        if (!SeoFields::$plugin->getSettings()->robotsPerSite) {
            $site = Craft::$app->getSites()->getPrimarySite();
        }

        $record = $this->getRecordForSiteId($site->id);
        if ($record && !$record->enableRobots) {
            return false;
        } else {
            $model = new SeoDefaultsModel();
            $fields = [
                'enableRobots' => $record->enableRobots,
                'robots' => $record->robots
            ];
            $model->setAttributes($fields);
            return $model;
        }
    }

    public function getRecordForSiteId($siteId)
    {
        $record = DefaultsRecord::findOne(
            ['siteId' => $siteId]
        );
        return $record;
    }

    public function copyDefaultsForSite(Site $site, $oldPrimarySiteId)
    {
      $defaults = $this->getDataBySiteId($oldPrimarySiteId);
      $defaults->siteId = $site->id;
      $this->saveDefaults($defaults, $site->id);
      return true;
    }
}
