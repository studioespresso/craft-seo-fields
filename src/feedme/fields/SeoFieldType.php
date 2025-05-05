<?php

namespace studioespresso\seofields\feedme\fields;

use Cake\Utility\Hash;
use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;
use studioespresso\seofields\fields\SeoField;

class SeoFieldType extends Field implements FieldInterface
{
    /**
     * @var string
     */
    public static string $name = 'SEO Field Type';

    /**
     * @var string
     */
    public static string $class = SeoField::class;

    /**
     * @inheritdoc
     */
    public function getMappingTemplate(): string
    {
        // Return a valid template path for your plugin:
        return 'seo-fields/feedme/seo-mapping-template';
    }

    /**
     * @inheritdoc
     */
    public function parseField(): mixed
    {
        $preppedData = [];

        /** @phpstan-ignore-next-line */
        $fields = Hash::get($this->fieldInfo, 'seo-fields');

        if (!$fields) {
            return null;
        }

        foreach ($fields as $subFieldHandle => $subFieldInfo) {
            /** @phpstan-ignore-next-line */
            $preppedData[$subFieldHandle] = DataHelper::fetchValue($this->feedData, $subFieldInfo);
        }

        // Protect against sending an empty array
        if (!$preppedData) {
            return null;
        }

        return $preppedData;
    }
}
