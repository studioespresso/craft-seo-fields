<?php

namespace studioespresso\seofields\extensions;

use studioespresso\seofields\SeoFields;

/**
 * SeoFieldsExtension class.
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class SeoFieldsExtension extends \Twig_Extension
{

    public function getFunctions()
    {
        return [
            new \Twig_Function(
                'getSeoFields',
                [$this, 'getSeoFields'],
                ['needs_context' => true]
            ),
        ];
    }

    // Functions
    // =========================================================================
    public function getSeoFields($context)
    {
        $data =  SeoFields::getInstance()->renderService->getSeoFromContent($context, SeoFields::getInstance()->getSettings()->fieldHandle);
        return $data['meta'];
    }

}