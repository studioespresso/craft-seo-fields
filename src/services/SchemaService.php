<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\web\View;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Schema;
use studioespresso\seofields\debug\SchemaPanel;
use studioespresso\seofields\SeoFields;
use yii\debug\Module as DebugModule;

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
        if (!in_array($type, $this->additionalPageTypes, true)) {
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

        Craft::$app->getView()->on(View::EVENT_END_PAGE, function() {
            if ($this->graph === null) {
                return;
            }

            if (empty($this->additionalPageTypes)) {
                $data = $this->graph->toArray();
                $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                $this->_saveDebugData($data, $json);

                Craft::$app->getView()->registerHtml(
                    $this->graph->toScript(),
                    View::POS_END
                );
                return;
            }

            $data = $this->graph->toArray();
            // Remove standalone nodes for additional types, but only if they don't have their own @id
            $data['@graph'] = array_values(array_filter($data['@graph'], function($node) {
                if (!in_array($node['@type'] ?? '', $this->additionalPageTypes, true)) {
                    return true;
                }
                // Keep nodes that have an explicit @id (they were intentionally added)
                return isset($node['@id']);
            }));
            foreach ($data['@graph'] as &$node) {
                if (($node['@id'] ?? '') === '#page') {
                    $types = (array)($node['@type'] ?? []);
                    $node['@type'] = array_values(array_unique(array_merge($types, $this->additionalPageTypes)));
                }
            }
            unset($node);

            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->_saveDebugData($data, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

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
        return new Schema();
    }

    public function validateSchema(array $data): array
    {
        $warnings = [];

        foreach ($data['@graph'] ?? [] as $index => $node) {
            $types = $node['@type'] ?? null;
            if ($types === null) {
                $warnings[] = "Node #$index is missing @type.";
                continue;
            }

            $typeList = is_array($types) ? $types : [$types];
            $validProperties = [];

            foreach ($typeList as $type) {
                $className = 'Spatie\\SchemaOrg\\' . $type;
                if (!class_exists($className)) {
                    $warnings[] = "Node \"{$type}\": Unknown schema.org type.";
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->getDeclaringClass()->getNamespaceName() === 'Spatie\\SchemaOrg'
                        && $method->getNumberOfParameters() >= 1
                        && !str_starts_with($method->getName(), '__')
                        && !in_array($method->getName(), ['setProperty', 'addProperties', 'if', 'setNonce', 'getProperty', 'getProperties', 'referenced', 'toArray', 'toScript', 'jsonSerialize'], true)
                    ) {
                        $validProperties[$method->getName()] = true;
                    }
                }
            }

            if (empty($validProperties)) {
                continue;
            }

            $nodeProperties = array_filter(
                array_keys($node),
                fn($k) => !str_starts_with($k, '@'),
            );

            foreach ($nodeProperties as $property) {
                if (!isset($validProperties[$property])) {
                    $typeLabel = is_array($types) ? implode('/', $types) : $types;
                    $warnings[] = "Node \"{$typeLabel}\": Property \"{$property}\" is not defined for this type.";
                }
            }
        }

        return $warnings;
    }

    private function _saveDebugData(array $data, string $json): void
    {
        $debugModule = Craft::$app->getModule('debug');
        if (
            $debugModule instanceof DebugModule &&
            isset($debugModule->panels['schema']) &&
            $debugModule->panels['schema'] instanceof SchemaPanel
        ) {
            $warnings = $this->validateSchema($data);
            $debugModule->panels['schema']->data = [
                'schema' => $data,
                'json' => $json,
                'nodes' => count($data['@graph'] ?? []),
                'warnings' => $warnings,
            ];
        }
    }
}
