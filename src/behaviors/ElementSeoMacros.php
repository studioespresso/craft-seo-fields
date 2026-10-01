<?php

namespace studioespresso\seofields\behaviors;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Facades\Deprecator;
use WeakMap;

/**
 * Element macros that let templates override SEO values per page, e.g. `{% do entry.setMetaTitle('…') %}`.
 *
 * Macros are shared by all element types; the values live in a WeakMap so they're dropped with the element.
 */
class ElementSeoMacros
{
    /** Current name => deprecated aliases */
    private const VALUES = [
        'metaTitle' => [],
        'metaDescription' => [],
        'socialTitle' => ['facebookTitle', 'twitterTitle'],
        'socialDescription' => ['facebookDescription', 'twitterDescription'],
        'socialImage' => ['facebookImage', 'twitterImage'],
    ];

    /** @var WeakMap<ElementInterface, array<string, mixed>> */
    private static WeakMap $values;

    public static function register(): void
    {
        self::$values = new WeakMap;

        foreach (self::VALUES as $name => $aliases) {
            $method = ucfirst($name);
            Element::macro("set$method", fn (string|Asset $value) => ElementSeoMacros::set($this, $name, $value));
            Element::macro("get$method", fn () => ElementSeoMacros::get($this, $name));

            foreach ($aliases as $alias) {
                $aliasMethod = ucfirst($alias);
                Element::macro("set$aliasMethod", function (string|Asset $value) use ($aliasMethod, $method, $name) {
                    ElementSeoMacros::deprecated("set$aliasMethod", "set$aliasMethod has been replaced by `set$method` and will be removed in a later update");
                    ElementSeoMacros::set($this, $name, $value);
                });
                Element::macro("get$aliasMethod", function () use ($aliasMethod, $method, $name) {
                    ElementSeoMacros::deprecated("get$aliasMethod", "get$aliasMethod has been replaced by `get$method` and will be removed in a later update");

                    return ElementSeoMacros::get($this, $name);
                });
            }
        }

        Element::macro('setShouldRenderSchema', function (bool $value) {
            ElementSeoMacros::deprecated('setShouldRenderSchema', 'setShouldRenderSchema is deprecated. Use `seoFields.graph` to add schema types directly instead.');
            ElementSeoMacros::set($this, 'shouldRenderSchema', $value);
        });
        Element::macro('getShouldRenderSchema', fn () => ElementSeoMacros::get($this, 'shouldRenderSchema') ?? true);
    }

    public static function set(ElementInterface $element, string $name, mixed $value): void
    {
        self::$values[$element] = [...(self::$values[$element] ?? []), $name => $value];
    }

    public static function get(?ElementInterface $element, string $name): mixed
    {
        return $element ? (self::$values[$element][$name] ?? null) : null;
    }

    public static function deprecated(string $method, string $message): void
    {
        Deprecator::log(self::class.'::'.$method, $message);
    }
}
