<?php

namespace studioespresso\seofields\events;

/**
 * Fired to collect the element types whose template variables carry SEO data.
 *
 * ```php
 * Event::listen(fn (RegisterSeoElementEvent $event) => $event->elements[] = MyElement::class);
 * ```
 *
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class RegisterSeoElementEvent
{
    /** @param class-string[] $elements */
    public function __construct(
        public array $elements = [],
    ) {}
}
