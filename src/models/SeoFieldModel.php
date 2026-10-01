<?php

namespace studioespresso\seofields\models;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\WebPage;
use studioespresso\seofields\behaviors\ElementSeoMacros;
use studioespresso\seofields\SeoFields;
use Throwable;

/**
 * The value of an SEO field.
 */
class SeoFieldModel
{
    /** The keys that are stored in the field's content */
    public const ATTRIBUTES = [
        'metaTitle',
        'metaDescription',
        'siteName',
        'hideSiteName',
        'facebookTitle',
        'facebookDescription',
        'facebookImage',
        'twitterTitle',
        'twitterDescription',
        'twitterImage',
        'allowIndexing',
        'schema',
    ];

    public ?string $metaTitle = null;

    public ?string $metaDescription = null;

    public ?string $facebookTitle = null;

    public ?string $facebookDescription = null;

    /** @var int[]|null */
    public ?array $facebookImage = null;

    public ?string $twitterTitle = null;

    public ?string $twitterDescription = null;

    /** @var int[]|null */
    public ?array $twitterImage = null;

    public ?string $siteName = null;

    public bool $hideSiteName = false;

    public ?int $siteId = null;

    public ?string $canonical = null;

    public ?string $schema = null;

    public string $allowIndexing = 'yes';

    public ?ElementInterface $element = null;

    private ?SeoDefaultsModel $_siteDefault = null;

    public function __construct(array $attributes = [])
    {
        $this->setAttributes($attributes);
    }

