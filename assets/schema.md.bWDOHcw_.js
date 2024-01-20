import{_ as e,c as a,o as s,V as t,a4 as i}from"./chunks/framework.sSymVGL-.js";const u=JSON.parse('{"title":"Schema.org markup - SEO Fields","description":"hello","frontmatter":{"title":"Schema.org markup - SEO Fields","prev":false,"next":false,"head":[["meta",{"name":"description","content":"hello"}],["meta",{"name":"keywords","content":"super duper SEO"}]]},"headers":[],"relativePath":"schema.md","filePath":"schema.md"}'),n={name:"schema.md"},h=t('<h1 id="schema-org-markup" tabindex="-1">Schema.org markup <a class="header-anchor" href="#schema-org-markup" aria-label="Permalink to &quot;Schema.org markup&quot;">​</a></h1><h2 id="schema-type-per-section" tabindex="-1">Schema type per section <a class="header-anchor" href="#schema-type-per-section" aria-label="Permalink to &quot;Schema type per section&quot;">​</a></h2><p>In the Schema.org section of the plugin, you can set a Schema type for each of the sections defined in your site.</p><p>The fields from the SEO field (meta title, meta description, facebook image) will be used to populated the Schema and it will be added to the page in <code>JSON-LD</code> format.</p><img src="'+i+`" alt=""><h2 id="custom-markup-template-overrides" tabindex="-1">Custom markup &amp; template overrides <a class="header-anchor" href="#custom-markup-template-overrides" aria-label="Permalink to &quot;Custom markup &amp; template overrides&quot;">​</a></h2><p>In case you want to specify the schema type yourself, overwrite the values or set additional properties, you can do so by following these steps.</p><h3 id="_1-disable-to-plugin-from-rendering-the-schema-data" tabindex="-1">1) Disable to plugin from rendering the schema data. <a class="header-anchor" href="#_1-disable-to-plugin-from-rendering-the-schema-data" aria-label="Permalink to &quot;1) Disable to plugin from rendering the schema data.&quot;">​</a></h3><p>This can be done with the following snippet:</p><div class="language-twig vp-adaptive-theme"><button title="Copy Code" class="copy"></button><span class="lang">twig</span><pre class="shiki shiki-themes github-light github-dark vp-code"><code><span class="line"><span style="--shiki-light:#6A737D;--shiki-dark:#6A737D;">{# make sure you add this line above any layout you&#39;re extending #}</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">{% </span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;">do</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;"> entry.setShouldRenderSchema(</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;">false</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">) %}</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">{% extend </span><span style="--shiki-light:#032F62;--shiki-dark:#9ECBFF;">&#39;layout.twig&#39;</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;"> %}</span></span></code></pre></div><h3 id="_2-add-you-own-schema-tag" tabindex="-1">2) Add you own schema tag <a class="header-anchor" href="#_2-add-you-own-schema-tag" aria-label="Permalink to &quot;2) Add you own schema tag&quot;">​</a></h3><p>You can create a new Schema object through the <code>seoFields</code> variable in your template.</p><div class="language-twig vp-adaptive-theme"><button title="Copy Code" class="copy"></button><span class="lang">twig</span><pre class="shiki shiki-themes github-light github-dark vp-code"><code><span class="line"><span style="--shiki-light:#6A737D;--shiki-dark:#6A737D;">{# @var schema \\Spatie\\SchemaOrg\\Schema #}</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">{% </span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;">set</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;"> schema </span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;">=</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;"> seoFields.schema %}</span></span></code></pre></div><p>When using PHPStorm, it&#39;s recommended that you use the <a href="https://plugins.jetbrains.com/plugin/7219-symfony-plugin" target="_blank" rel="noreferrer">Symfony Plugin</a>, to get proper autocompletion on the created object Doing that will make it easier to add a script like the example below:</p><div class="language-twig vp-adaptive-theme"><button title="Copy Code" class="copy"></button><span class="lang">twig</span><pre class="shiki shiki-themes github-light github-dark vp-code"><code><span class="line"><span style="--shiki-light:#6A737D;--shiki-dark:#6A737D;">{# @var schema \\Spatie\\SchemaOrg\\Schema #}</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">{% </span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;">set</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;"> schema </span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;">=</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;"> craft.schema %}</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">{{ schema.organization</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">    .name(</span><span style="--shiki-light:#032F62;--shiki-dark:#9ECBFF;">&quot;Studio Espresso&quot;</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">)</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">    .email(</span><span style="--shiki-light:#032F62;--shiki-dark:#9ECBFF;">&quot;info@studioespresso.co&quot;</span><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">)</span></span>
<span class="line"><span style="--shiki-light:#24292E;--shiki-dark:#E1E4E8;">|raw }}</span></span></code></pre></div><h2 id="add-your-own-types-through-settings" tabindex="-1">Add your own types through settings <a class="header-anchor" href="#add-your-own-types-through-settings" aria-label="Permalink to &quot;Add your own types through settings&quot;">​</a></h2><p>Out of the box, you can set a section to one of the following types:</p><ul><li><a href="https://schema.org/WebPage" target="_blank">WebPage</a></li><li><a href="https://schema.org/Article" target="_blank">Article</a></li><li><a href="https://schema.org/CreativeWork" target="_blank">CreativeWork</a></li><li><a href="https://schema.org/Review" target="_blank">Review</a></li><li><a href="https://schema.org/Organisation" target="_blank">Organisation</a></li><li><a href="https://schema.org/Recipe" target="_blank">Recipe</a></li><li><a href="https://schema.org/Person" target="_blank">Person</a></li></ul><p>You can extend this list by setting the <code>schemaOptions</code> attribute in the <code>seo-fields.php</code> settings file. You can find the syntax <a href="./.html">here</a></p>`,19),o=[h];function l(r,p,d,c,k,g){return s(),a("div",null,o)}const E=e(n,[["render",l]]);export{u as __pageData,E as default};
