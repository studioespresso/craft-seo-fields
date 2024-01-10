<?php

namespace studioespresso\seofields\extensions;

use Spatie\SchemaOrg\Schema;
use studioespresso\seofields\SeoFields;
use studioespresso\seofields\variables\SeoFieldsVariable;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * SeoFieldsExtension class.
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     1.0.0
 */
class SeoFieldsExtension extends AbstractExtension implements GlobalsInterface
{
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'getSeoFields',
                [$this, 'getSeoFields'],
                ['needs_context' => true]
            )
        ];
    }

    public function getGlobals(): array
    {
        return [
            'seoFields' => SeoFields::getInstance()->schemaService
        ];
    }

    // Functions
    // =========================================================================
    public function getSeoFields($context)
    {
        $data = SeoFields::getInstance()->renderService->getSeoFromContent($context, SeoFields::getInstance()->getSettings()->fieldHandle);
        return $data;
    }
}
