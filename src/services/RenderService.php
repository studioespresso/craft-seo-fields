<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\web\View;
use studioespresso\seofields\models\SeoFieldModel;
use studioespresso\seofields\SeoFields;

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
        $handle = SeoFields::$plugin->getSettings()->fieldHandle;
        Craft::beginProfile('renderMeta', __METHOD__);

        try {
            if (isset($context['entry'])) {
                if (isset($context['entry'][$handle])) {
                    $meta = $context['entry'][$handle];
                } else {
                    $meta = new SeoFieldModel();
                }
                $element = $context['entry'];
            } elseif (isset($context['product'])) {
                if (isset($context['product'][$handle])) {
                    $meta = $context['product'][$handle];
                } else {
                    $meta = new SeoFieldModel();
                }
                $element = $context['product'];
            }
        } catch
        (\Exception $e) {
            return false;
        }

        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        Craft::endProfile('renderMeta', __METHOD__);
        return Craft::$app->getView()->renderTemplate(
            'seo-fields/_meta',
            ['meta' => $meta, 'entry' => $element]
        );
    }

}
