<?php

namespace studioespresso\seofields\behaviors;

use Craft;
use craft\elements\Asset;
use craft\elements\db\EntryQuery;
use yii\base\Behavior;

/**
 * Class EntryQueryBehavior
 *
 * @property EntryQuery $owner
 */
class ElementSeoBehavior extends Behavior
{
    public bool $shouldRenderSchema = true;

    public string|null $metaTitle = null;

    public string|null $socialTitle = null;

    public string|null $facebookTitle = null;

    public string|null $twitterTitle = null;

    public string|null $metaDescription = null;

    public string|null $socialDescription = null;

    public string|null $facebookDescription = null;

    public Asset|null $socialImage = null;

    public Asset|null $facebookImage = null;

    public string|null $twitterDescription = null;

    public Asset|null $twitterImage = null;

    public function setShouldRenderSchema(bool $value): void
    {
        $this->shouldRenderSchema = $value;
    }

    public function getShouldRenderSchema(): bool
    {
        return $this->shouldRenderSchema;
    }

    public function setMetaTitle(string $value): void
    {
        $this->metaTitle = $value;
    }

    public function getMetaTitle(): string|null
    {
        return $this->metaTitle;
    }

    public function setSocialTitle(string $value): void
    {
        $this->socialTitle = $value;
    }

    public function getSocialTitle(): string|null
    {
        return $this->socialTitle;
    }

    public function setFacebookTitle(string $value): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookTitle', "setFacebookTitle has been replaced by `setSocialTitle` and will be removed in a later update", __FILE__, __LINE__-2);
        $this->socialTitle = $value;
    }

    public function getFacebookTitle(): string|null
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getFacebookTitle', "getFacebookTitle has been replaced by `getSocialTitle` and will be removed in a later update", __FILE__, __LINE__-2);
        return $this->socialTitle;
    }

    public function setTwitterTitle(string $value): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterTitle', "setTwitterTitle has been replaced by `setSocialTitle` and will be removed in a later update", __FILE__, __LINE__-2);
        $this->socialTitle = $value;
    }

    public function getTwitterTitle(): string|null
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterTitle', "getTwitterTitle has been replaced by `getSocialTitle` and will be removed in a later update", __FILE__, __LINE__-2);
        return $this->socialTitle;
    }


    public function setMetaDescription(string $value): void
    {
        $this->metaDescription = $value;
    }

    public function getMetaDescription(): string|null
    {
        return $this->metaDescription;
    }

    public function setSocialDescription(string $value): void
    {
        $this->socialDescription = $value;
    }

    public function getSocialDescription(): string|null
    {
        return $this->socialDescription;
    }

    public function setFacebookDescription(string $value): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookDescription', "setFacebookDescription has been replaced by `setSocialDescription` and will be removed in a later update", __FILE__, __LINE__-2);
        $this->facebookDescription = $value;
    }

    public function getFacebookDescription(): string|null
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getFacebookDescription', "getFacebookDescription has been replaced by `getSocialDescription` and will be removed in a later update", __FILE__, __LINE__-2);
        return $this->facebookDescription;
    }

    public function setTwitterDescription(string $value): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterDescription', "setTwitterDescription has been replaced by `setSocialDescription` and will be removed in a later update", __FILE__, __LINE__-2);
        $this->twitterDescription = $value;
    }

    public function getTwitterDescription(): string|null
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterDescription', "getTwitterDescription has been replaced by `getSocialDescription` and will be removed in a later update", __FILE__, __LINE__-2);
        return $this->twitterDescription;
    }

    public function setSocialImage(Asset $value): void
    {
        $this->socialImage = $value;
    }

    public function getSocialImage(): Asset|null
    {
        return $this->socialImage;
    }

    public function setFacebookImage(Asset $value): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookImage', "setFacebookImage has been replaced by `setSocialImage` and will be removed in a later update", __FILE__, __LINE__-2);
        $this->facebookImage = $value;
    }

    public function getFacebookImage(): Asset|null
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getFacebookImage', "getFacebookImage has been replaced by `getSocialImage` and will be removed in a later update", __FILE__, __LINE__-2);
        return $this->facebookImage;
    }


    public function setTwitterImage(Asset $value): void
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterImage', "setTwitterImage has been replaced by `setSocialImage` and will be removed in a later update", __FILE__, __LINE__-2);
        $this->twitterImage = $value;
    }

    public function getTwitterImage(): Asset|null
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterImage', "getTwitterImage has been replaced by `getSocialImage` and will be removed in a later update", __FILE__, __LINE__-2);
        return $this->twitterImage;
    }
}
