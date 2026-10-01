<?php

namespace studioespresso\seofields\services;

use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class DefaultsService
{
    public function saveDefaults(SeoDefaultsModel $model, int $siteId): bool
    {
        $record = $this->getRecordForSiteId($siteId) ?? new DefaultsRecord(['siteId' => $siteId]);

        $record->fill([
            'defaultMeta' => $model->getMeta(),
            'enableRobots' => $model->enableRobots ?? true,
            'robots' => $model->robots,
            'schema' => $model->schema,
            'sitemap' => $model->sitemap,
        ]);

        return $record->save();
    }

    public function getDataById(int $id): ?SeoDefaultsModel
    {
        $record = DefaultsRecord::query()->find($id);

        return $record ? $this->toModel($record) : null;
    }

    public function getDataBySiteId(int $siteId): SeoDefaultsModel
    {
        $record = $this->getRecordForSiteId($siteId);

        return $record ? $this->toModel($record) : new SeoDefaultsModel(['siteId' => $siteId]);
    }

    public function getDataBySiteHandle(string $handle): SeoDefaultsModel
    {
        return $this->getDataBySiteId(Sites::getSiteByHandle($handle)->id);
    }

    public function getDataBySite(Site $site): SeoDefaultsModel
    {
        return $this->getDataBySiteId($site->id);
    }

    /**
     * Returns the robots.txt settings that apply to a site, or `false` when robots.txt is disabled.
     */
    public function getRobotsForSite(Site $site): SeoDefaultsModel|false
    {
        if (! SeoFields::getInstance()->getSettings()->robotsPerSite) {
            $site = Sites::getPrimarySite();
        }

        $record = $this->getRecordForSiteId($site->id);
        if (! $record || ! $record->enableRobots) {
            return false;
        }

        return new SeoDefaultsModel([
            'enableRobots' => $record->enableRobots,
            'robots' => $record->robots,
        ]);
    }

    public function getRecordForSiteId(int $siteId): ?DefaultsRecord
    {
        return DefaultsRecord::query()->where('siteId', $siteId)->first();
    }

    public function copyDefaultsForSite(Site $site, int $fromSiteId): void
    {
        $defaults = $this->getDataBySiteId($fromSiteId);
        $defaults->id = null;
        $defaults->siteId = $site->id;
        $this->saveDefaults($defaults, $site->id);
    }

    private function toModel(DefaultsRecord $record): SeoDefaultsModel
    {
        return new SeoDefaultsModel([
            ...($record->defaultMeta ?? []),
            'id' => $record->id,
            'siteId' => $record->siteId,
            'enableRobots' => $record->enableRobots,
            'robots' => $record->robots,
            'schema' => $record->schema,
            'sitemap' => $record->sitemap,
        ]);
    }
}
