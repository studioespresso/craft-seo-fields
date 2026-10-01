<?php

/**
 * SEO Fields plugin for Craft CMS
 *
 * Fields for your SEO & OG data
 *
 * @link      https://studioespresso.co
 *
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\seofields\fields;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\AssetSelect;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\ContentBlock;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Form\Nodes\MarkdownContent;
use CraftCms\Cms\Form\Nodes\TemplateContent;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use studioespresso\seofields\models\SeoFieldModel;
use studioespresso\seofields\SeoFields;

use function CraftCms\Cms\t;

/**
 * @author    Studio Espresso
 *
 * @since     1.0.0
 */
class SeoField extends Field
{
    /** @var array{general?: bool, facebook?: bool, advanced?: bool} Which groups to show; all by default */
    public array $tabs = [];

    public bool $allowSitenameOverwrite = false;

    public bool $allowSitenameDisable = false;

    public static function displayName(): string
    {
        return t('SEO Fields', category: 'seo-fields');
    }

    public static function icon(): string
    {
        return 'magnifying-glass';
    }

    public static function phpType(): string
    {
        return SeoFieldModel::class;
    }

    public static function isMultiInstance(): bool
    {
        return false;
    }

    public static function dbType(): string
    {
        return Query::TYPE_JSON;
    }

    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'allowSitenameOverwrite' => ['boolean'],
            'allowSitenameDisable' => ['boolean'],
        ]);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): SeoFieldModel
    {
        if ($value instanceof SeoFieldModel) {
            return $value;
        }

        $model = new SeoFieldModel(is_array($value) ? $value : (Json::decodeIfJson($value) ?: []));
        $model->siteId = $element?->siteId;

        return $model;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): ?array
    {
        return $value instanceof SeoFieldModel ? $value->toArray() : null;
    }

    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            MarkdownContent::make('seo-fields-handle-note', t('Note that if your field handle is **not** `seo`, you will have to set [`fieldHandle`]({link}) in the plugin config to tell it about your field.', ['link' => 'https://studioespresso.github.io/craft-seo-fields/field.html#your-field'], 'seo-fields')),
            FormField::make(t('Show general tab', category: 'seo-fields'), Lightswitch::make('tabs.general')->value($this->showTab('general'))),
            FormField::make(t('Show social media tab', category: 'seo-fields'), Lightswitch::make('tabs.facebook')->value($this->showTab('facebook'))),
            FormField::make(t('Show advanced tab', category: 'seo-fields'), Lightswitch::make('tabs.advanced')->value($this->showTab('advanced'))),
            FormField::make(t('Allow sitename overwrite', category: 'seo-fields'), Lightswitch::make('allowSitenameOverwrite'))
                ->instructions(t('Allow the user to overwrite the sitename that gets added to the entry title on a per-entry basis', category: 'seo-fields')),
            FormField::make(t('Allow sitename to be hidden', category: 'seo-fields'), Lightswitch::make('allowSitenameDisable'))
                ->instructions(t('Allow the user to hide the sitename on a per entry basis', category: 'seo-fields')),
        ]);
    }

    public function formControl(FieldContext $context): Control
    {
        /** @var SeoFieldModel $value */
        $value = $context->value;
        $element = $context->element;
        $value->siteId = $element?->siteId;

        $groups = [];

        if ($this->showTab('general')) {
            $groups[] = Group::make('seo-general', array_values(array_filter([
                $element ? TemplateContent::make('seo-preview', $this->searchPreview($value, $element)) : null,
                FormField::make(t('Meta title', category: 'seo-fields'), Text::make('metaTitle')->value($value->metaTitle)),
                $this->allowSitenameOverwrite
                    ? FormField::make(t('Site name', category: 'seo-fields'), Text::make('siteName')->value($value->siteName))
                    : null,
                $this->allowSitenameDisable
                    ? FormField::make(t('Hide site name', category: 'seo-fields'), Lightswitch::make('hideSiteName')->value($value->hideSiteName))
                    : null,
                FormField::make(t('Meta description', category: 'seo-fields'), Textarea::make('metaDescription')->maxLength(300)->value($value->metaDescription)),
                FormField::make(t('Schema type', category: 'seo-fields'), Choice::make('schema')
                    ->options($this->schemaOptions())
                    ->value($value->schema ?? ''))
                    ->instructions(t('Override the default schema type for this entry', category: 'seo-fields')),
            ])))->label(t('General meta', category: 'seo-fields'));
        }

        if ($this->showTab('facebook')) {
            $groups[] = Group::make('seo-social', [
                FormField::make(t('Social Media title', category: 'seo-fields'), Text::make('facebookTitle')->value($value->facebookTitle)),
                FormField::make(t('Social Media description', category: 'seo-fields'), Textarea::make('facebookDescription')->maxLength(300)->value($value->facebookDescription)),
                FormField::make(t('Social Media image', category: 'seo-fields'), AssetSelect::make('facebookImage')
                    ->elementType(Asset::class)
                    ->criteria(['kind' => ['image']])
                    ->single()
                    ->viewMode(AssetSelect::VIEW_MODE_LIST)
                    ->selectionLabel(t('Select an image', category: 'seo-fields'))
                    ->value($value->facebookImage ?? [])),
            ])->label(t('Social Media', category: 'seo-fields'));
        }

        if ($this->showTab('advanced')) {
            $groups[] = Group::make('seo-advanced', [
                FormField::make(t('Allow search engines to index this page?', category: 'seo-fields'), Choice::make('allowIndexing')
                    ->options([
                        ['label' => t('Yes', category: 'seo-fields'), 'value' => 'yes'],
                        ['label' => t('No', category: 'seo-fields'), 'value' => 'no'],
                    ])
                    ->value($value->allowIndexing))
                    ->instructions(t('Disabling this option will add a noindex header and will remove the page from any sitemaps', category: 'seo-fields')),
            ])->label(t('Advanced', category: 'seo-fields'));
        }

        return ContentBlock::make($context->path)
            ->form(Form::make($groups))
            ->value($value->toArray());
    }

    /** Whether the field settings show a group; all are shown until configured */
    private function showTab(string $tab): bool
    {
        return (bool) ($this->tabs[$tab] ?? true);
    }

    /** @return list<array{label: string, value: string}> */
    private function schemaOptions(): array
    {
        $options = [['label' => t('Use section default', category: 'seo-fields'), 'value' => '']];
        foreach (SeoFields::getInstance()->schemaService->getDefaultOptions() as $class => $label) {
            $options[] = ['label' => $label, 'value' => $class];
        }

        return $options;
    }

    /** A static preview of the saved values as a search result */
    private function searchPreview(SeoFieldModel $value, ElementInterface $element): string
    {
        return sprintf(
            '<p><strong>%s</strong><br><small>%s</small><br>%s</p>',
            e($value->getPageTitle($element)),
            e($element->getUrl() ?? ''),
            e($value->metaDescription ?? ''),
        );
    }
}
