<?php

namespace studioespresso\seofields\extensions;

use studioespresso\seofields\SeoFields;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * SeoFieldsExtension class.
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class SeoFieldsExtension extends AbstractExtension
{

    public function getFunctions()
    {
        return [
            new TwigFunction(
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
        return $data;
    }

}