---
title: LLM.txt - SEO Fields
prev: false
next: false
---

# LLM.txt

### What is llms.txt?
[llms.txt](https://llmstxt.org/) is a standard for providing structured, markdown-formatted information about your website for large language models. It helps AI tools understand your site's content and structure in a concise, machine-readable way.

### Enabling
You can enable llms.txt from the control panel under **SEO > LLM.txt**. Toggle the "Enable llms.txt" switch to have the plugin handle your `/llms.txt` route.

### Title & Summary
Two optional fields let you customize the top of your llms.txt output:

- **Title** — The main heading for your llms.txt file. Defaults to the site name if left empty.
- **Summary** — A brief description of what the website is about. This is rendered as a blockquote in the output.

### Description Fallback Fields
For each entry type, you can select a field to use as a fallback description when no SEO meta description is set on an entry. Only **Plain Text** and **CKEditor** fields are available as options.

The description priority for each entry is:
1. SEO meta description (if set)
2. The configured fallback field for that entry type
3. No description

### Generated Content
The plugin automatically builds the llms.txt content based on your site's sections and category groups:

- **Singles** — Listed under an "Overview" heading.
- **Channels** — Each channel gets its own heading showing the field types it contains, the total entry count, and up to 5 most recent entries.
- **Structures** — Each structure gets its own heading with entries displayed in their full hierarchy using nested lists.
- **Categories** — Grouped under a "Categories" heading, each group showing its count and up to 5 example categories.

Sections and category groups are **skipped** if they don't have URLs enabled for the current site or have zero entries.

### Entry Format
Each entry is rendered as a markdown list item following the llms.txt spec:

```
- [Entry Title](https://example.com/entry-url): Description text
```

The title is taken from the SEO meta title if set, otherwise the entry title. The description follows the priority described above.

### Caching
The generated llms.txt output is cached automatically. The cache is invalidated when entries are saved or deleted, and when sections or entry types change. You can also manually clear it from Craft's **Utilities > Clear Caches** tool.

### Multisite
Each site gets its own llms.txt settings and output. Use the site switcher at the top of the LLM.txt settings page to configure each site independently.

### Example Output
```markdown
# My Website

> A brief description of the website.

## Overview

- [Home](https://example.com): Welcome to our website
- [About](https://example.com/about): Learn more about us

## Blog

Contains: Plain Text, CKEditor
5 entries

- [Latest Post](https://example.com/blog/latest-post): Summary of the post
- [Another Post](https://example.com/blog/another-post): Another summary

## Pages

- [Services](https://example.com/services): Our services
  - [Web Development](https://example.com/services/web-development): Custom web solutions
  - [Design](https://example.com/services/design): Creative design services

## Categories

### Topics (3)

- [Technology](https://example.com/topics/technology)
- [Design](https://example.com/topics/design)
- [Business](https://example.com/topics/business)
```
