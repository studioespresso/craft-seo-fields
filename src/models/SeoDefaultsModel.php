<?php

namespace studioespresso\seofields\models;

use CraftCms\Cms\Support\Json;

/**
 * Per-site defaults: meta, robots.txt, sitemap and schema settings.
 */
class SeoDefaultsModel
{
    /** The keys stored together in the `defaultMeta` JSON column */
    public const META_KEYS = [
        'defaultSiteTitle',
        'defaultMetaDescription',
        'titleSeperator',
        'defaultImage',
        'organizationName',
        'organizationLogo',
        'sameAs',
        'siteEntity',
    ];

    public ?int $id = null;

    public ?string $defaultSiteTitle = null;

    public ?string $defaultMetaDescription = null;

    /** @var int[]|null */
    public ?array $defaultImage = null;

    public ?string $titleSeperator = null;

    public ?int $siteId = null;

    public ?bool $enableRobots = null;

    public ?string $robots = null;

    /** @var string|null JSON */
    public ?string $schema = null;

    /** @var string|null JSON */
    public ?string $sitemap = null;

    public ?string $organizationName = null;

    /** @var int[]|null */
    public ?array $organizationLogo = null;

    /** @var string[]|null */
    public ?array $sameAs = null;

    public ?string $siteEntity = null;

    public function __construct(array $attributes = [])
    {
        $this->setAttributes($attributes);
    }

    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $name => $value) {
            if (! property_exists($this, $name)) {
                continue;
            }
            $this->$name = match ($name) {
                'id', 'siteId' => $value === null || $value === '' ? null : (int) $value,
                'enableRobots' => $value === null ? null : (bool) $value,
                'defaultImage', 'organizationLogo' => $this->normalizeIds($value),
                'sameAs' => is_array($value) ? array_values($value) : null,
                'schema', 'sitemap' => is_array($value) ? Json::encode($value) : ($value === '' ? null : $value),
                default => $value === '' ? null : $value,
            };
        }
    }

    public function getSchema(): ?array
    {
        return Json::decodeIfJson($this->schema) ?: null;
    }

    public function getSitemap(): ?array
    {
        return Json::decodeIfJson($this->sitemap) ?: null;
    }

    /** @return array<string, mixed> */
    public function getMeta(): array
    {
        return array_intersect_key(get_object_vars($this), array_flip(self::META_KEYS));
    }

    /** @return int[]|null */
    private function normalizeIds(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return array_values(array_map('intval', (array) $value));
    }
}
