<?php

namespace studioespresso\seofields\behaviors;

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

    public string|null $facebookTitle = null;

    public string|null $twitterTitle = null;

    public string|null $metaDescription = null;

    public string|null $facebookDescription = null;

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

    public function setFacebookTitle(string $value): void
    {
        $this->facebookTitle = $value;
    }

    public function getFacebookTitle(): string|null
    {
        return $this->facebookTitle;
    }

    public function setTwitterTitle(string $value): void
    {
        $this->twitterTitle = $value;
    }

    public function getTwitterTitle(): string|null
    {
        return $this->twitterTitle;
    }


    public function setMetaDescription(string $value): void
    {
        $this->metaDescription = $value;
    }

    public function getMetaDescription(): string|null
    {
        return $this->metaDescription;
    }

    public function setFacebookDescription(string $value): void
    {
        $this->facebookDescription = $value;
    }

    public function getFacebookDescription(): string|null
    {
        return $this->facebookDescription;
    }


    public function setFacebookImage(Asset $value): void
    {
        $this->facebookImage = $value;
    }

    public function getFacebookImage(): Asset|null
    {
        return $this->facebookImage;
    }

    public function setTwitterDescription(string $value): void
    {
        $this->twitterDescription = $value;
    }

    public function getTwitterDescription(): string|null
    {
        return $this->twitterDescription;
    }

    public function setTwitterImage(Asset $value): void
    {
        $this->twitterImage = $value;
    }

    public function getTwitterImage(): Asset|null
    {
        return $this->twitterImage;
    }
}