    /**
     * Assigns stored attributes. Unknown keys are ignored.
     */
    public function setAttributes(array $values): void
    {
        foreach (array_intersect_key($values, array_flip([...self::ATTRIBUTES, 'siteId'])) as $name => $value) {
            $this->$name = match ($name) {
                'facebookImage', 'twitterImage' => $value ? array_values(array_map('intval', (array) $value)) : null,
                'hideSiteName' => (bool) $value,
                'siteId' => $value ? (int) $value : null,
                'allowIndexing' => $value === 'no' ? 'no' : 'yes',
                default => $value === '' ? null : $value,
            };
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_intersect_key(get_object_vars($this), array_flip(self::ATTRIBUTES));
    }

    /** The defaults of the field's site */
    public SeoDefaultsModel $siteDefault {
        get {
            $site = ($this->siteId ? Sites::getSiteById($this->siteId) : null) ?? Sites::getCurrentSite();

            return $this->_siteDefault ??= SeoFields::getInstance()->defaultsService->getDataBySite($site);
        }
    }

    /** @deprecated The site defaults are loaded on demand through `siteDefault` */
    public function getDefaults(): SeoDefaultsModel
    {
        return $this->siteDefault;
    }

    public function getSchema(?ElementInterface $element = null): void
    {
        if (! $element || ! (ElementSeoMacros::get($element, 'shouldRenderSchema') ?? true)) {
            return;
        }

        try {
            $schemaService = SeoFields::getInstance()->schemaService;
            $defaults = SeoFields::getInstance()->defaultsService->getDataBySite(Sites::getPrimarySite());
            $settings = $defaults->getSchema() ?? [];

            $graph = $schemaService->getGraph();

            $entityName = $defaults->organizationName ?: ($defaults->defaultSiteTitle ?: Cms::systemName());
            $entityClass = $defaults->siteEntity ?: get_class(Schema::organization());

            $entity = $graph->{$schemaService->getGraphMethodName($entityClass)}()
                ->setProperty('@id', '#organization')
                ->name($entityName)
                ->url(Url::siteUrl());

            if ($defaults->organizationLogo && $logo = Asset::find()->id($defaults->organizationLogo[0])->first()) {
                $entity->logo($logo->getUrl());
            }

            if ($defaults->sameAs) {
                $entity->sameAs($defaults->sameAs);
            }

            $graph->webSite()
                ->setProperty('@id', '#website')
                ->name($entityName)
                ->publisher(['@id' => '#organization'])
                ->url(Url::siteUrl());

            $schemaClass = WebPage::class;
            if ($element instanceof Entry && isset($settings['sections'])) {
                $schemaClass = $settings['sections'][$element->sectionId] ?? WebPage::class;
            }
            if (! empty($this->schema)) {
                $schemaClass = $this->schema;
            }

            $pageNode = $graph->{$schemaService->getGraphMethodName($schemaClass)}()
                ->setProperty('@id', '#page')
                ->author(['@id' => '#organization'])
                ->isPartOf(['@id' => '#website'])
                ->url($element->getUrl() ?? '');
            $schemaService->setPageNode($pageNode);
            $schemaService->setPageDefaults($this, $element);
        } catch (Throwable $e) {
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }

    public function getSiteNameWithSeperator(): string|false
    {
        if ($this->hideSiteName) {
            return false;
        }

        $siteName = $this->siteName ?: ($this->siteDefault->defaultSiteTitle ?: Cms::systemName());
        $seperator = $this->siteDefault->titleSeperator ?: '-';

        return ' '.$seperator.' '.$siteName;
    }

    public function getPageTitle(?ElementInterface $element = null, bool $includeSiteName = true): string
    {
        if ($element) {
            $this->element = $element;
        }

        $title = match (true) {
            (bool) ElementSeoMacros::get($element, 'socialTitle') => ElementSeoMacros::get($element, 'socialTitle'),
            $element && ! $this->metaTitle => $element->title,
            default => $this->metaTitle,
        };

        return $title.($includeSiteName ? $this->getSiteNameWithSeperator() : '');
    }

    public function getCanonical(): string
    {
        return request()->getSchemeAndHttpHost().'/'.ltrim(request()->path(), '/');
    }

    public function getMetaTitle(?ElementInterface $element = null): ?string
    {
        $element ??= $this->element;
        if (ElementSeoMacros::get($element, 'metaTitle')) {
            return ElementSeoMacros::get($element, 'metaTitle');
        }

        return $this->metaTitle ?: $this->getPageTitle($element, false);
    }

    public function getSocialTitle(?ElementInterface $element = null): string
    {
        $title = match (true) {
            (bool) ElementSeoMacros::get($element, 'socialTitle') => ElementSeoMacros::get($element, 'socialTitle'),
            (bool) $this->facebookTitle => $this->facebookTitle,
            default => $this->getPageTitle($element, false),
        };

        return $title.$this->getSiteNameWithSeperator();
    }

    public function getMetaDescription(): ?string
    {
        return ElementSeoMacros::get($this->element, 'socialDescription')
            ?: ElementSeoMacros::get($this->element, 'metaDescription')
            ?: $this->metaDescription
            ?: $this->siteDefault->defaultMetaDescription;
    }

    public function getSocialDescription(): ?string
    {
        return ElementSeoMacros::get($this->element, 'socialDescription')
            ?: $this->facebookDescription
            ?: $this->siteDefault->defaultMetaDescription;
    }

    /**
     * @return array{height: int|null, width: int|null, url: string|null, alt: string|null}|false
     */
    public function getSocialImage(?Asset $asset = null): array|false
    {
        $asset ??= ElementSeoMacros::get($this->element, 'socialImage');

        $id = $this->facebookImage[0] ?? $this->siteDefault->defaultImage[0] ?? null;
        if (! $asset && $id) {
            $asset = Asset::find()->id($id)->first();
        }

        if (! $asset) {
            return false;
        }

        $transform = ['width' => 1200, 'height' => 590, 'mode' => 'crop'];

        return [
            'height' => $asset->getHeight($transform),
            'width' => $asset->getWidth($transform),
            'url' => $asset->getUrl($transform),
            'alt' => $asset->title,
        ];
    }

    /**
     * @return list<array{url: string, language: string}>|false
     */
    public function getAlternate(?ElementInterface $element = null): array|false
    {
        if (! $element) {
            return false;
        }

        $sites = DB::table(Table::ELEMENTS_SITES.' as es')
            ->join(Table::SITES.' as s', 's.id', '=', 'es.siteId')
            ->where('es.elementId', $element->id)
            ->where('es.enabled', true)
            ->where('s.enabled', true)
            ->whereNull('s.dateDeleted')
            ->whereNotNull('es.uri')
            ->select(['es.siteId', 'es.uri', 's.language'])
            ->distinct()
            ->get();

        if (SeoFields::getInstance()->getSettings()->logicallySeperatedSiteGroups) {
            $groupId = Sites::getCurrentSite()->groupId;
            $sites = $sites->filter(fn ($site) => Sites::getSiteById($site->siteId)?->groupId === $groupId);
        }

        if ($sites->isEmpty()) {
            return false;
        }

        return $sites->map(fn ($site) => [
            'url' => Url::siteUrl($site->uri === '__home__' ? '' : $site->uri, siteId: $site->siteId),
            'language' => $site->language,
        ])->values()->all();
    }

    /** @deprecated Use `getSocialTitle()` */
    public function getOgTitle(?ElementInterface $element = null): string
    {
        $this->deprecated('getOgTitle', 'getOgTitle has been replaced by `getSocialTitle` and will be removed in a later update');

        return $this->getSocialTitle($element);
    }

    /** @deprecated Use `getSocialTitle()` */
    public function getTwitterTitle(?ElementInterface $element = null): string
    {
        $this->deprecated('getTwitterTitle', 'getTwitterTitle has been replaced by `getSocialTitle` and will be removed in a later update');

        return $this->getSocialTitle($element);
    }

    /** @deprecated Use `getSocialDescription()` */
    public function getOgDescription(): ?string
    {
        $this->deprecated('getOgDescription', 'getOgDescription has been replaced by `getSocialDescription` and will be removed in a later update');

        return $this->getSocialDescription();
    }

    /** @deprecated Use `getSocialDescription()` */
    public function getTwitterDescription(): ?string
    {
        $this->deprecated('getTwitterDescription', 'getTwitterDescription has been replaced by `getSocialDescription` and will be removed in a later update');

        return $this->getSocialDescription();
    }

    /** @deprecated Use `getSocialImage()` */
    public function getOgImage(?Asset $asset = null): array|false
    {
        $this->deprecated('getOgImage', 'getOgImage has been replaced by `getSocialImage` and will be removed in a later update');

        return $this->getSocialImage($asset);
    }

    /** @deprecated Use `getSocialImage()` */
    public function getTwitterImage(?Asset $asset = null): array|false
    {
        $this->deprecated('getTwitterImage', 'getTwitterImage has been replaced by `getSocialImage` and will be removed in a later update');

        return $this->getSocialImage($asset);
    }

    /**
     * Overwriting SEO properties through `entry.seo.setX()` no longer works; set them on the element instead.
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (preg_match('/^set(MetaTitle|MetaDescription|FacebookTitle|FacebookDescription|FacebookImage|TwitterTitle|TwitterDescription|TwitterImage)$/', $name)) {
            $this->deprecated($name, "Overwriting SEO properties through `entry.seo.$name` no longer works. Please see the docs for an upgrading guide.");

            return null;
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    private function deprecated(string $method, string $message): void
    {
        Deprecator::log(self::class.'::'.$method, $message);
    }
}
