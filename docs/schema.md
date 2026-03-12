---
title: Schema.org markup - SEO Fields
prev: false
next: false
head:
  - - meta
    - name: description
      content: hello
  - - meta
    - name: keywords
      content: super duper SEO
---
 
# Schema.org markup

## Schema type per section

In the Schema.org section of the plugin, you can set a Schema type for each of the sections defined in your site.

The fields from the SEO field (meta title, meta description, facebook image) will be used to populated the Schema and it will be added to the page in ``JSON-LD`` format.

<img src="/img/schema_org.png" alt="">

## Schema Builder API

The plugin exposes a shared schema graph that you can add to from any template or include. All types are merged into a single `<script type="application/ld+json">` tag automatically — no manual output needed.

### Adding schema types

Use `seoFields.graph` to add top-level schema types to the page:

````twig
{% do seoFields.graph.event()
    .name(entry.title)
    .description(entry.intro|striptags)
    .url(entry.url)
%}
````

The default schema (organization + section-level type) is still added automatically. Anything you add from your templates merges into the same graph.

### Building across includes

Your entry template and its includes all contribute to the same graph:

````twig
{# _events/_entry.twig #}
{% do seoFields.graph.event()
    .name(entry.title)
    .description(entry.intro|striptags)
    .url(entry.url)
%}

{% include '_snippets/_faq' with { faqs: entry.faqBlocks } %}
````

````twig
{# _snippets/_faq.twig #}
{% if faqs|length %}
    {% set questions = [] %}
    {% for faq in faqs.all() %}
        {% set questions = questions|merge([
            seoFields.schema.question()
                .name(faq.title)
                .acceptedAnswer(
                    seoFields.schema.answer().text(faq.answer|striptags)
                )
        ]) %}
    {% endfor %}
    {% do seoFields.graph.fAQPage().mainEntity(questions) %}
{% endif %}
````

### `seoFields.graph` vs `seoFields.schema`

- **`seoFields.graph`** — the shared Graph instance for the current request. Use this for top-level schema types (Event, FAQPage, Article, etc.) that end up in the `@graph` array.
- **`seoFields.schema`** — a factory for creating standalone types. Use this for nested objects like `Question` and `Answer` that get passed as properties to graph nodes.

### Setting custom properties

Every schema type supports the fluent API from [spatie/schema-org](https://github.com/spatie/schema-org). For properties without a named method, use `setProperty`:

````twig
{% do seoFields.graph.event()
    .setProperty('@id', '#my-event')
    .setProperty('eventAttendanceMode', 'https://schema.org/OfflineEventAttendanceMode')
%}
````

## Disabling schema output

If you want to fully disable schema output for a specific entry, you can still do so:

````twig
{% do entry.setShouldRenderSchema(false) %}
{% extends 'layout.twig' %}
````


## Add your own types through settings

Out of the box, you can set a section to one of the following types:
- <a href="https://schema.org/WebPage" target="_blank">WebPage</a>
- <a href="https://schema.org/Article" target="_blank">Article</a>
- <a href="https://schema.org/CreativeWork" target="_blank">CreativeWork</a>
- <a href="https://schema.org/Review" target="_blank">Review</a>
- <a href="https://schema.org/Organisation" target="_blank">Organisation</a>
- <a href="https://schema.org/Recipe" target="_blank">Recipe</a>
- <a href="https://schema.org/Person" target="_blank">Person</a>

You can extend this list by setting the ``schemaOptions`` attribute in the ``seo-fields.php`` settings file. You can find the syntax [here]() 

