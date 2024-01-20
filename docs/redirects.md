---
title: Schema markup - SEO Fields
prev: false
next: false
---
 
# Redirects

You can redirect legacy urls, to make sure you don't lose SEO value when migrating/restructuring your website. Another use for this is a short urls that are easier to read or print, should you have need for those.

::: tip Important
These redirects will only work if there is no live page in the CMS with the same url. Otherwise that page will be shown first.
:::

## Redirect options/parameters

**Old pattern or URL to redirect** 

This can be the full URL (with the domain) or just the URI (without the domain)

**Which part of the URL should be matched**

Options here are:
- path or uri only
- path or uri only **without any query parameters** 
- the full url

**URL to redirect to**

This has to be a a full url, not a relative one.

**Match type**

- Exact match
- Regex match

**Match Type**

- 301 (permanent redirect)
- 302 (temporary redirect)