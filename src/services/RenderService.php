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

        try {
            if (isset($context['entry']) && isset($context['entry'][$handle])) {
                $meta = $context['entry'][$handle];
            } elseif (isset($context['product']) && isset($context['product'][$handle])) {
                $meta = $context['product'][$handle];
            } else {
                $meta = new SeoFieldModel();
            }
        } catch (\Exception $e) {
            return false;
        }

        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        return Craft::$app->getView()->renderTemplate(
            'seo-fields/_meta',
            ['meta' => $meta, 'entry' => $context['entry']]
        );
    }

}
