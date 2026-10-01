<?php

namespace studioespresso\seofields\services;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\View\TemplateMode;
use studioespresso\seofields\events\RegisterSeoElementEvent;
use studioespresso\seofields\models\SeoFieldModel;

use function CraftCms\Cms\template;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class RenderService
{
    public function renderMeta(array $context, string $handle = 'seo'): string
    {
        $data = $this->getSeoFromContent($context, $handle);

        return template('seo-fields/_meta', [
            'meta' => $data['meta'],
            'element' => $data['element'],
            'requestUrl' => request()->fullUrl(),
            // Error templates get a `statusCode` variable; canonical links are left out on those
            'statusCode' => $context['statusCode'] ?? 200,
        ], TemplateMode::Cp);
    }

    /**
     * Finds the SEO field value of the element the template is rendering (e.g. `entry`).
     *
     * @return array{meta: SeoFieldModel, element: mixed, entry: mixed}
     */
    public function getSeoFromContent(array $context, string $handle): array
    {
        $meta = null;
        $element = null;

        foreach ($this->registeredElements() as $class) {
            $variable = strtolower(class_basename($class));
            if (isset($context[$variable])) {
                $element = $context[$variable];
                $meta = $element->$handle ?? null;
            }
        }

        if (! $meta instanceof SeoFieldModel) {
            $meta = new SeoFieldModel;
        }

        return ['meta' => $meta, 'entry' => $element, 'element' => $element];
    }

    /** @return class-string[] */
    private function registeredElements(): array
    {
        event($event = new RegisterSeoElementEvent);

        return [...array_filter($event->elements), Entry::class];
    }
}
