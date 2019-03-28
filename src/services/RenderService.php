<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
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
        $meta = false;
        $element = null;
        $handle = SeoFields::$plugin->getSettings()->fieldHandle;
        Craft::beginProfile('renderMeta', __METHOD__);
        $elements = [Entry::class];

        $event = new RegisterSeoElementEvent([
            'elements' => $elements,
        ]);

        Event::trigger(SeoFields::class, SeoFields::EVENT_SEOFIELDS_REGISTER_ELEMENT, $event);
        $registeredElements = array_filter($event->elements);

        foreach($registeredElements as $item) {
            $class = explode('\\', $item);
            $elementName = strtolower(end($class));
            if(isset($context[$elementName])) {
                if (isset($context[$elementName][$handle])) {
                    $meta = $context[$elementName][$handle];
                } else {
                    $meta = new SeoFieldModel();
                }
                $element = $context[$elementName];
            }
        }

        if(!$meta) {
            $meta = new SeoFieldModel();
        }

        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        Craft::endProfile('renderMeta', __METHOD__);
        return Craft::$app->getView()->renderTemplate(
            'seo-fields/_meta',
            ['meta' => $meta, 'entry' => $element]
        );
    }

}
