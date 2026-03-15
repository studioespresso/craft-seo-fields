<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\web\View;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Schema;
use studioespresso\seofields\SeoFields;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     4.0.0
 */
class SchemaService extends Component
{
    private ?Graph $graph = null;
    private bool $renderRegistered = false;
    private ?BaseType $pageNode = null;
    private array $additionalPageTypes = [];

    public function getGraph(): Graph
    {
        if ($this->graph === null) {
            $this->graph = new Graph();
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

    public function addPageType(string $type): void
    {
        $this->additionalPageTypes[] = $type;
    }

    private function registerDeferredRender(): void
    {
        if ($this->renderRegistered) {
            return;
        }
        $this->renderRegistered = true;

        Craft::$app->getView()->on(View::EVENT_END_PAGE, function () {
            if ($this->graph === null) {
                return;
            }

            if (empty($this->additionalPageTypes)) {
                Craft::$app->getView()->registerHtml(
                    $this->graph->toScript(),
                    View::POS_END
                );
                return;
            }

            $data = $this->graph->toArray();
            // Remove standalone nodes for additional types and merge into page node
            $data['@graph'] = array_values(array_filter($data['@graph'], function ($node) {
                return !in_array($node['@type'] ?? '', $this->additionalPageTypes);
            }));
            foreach ($data['@graph'] as &$node) {
                if (($node['@id'] ?? '') === '#page') {
                    $types = (array)($node['@type'] ?? []);
                    $node['@type'] = array_values(array_unique(array_merge($types, $this->additionalPageTypes)));
                }
            }
            unset($node);

            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            Craft::$app->getView()->registerHtml(
                '<script type="application/ld+json">' . $json . '</script>',
                View::POS_END
            );
        });
    }

    public function getDefaultOptions()
    {
        $options = SeoFields::getInstance()->getSettings()->schemaOptions;
        return array_merge([
            get_class(Schema::webPage()) => 'WebPage',
            get_class(Schema::article()) => 'Article',
            get_class(Schema::creativeWork()) => 'Creative Work',
            get_class(Schema::review()) => 'Review',
            get_class(Schema::organization()) => 'Organisation',
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
        return new Schema();
    }
}
