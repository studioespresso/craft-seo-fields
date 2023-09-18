<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\elements\Category;
use craft\elements\Entry;
use craft\web\View;
use studioespresso\seofields\events\RegisterSeoElementEvent;
use studioespresso\seofields\models\SeoFieldModel;
use studioespresso\seofields\SeoFields;
use yii\base\Event;

/**
 *
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class RenderService extends Component
{

    // Public Methods
    // =========================================================================
    public function renderMeta($context, $handle = 'seo')
    {

        Craft::beginProfile('renderMeta', __METHOD__);
        $data = $this->getSeoFromContent($context, $handle);
        $oldTemplateMode = Craft::$app->getView()->getTemplateMode();


            Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
            $template = Craft::$app->getView()->renderTemplate(
                'seo-fields/_meta',
                ['meta' => $data['meta'], 'element' => $data['entry']]
            );

            Craft::endProfile('renderMeta', __METHOD__);
            Craft::$app->getView()->setTemplateMode($oldTemplateMode);
            return $template;

        try {
        } catch (\Exception $e) {
            Craft::$app->getView()->setTemplateMode($oldTemplateMode);
            return null;
        }
    }

    public function getSeoFromContent($context, $handle)
    {
        $meta = null;
        $element = null;
        $handle = SeoFields::$plugin->getSettings()->fieldHandle;

        Craft::beginProfile('renderMeta', __METHOD__);

        $registeredElements = $this->_registerElementsEvent();

        try {
            foreach ($registeredElements as $item) {
                $class = explode('\\', $item);
                $elementName = strtolower(end($class));
                if (isset($context[$elementName])) {
                    if (isset($context[$elementName][$handle])) {
                        $meta = $context[$elementName][$handle];
                    } else {
                        $meta = new SeoFieldModel();
                    }
                    $element = $context[$elementName];
                }
            }

            if (!$meta) {
                $meta = new SeoFieldModel();
            }

            return ['meta' => $meta, 'entry' => $element, 'element' => $element];

        } catch (\Exception $e) {
            return null;
        }
    }

    private function _registerElementsEvent()
    {
        $elements = [];
        $event = new RegisterSeoElementEvent([
            'elements' => $elements,
        ]);

        Event::trigger(SeoFields::class, SeoFields::EVENT_SEOFIELDS_REGISTER_ELEMENT, $event);
        $registeredElements = array_filter($event->elements);

        array_push($registeredElements, Entry::class);
        array_push($registeredElements, Category::class);

        return $registeredElements;
    }

}
