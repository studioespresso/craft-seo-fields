<?php

namespace studioespresso\seofields\services;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Support\Facades\DB;
use studioespresso\seofields\SeoFields;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class SitemapService
{
    public const SITEMAP_CACHE_KEY = 'seofields_cache_sitemaps';

    /**
     * Returns the sitemap settings of the sections that should be listed for the site,
     * falling back to the primary site's settings, or `false` when there's nothing to render.
     *
     * @return array<int, array{changefreq?: string, priority?: string, enabled?: mixed}>|false
     */
    public function getSectionsToRender(Site $site): array|false
    {
        $settings = SeoFields::getInstance()->defaultsService->getDataBySite($site)->getSitemap()
            ?? SeoFields::getInstance()->defaultsService->getDataBySite(Sites::getPrimarySite())->getSitemap();

        $sections = array_filter($settings['entry'] ?? [], function (array $sectionSettings, int|string $sectionId) use ($site) {
            if (empty($sectionSettings['enabled'])) {
                return false;
            }
            $section = Sections::getSectionById((int) $sectionId);

            return (bool) ($section?->getSiteSettings()[$site->id]?->hasUrls ?? false);
        }, ARRAY_FILTER_USE_BOTH);

        return $sections ?: false;
    }

    public function getSitemapIndex(array $sections): string
    {
        $site = Sites::getCurrentSite();
        $key = self::SITEMAP_CACHE_KEY.'_index_site'.$site->id;

        return $this->cached($key, [self::SITEMAP_CACHE_KEY, $key], function () use ($sections, $site) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach (array_keys($sections) as $sectionId) {
                $section = Sections::getSectionById($sectionId);
                $latest = Entry::find()->sectionId($sectionId)->siteId($site->id)->orderBy('dateUpdated', 'desc')->first();
                if ($section && $latest) {
                    $loc = Url::siteUrl("sitemap_{$site->id}_entry_{$section->id}_".strtolower($section->handle).'.xml', siteId: $site->id);
                    $xml .= '<sitemap><loc>'.e($loc).'</loc><lastmod>'.$latest->dateUpdated->format('Y-m-d').'</lastmod></sitemap>';
                }
            }

            return $xml.'</sitemapindex>';
        });
    }

    public function getSitemapData(int $siteId, int $sectionId): ?string
    {
        $settings = $this->getSectionsToRender(Sites::getSiteById($siteId))[$sectionId] ?? null;
        if (! $settings) {
            return null;
        }

        $key = self::SITEMAP_CACHE_KEY.'_'.$siteId.'_'.$sectionId;

        return $this->cached($key, [self::SITEMAP_CACHE_KEY, $key], function () use ($siteId, $sectionId, $settings) {
            $entries = Entry::find()->siteId($siteId)->sectionId($sectionId)->orderBy('dateUpdated', 'desc')->get();

            return $this->renderUrlset($entries, $settings, $siteId);
        });
    }

    public function clearCaches(array|string $tags = self::SITEMAP_CACHE_KEY): void
    {
        TagDependency::invalidate($tags);
    }

    public function clearCacheForElement(ElementInterface $element): void
    {
        if (! $element instanceof Entry || ! $element->sectionId || ElementHelper::isDraftOrRevision($element)) {
            return;
        }

        $this->clearCaches([
            self::SITEMAP_CACHE_KEY.'_index_site'.$element->siteId,
            self::SITEMAP_CACHE_KEY.'_'.$element->siteId.'_'.$element->sectionId,
        ]);
    }

    /** Sitemaps are cached until invalidated, except in dev mode */
    private function cached(string $key, array $tags, \Closure $callback): string
    {
        if (Cms::config()->devMode) {
            return $callback();
        }

        return DependencyCache::rememberForever($key, $callback, new TagDependency($tags));
    }

    private function renderUrlset(iterable $entries, array $settings, int $siteId): string
    {
        $fieldHandle = SeoFields::getInstance()->getSettings()->fieldHandle;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xhtml="http://www.w3.org/1999/xhtml" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($entries as $entry) {
            $url = $entry->getUrl();
            if (! $url || ($entry->getFieldValue($fieldHandle)?->allowIndexing ?? 'yes') === 'no') {
                continue;
            }

            $xml .= '<url>';
            $xml .= '<loc>'.e(Url::encodeUrl($url)).'</loc>';
            $xml .= '<lastmod>'.$entry->dateUpdated->format('Y-m-d').'</lastmod>';
            $xml .= '<changefreq>'.e($settings['changefreq'] ?? 'weekly').'</changefreq>';
            $xml .= '<priority>'.e($settings['priority'] ?? '0.5').'</priority>';
            foreach ($this->alternates($entry->id, $siteId) as $alternate) {
                $href = Url::siteUrl($alternate->uri === '__home__' ? '' : $alternate->uri, siteId: $alternate->siteId);
                $xml .= '<xhtml:link rel="alternate" hreflang="'.e($alternate->language).'" href="'.e($href).'"/>';
            }
            $xml .= '</url>';
        }

        return $xml.'</urlset>';
    }

    /** The element's URIs in the other enabled sites */
    private function alternates(int $elementId, int $siteId): iterable
    {
        return DB::table(Table::ELEMENTS_SITES.' as es')
            ->join(Table::SITES.' as s', 's.id', '=', 'es.siteId')
            ->where('es.elementId', $elementId)
            ->where('es.siteId', '!=', $siteId)
            ->whereNotNull('es.uri')
            ->where('s.enabled', true)
            ->whereNull('s.dateDeleted')
            ->select(['es.siteId', 'es.uri', 's.language'])
            ->get();
    }
}
