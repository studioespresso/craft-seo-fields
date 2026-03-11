<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\web\View;
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

    public function getGraph(): Graph
    {
        if ($this->graph === null) {
            $this->graph = new Graph();
            $this->registerDeferredRender();
        }
        return $this->graph;
    }

    private function registerDeferredRender(): void
    {
        if ($this->renderRegistered) {
            return;
        }
        $this->renderRegistered = true;

        Craft::$app->getView()->on(View::EVENT_END_PAGE, function () {
            if ($this->graph !== null) {
                Craft::$app->getView()->registerHtml(
                    $this->graph->toScript(),
                    View::POS_END
                );
            }
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

    public function schema()
    {
        return new Schema();
    }
}
