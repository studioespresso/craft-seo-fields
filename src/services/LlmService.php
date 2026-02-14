<?php

namespace studioespresso\seofields\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\elements\Category;
use craft\elements\Entry;
use craft\models\Site;
use studioespresso\seofields\SeoFields;
use yii\caching\TagDependency;

class LlmService extends Component
{
    public const LLM_CACHE_KEY = 'seofields_cache_llm';

    public function generateMarkdown(Site $site, array $llmData): string
    {
        $cacheKey = self::LLM_CACHE_KEY . '_site' . $site->id;
        $cacheDependency = new TagDependency([
            'tags' => [
                self::LLM_CACHE_KEY,
                $cacheKey,
            ],
        ]);

        $duration = Craft::$app->getConfig()->general->devMode ? 1 : null;

        return Craft::$app->getCache()->getOrSet(
            $cacheKey,
            function () use ($site, $llmData) {
                return $this->_buildMarkdown($site, $llmData);
            },
            $duration,
            $cacheDependency
        );
    }

    public function clearCaches(): void
    {
        TagDependency::invalidate(
            Craft::$app->getCache(),
            [self::LLM_CACHE_KEY]
        );
    }

    private function _buildMarkdown(Site $site, array $llmData): string
    {
        $lines = [];
        $descriptionFields = $llmData['descriptionFields'] ?? [];

        // H1 — title
        $title = !empty($llmData['title']) ? $llmData['title'] : $site->name;
        $lines[] = '# ' . $title;
        $lines[] = '';

        // Blockquote — summary
        if (!empty($llmData['summary'])) {
            foreach (explode("\n", $llmData['summary']) as $summaryLine) {
                $lines[] = '> ' . $summaryLine;
            }
            $lines[] = '';
        }

        // Sections
        $sections = Craft::$app->getEntries()->getAllSections();
        $singles = [];
        $channels = [];

        foreach ($sections as $section) {
            $siteSettings = $section->getSiteSettings();
            if (!isset($siteSettings[$site->id]) || !$siteSettings[$site->id]->hasUrls) {
                continue;
            }

            $count = Entry::find()
                ->siteId($site->id)
                ->sectionId($section->id)
                ->count();

            if ($count === 0) {
                continue;
            }

            if ($section->type === 'single') {
                $entry = Entry::find()
                    ->siteId($site->id)
                    ->sectionId($section->id)
                    ->one();
                if ($entry && $entry->getUrl()) {
                    $singles[] = $entry;
                }
            } else {
                $channels[] = [
                    'section' => $section,
                    'count' => $count,
                ];
            }
        }

        // Singles
        if (!empty($singles)) {
            $lines[] = '## Overview';
            $lines[] = '';
            foreach ($singles as $entry) {
                $lines[] = $this->_formatElementLink($entry, $descriptionFields);
            }
            $lines[] = '';
        }

        // Channels & Structures
        foreach ($channels as $channelData) {
            $section = $channelData['section'];
            $count = $channelData['count'];

            $lines[] = '## ' . $section->name;
            $lines[] = '';

            // Collect field types across all entry types in this section
            $fieldNames = [];
            foreach ($section->getEntryTypes() as $entryType) {
                $fieldLayout = $entryType->getFieldLayout();
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $displayName = $field::displayName();
                    $fieldNames[$displayName] = true;
                }
            }
            if (!empty($fieldNames)) {
                $lines[] = 'Contains: ' . implode(', ', array_keys($fieldNames));
                $lines[] = '';
            }

            $lines[] = $count . ' ' . ($count === 1 ? 'entry' : 'entries');
            $lines[] = '';

            if ($section->type === 'structure') {
                // All entries in structure order, nested by level
                $entries = Entry::find()
                    ->siteId($site->id)
                    ->sectionId($section->id)
                    ->status('live')
                    ->orderBy('lft ASC')
                    ->all();

                foreach ($entries as $entry) {
                    if ($entry->getUrl()) {
                        $indent = str_repeat('  ', $entry->level - 1);
                        $lines[] = $indent . $this->_formatElementLink($entry, $descriptionFields);
                    }
                }
            } else {
                // Up to 5 most recent entries for channels
                $entries = Entry::find()
                    ->siteId($site->id)
                    ->sectionId($section->id)
                    ->status('live')
                    ->orderBy('postDate DESC')
                    ->limit(5)
                    ->all();

                foreach ($entries as $entry) {
                    if ($entry->getUrl()) {
                        $lines[] = $this->_formatElementLink($entry, $descriptionFields);
                    }
                }
            }
            $lines[] = '';
        }

        // Category groups
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();
        $validGroups = [];

        foreach ($categoryGroups as $group) {
            $siteSettings = $group->getSiteSettings();
            if (!isset($siteSettings[$site->id]) || !$siteSettings[$site->id]->hasUrls) {
                continue;
            }

            $count = Category::find()
                ->siteId($site->id)
                ->groupId($group->id)
                ->count();

            if ($count === 0) {
                continue;
            }

            $validGroups[] = [
                'group' => $group,
                'count' => $count,
            ];
        }

        if (!empty($validGroups)) {
            $lines[] = '## Categories';
            $lines[] = '';

            foreach ($validGroups as $groupData) {
                $group = $groupData['group'];
                $count = $groupData['count'];

                $lines[] = '### ' . $group->name . ' (' . $count . ')';
                $lines[] = '';

                $categories = Category::find()
                    ->siteId($site->id)
                    ->groupId($group->id)
                    ->limit(5)
                    ->all();

                foreach ($categories as $category) {
                    if ($category->getUrl()) {
                        $lines[] = $this->_formatElementLink($category, $descriptionFields);
                    }
                }
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    private function _formatElementLink(Element $element, array $descriptionFields = []): string
    {
        $fieldHandle = SeoFields::$plugin->getSettings()->fieldHandle;
        $title = $element->title;
        $description = null;

        if ($fieldHandle && isset($element->$fieldHandle)) {
            $seoData = $element->$fieldHandle;
            if ($seoData->metaTitle) {
                $title = $seoData->metaTitle;
            }
            if ($seoData->metaDescription) {
                $description = $seoData->metaDescription;
            }
        }

        // Fallback: use the configured description field for this entry type
        if (!$description && $element instanceof Entry) {
            $entryTypeId = (string) $element->getType()->id;
            if (!empty($descriptionFields[$entryTypeId])) {
                $fallbackHandle = $descriptionFields[$entryTypeId];
                if (isset($element->$fallbackHandle)) {
                    $value = (string) $element->$fallbackHandle;
                    $value = strip_tags($value);
                    $value = trim($value);
                    if (!empty($value)) {
                        $description = $value;
                    }
                }
            }
        }

        $link = '- [' . $title . '](' . $element->getUrl() . ')';
        if ($description) {
            $link .= ': ' . $description;
        }

        return $link;
    }
}
