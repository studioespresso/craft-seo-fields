<?php

namespace studioespresso\seofields\services;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\Events\ViewAssetsRendering;
use Illuminate\Support\Facades\Event;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Schema;
use studioespresso\seofields\models\SeoFieldModel;
use studioespresso\seofields\SeoFields;

/**
 * @author    Studio Espresso
 *
 * @since     4.0.0
 */
class SchemaService
{
    private ?Graph $graph = null;

    private bool $renderRegistered = false;

    private bool $rendered = false;

    private ?BaseType $pageNode = null;

    private array $additionalPageTypes = [];

    private ?string $pageType = null;

    private ?SeoFieldModel $pageDefaultsModel = null;

    private ?ElementInterface $pageDefaultsElement = null;

    public function getGraph(): Graph
    {
        if ($this->graph === null) {
            $this->graph = new Graph;
            $this->registerDeferredRender();
        }

        return $this->graph;
    }

    public function setPageNode(BaseType $node): void
    {
        $this->pageNode = $node;
    }

    public function getPageNode(): ?BaseType
    {
        return $this->pageNode;
    }

    public function setPageDefaults(SeoFieldModel $model, ElementInterface $element): void
    {
        $this->pageDefaultsModel = $model;
        $this->pageDefaultsElement = $element;
    }

    public function setPageType(string $type): void
    {
        $this->pageType = $type;
    }

    public function addPageType(string $type): void
    {
        if (! in_array($type, $this->additionalPageTypes, true)) {
            $this->additionalPageTypes[] = $type;
        }
    }

    public function getGraphMethodName(string $className): string
    {
        return lcfirst((new \ReflectionClass($className))->getShortName());
    }

    private function registerDeferredRender(): void
    {
        if ($this->renderRegistered) {
            return;
        }
        $this->renderRegistered = true;

        // Fires once the page has rendered, before its registered assets are output
        Event::listen(ViewAssetsRendering::class, function () {
            if ($this->graph === null || $this->rendered) {
                return;
            }
            $this->rendered = true;

            // Apply default name/description to the page node only if not already set by user template code
            if ($this->pageNode !== null && $this->pageDefaultsModel !== null) {
                if ($this->pageNode->getProperty('name') === null) {
                    $this->pageNode->setProperty(
                        'name',
                        $this->pageDefaultsModel->getMetaTitle($this->pageDefaultsElement) ?? ''
                    );
                }
                if ($this->pageNode->getProperty('description') === null) {
                    $this->pageNode->setProperty(
                        'description',
                        $this->pageDefaultsModel->getMetaDescription() ?? ''
                    );
                }
            }

            // Override page type: merge properties from the standalone override node into #page, remove the standalone
            if ($this->pageType !== null) {
                $data = $this->graph->toArray();
                $overrideType = $this->pageType;

                // Find the standalone node of the override type (no @id) and collect its properties
                $overrideProps = [];
                $data['@graph'] = array_values(array_filter($data['@graph'], function ($node) use ($overrideType, &$overrideProps) {
                    if (($node['@type'] ?? '') === $overrideType && ! isset($node['@id'])) {
                        $overrideProps = array_filter($node, fn ($k) => ! str_starts_with($k, '@'), ARRAY_FILTER_USE_KEY);

                        return false; // remove this node
                    }

                    return true;
                }));

                // Update the #page node: change @type, merge properties
                foreach ($data['@graph'] as &$node) {
                    if (($node['@id'] ?? '') === '#page') {
                        $node['@type'] = $overrideType;
                        $node = array_merge($node, $overrideProps);
                    }
                }
                unset($node);

                // Still apply additionalPageTypes if any
                if (! empty($this->additionalPageTypes)) {
                    foreach ($data['@graph'] as &$node) {
                        if (($node['@id'] ?? '') === '#page') {
                            $types = (array) ($node['@type'] ?? []);
                            $node['@type'] = array_values(array_unique(array_merge($types, $this->additionalPageTypes)));
                        }
                    }
                    unset($node);
                }

                $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                HtmlStack::html('<script type="application/ld+json">'.$json.'</script>', Position::BodyEnd);

                return;
            }

            if (empty($this->additionalPageTypes)) {
                HtmlStack::html($this->graph->toScript(), Position::BodyEnd);

                return;
            }

            $data = $this->graph->toArray();
            // Remove standalone nodes for additional types, but only if they don't have their own @id
            $data['@graph'] = array_values(array_filter($data['@graph'], function ($node) {
                if (! in_array($node['@type'] ?? '', $this->additionalPageTypes, true)) {
                    return true;
                }

                // Keep nodes that have an explicit @id (they were intentionally added)
                return isset($node['@id']);
            }));
            foreach ($data['@graph'] as &$node) {
                if (($node['@id'] ?? '') === '#page') {
                    $types = (array) ($node['@type'] ?? []);
                    $node['@type'] = array_values(array_unique(array_merge($types, $this->additionalPageTypes)));
                }
            }
            unset($node);

            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            HtmlStack::html('<script type="application/ld+json">'.$json.'</script>', Position::BodyEnd);
        });
    }

    public function getDefaultOptions()
    {
        $options = SeoFields::getInstance()->getSettings()->schemaOptions;

        return array_merge([
            get_class(Schema::webPage()) => 'WebPage',
            get_class(Schema::contactPage()) => 'Contact Page',
            get_class(Schema::article()) => 'Article',
            get_class(Schema::creativeWork()) => 'Creative Work',
            get_class(Schema::review()) => 'Review',
            get_class(Schema::organization()) => 'Organization',
            get_class(Schema::recipe()) => 'Recipe',
            get_class(Schema::person()) => 'Person',
        ], $options);
    }

    public function getSiteEntityOptions()
    {
        $options = SeoFields::getInstance()->getSettings()->siteEntityOptions;

        return array_merge([
            get_class(Schema::organization()) => 'Organization',
            get_class(Schema::localBusiness()) => 'Local Business',
            get_class(Schema::person()) => 'Person',
            get_class(Schema::event()) => 'Event',
            get_class(Schema::governmentOrganization()) => 'Government Organization',
            get_class(Schema::educationalOrganization()) => 'Educational Organization',
            get_class(Schema::sportsOrganization()) => 'Sports Organization',
        ], $options);
    }

    public function schema()
    {
        return new Schema;
    }
}
