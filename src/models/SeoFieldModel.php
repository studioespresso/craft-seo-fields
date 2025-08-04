<?php

namespace studioespresso\seofields\models;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\ImageTransform;
use craft\web\View;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\WebPage;
use studioespresso\seofields\SeoFields;

class SeoFieldModel extends Model
{
    public $metaTitle;
    public $metaDescription;

    public $facebookTitle;
    public $facebookDescription;
    public $facebookImage;

    public $twitterTitle;
    public $twitterDescription;
    public $twitterImage;

    public $siteName;
    public $hideSiteName;
    public $siteId;
    public $canonical;

    public $schema;
    public $allowIndexing = 'yes';

    /**
     * @var SeoDefaultsModel
     */
    public $siteDefault;

    public $element;

    public function init(): void
    {
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }
        $this->siteDefault = SeoFields::getInstance()->defaultsService->getDataBySite($site);
    }

    public function getDefaults()
    {
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }
        $this->siteDefault = SeoFields::getInstance()->defaultsService->getDataBySite($site);
    }

    public function getSchema(Element $element = null)
    {
        if (!$element) {
            return null;
        }

        /** @phpstan-ignore-next-line */
        if (!$element->getShouldRenderSchema()) {
            return null;
        }

        try {
            $schema = null;
            $primarySite = Craft::$app->getSites()->getPrimarySite();
            $defaults = SeoFields::getInstance()->defaultsService->getDataBySite($primarySite);
            $settings = $defaults->getSchema();

            switch (get_class($element)) {
                case Entry::class:
                    if (isset($settings['sections'])) {
                        $schemaSettings = $settings['sections'];
                        $sectionId = $element->section->id;
                        $schemaClass = $schemaSettings[$sectionId] ?? WebPage::class;
                    } else {
                        $schemaClass = WebPage::class;
                    }

                    /** @var Schema $schema */
                    $schema = \Craft::createObject($schemaClass);
                    $schema->name($this->getMetaTitle($element) ?? ""); // @phpstan-ignore-line
                    $schema->description($this->getMetaDescription() ?? ""); // @phpstan-ignore-line
                    $schema->url($element->getUrl() ?? ""); // @phpstan-ignore-line
                    break;
                case Category::class:
                    if(isset($settings['categories'])) {
                        $schemaSettings = $settings['categories'];
                        $groupId = $element->group->id;
                        $schemaClass = $schemaSettings[$groupId] ?? WebPage::class;
                    } else {
                        $schemaClass = WebPage::class;
                    }
                    $schemaSettings = $settings['groups'];
                    $groupId = $element->group->id;
                    $schemaClass = $schemaSettings[$groupId] ?? WebPage::class;

                    /** @var Schema $schema */
                    $schema = Craft::createObject($schemaClass);
                    $schema->name($this->getMetaTitle($element) ?? ""); // @phpstan-ignore-line
                    $schema->description($this->getMetaDescription() ?? ""); // @phpstan-ignore-line
                    $schema->url($element->getUrl() ?? ""); // @phpstan-ignore-line
                    break;
            }
            if ($schema) {
                \Craft::$app->getView()->registerScript(
                    Json::encode($schema),
                    View::POS_END, [
                        'type' => 'application/ld+json',
                    ]
                );
            }
        } catch (\Exception $e) {
            \Craft::error($e, SeoFields::class);
            return null;
        }
    }

    public function getSiteNameWithSeperator()
    {
        $this->getDefaults();
        if ($this->hideSiteName) {
            return false;
        }
        if ($this->siteName) {
            $siteName = $this->siteName;
        } elseif ($this->siteDefault->defaultSiteTitle) {
            $siteName = $this->siteDefault->defaultSiteTitle;
        } else {
            $siteName = Craft::$app->getSystemName();
        }

        $seperator = $this->siteDefault->titleSeperator ? $this->siteDefault->titleSeperator : '-';
        return ' ' . $seperator . ' ' . $siteName;
    }


    public function getPageTitle($element = null, $includeSiteName = true)
    {
        if ($element) {
            $this->element = $element;
        }
        if ($element && $element->getSocialTitle()) {
            return $element->getSocialTitle() . ($includeSiteName ? $this->getSiteNameWithSeperator() : '');
        }
        if ($element && !$this->metaTitle) {
            return $element->title . ($includeSiteName ? $this->getSiteNameWithSeperator() : '');
        }
        return $this->metaTitle . ($includeSiteName ? $this->getSiteNameWithSeperator() : '');
    }

    public function getCanonical()
    {
        $request = Craft::$app->getRequest();
        return $request->hostInfo . '/' . $request->getPathInfo(true);
    }


    public function getMetaTitle($element)
    {
        $element = $element ?? $this->element;
        $title = $this->getPageTitle($element, false);

        if ($element->getMetaTitle()) {
            $title = $element->getMetaTitle();
        } elseif ($this->metaTitle) {
            $title = $this->metaTitle;
        }
        return $title;
    }

    public function getSocialTitle($element)
    {
        $title = $this->getPageTitle($element, false);

        if ($element->getFacebookTitle()) {
            $title = $element->getFacebookTitle();
        } elseif ($this->facebookTitle) {
            $title = $this->facebookTitle;
        }

        return $title . $this->getSiteNameWithSeperator();
    }

    public function getOgTitle($element = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getOgTitle', "getOgTitle has been replaced by `getSocialTitle` and will be removed in a later update");
        return $this->getSocialTitle($element);
    }

    public function getTwitterTitle($element = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterTitle', "getTwitterTitle has been replaced by `getSocialTitle` and will be removed in a later update");
        return $this->getSocialTitle($element);
    }

    public function getMetaDescription()
    {
        if ($this->element && $this->element->getSocialDescription()) {
            return $this->element->getSocialDescription();
        }

        if ($this->element->getMetaDescription()) {
            return $this->element->getMetaDescription();
        }

        if ($this->metaDescription) {
            return $this->metaDescription;
        }

        return $this->siteDefault->defaultMetaDescription;
    }

    public function getSocialDescription()
    {
        if ($this->element && $this->element->getSocialDescription()) {
            return $this->element->getSocialDescription();
        }
        if ($this->element->getFacebookDescription()) {
            return $this->element->getFacebookDescription();
        }

        if ($this->facebookDescription) {
            return $this->facebookDescription;
        }

        return $this->siteDefault->defaultMetaDescription;
    }

    public function getOgDescription()
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getOgDescription', "getOgDescription has been replaced by `getSocialDescription` and will be removed in a later update");
        return $this->getSocialDescription();
    }

    public function getTwitterDescription()
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterDescription', "getTwitterDescription has been replaced by `getSocialDescription` and will be removed in a later update");
        return $this->getSocialDescription();
    }


    public function getSocialImage(Asset $asset = null)
    {
        if ($asset) {
            $asset = $asset;
        } elseif ($this->element->getFacebookImage()) {
            $asset = $this->element->getFacebookImage();
        } elseif ($this->facebookImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->facebookImage[0]);
        } elseif ($this->siteDefault->defaultImage) {
            $asset = Craft::$app->getAssets()->getAssetById($this->siteDefault->defaultImage[0]);
        }
        if (!isset($asset)) {
            return false;
        }

        $transform = $this->_getPreviewTransform($asset);
        return [
            'height' => $asset->getHeight($transform),
            'width' => $asset->getWidth($transform),
            'url' => $asset->getUrl($transform, true),
            'alt' => $asset->title,
        ];
    }

    public function getOgImage(Asset $asset = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getOgImage', "getOgImage has been replaced by `getSocialImage` and will be removed in a later update");
        return $this->getSocialImage($asset);
    }

    public function getTwitterImage(Asset $asset = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'getTwitterImage', "getTwitterImage has been replaced by `getSocialImage` and will be removed in a later update");
        return $this->getSocialImage($asset);
    }

    public function getAlternate($element = null)
    {
        if (!$element) {
            return false;
        }
        $siteEntries =
            (new Query())->select(['siteId', 'uri', 'language', 'sites.primary as primary'])
                ->from('{{%elements_sites}} as  elements')
                ->leftJoin('{{%sites}} as sites', 'sites.id = elements.siteId')
                ->where('[[elementId]] = ' . $element->id)
                ->andWhere('sites.enabled = 1')
                ->andWhere('sites.dateDeleted IS NULL')
                ->andWhere('elements.enabled = true')
                ->distinct(true)
                ->all();
        $currentSite = Craft::$app->getSites()->getCurrentSite()->id;
        $sites = $siteEntries;
        if (empty($sites)) {
            return false;
        }

        $data = [];
        foreach ($sites as $site) {
            if ($site['uri']) {
                $data[] = [
                    'url' => UrlHelper::siteUrl($site['uri'] === '__home__' ? '' : $site['uri'], null, null, $site['siteId']),
                    'language' => $site['language'],
                ];
            }
        }

        return $data;
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setMetaTitle($value = null)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setMetaTitle', "Overwriting SEO properties through `entry.seo.setMetaTitle` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setMetaDescription($value)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setMetaDescription', "Overwriting SEO properties through `entry.seo.setMetaDescription` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setFacebookTitle($value)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookTitle', "Overwriting SEO properties through `entry.seo.setFacebookTitle` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setFacebookDescription($value)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookDescription', "Overwriting SEO properties through `entry.seo.setFacebookDescription` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setFacebookImage($value)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setFacebookImage', "Overwriting SEO properties through `entry.seo.setFacebookImage` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setTwitterTitle($value)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterTitle', "Overwriting SEO properties through `entry.seo.setTwitterTitle` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setTwitterDescription($value)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterDescription', "Overwriting SEO properties through `entry.seo.setTwitterDescription` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @param $value
     * @return void
     * @throws \craft\errors\DeprecationException
     * @deprecated 5.0.0 Overwriting SEO properties through this method no longer works. Please see the docs for an upgrading guide
     */
    public function setTwitterImage($value)
    {
        Craft::$app->getDeprecator()->log(__CLASS__ . 'setTwitterImage', "Overwriting SEO properties through `entry.seo.setTwitterImage` no longer works. Please see the docs for an upgrading guide ");
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [
                [
                    'metaTitle',
                    'metaDescription',
                    'siteName',
                    'hideSiteName',
                    'facebookTitle',
                    'facebookDescription',
                    'facebookImage',
                    'twitterTitle',
                    'twitterDescription',
                    'twitterImage',
                    'allowIndexing',
                ],
                'safe',
            ],
        ];
    }

    private function _getPreviewTransform(Asset $asset)
    {
        $transform = new ImageTransform();
        $transform->width = 1200;
        $transform->height = 590;
        $transform->mode = 'crop';
        if ($asset->hasFocalPoint) {
            $transform->position = implode(',', $asset->focalPoint);
        }
        return $transform;
    }
}
