<?php

namespace studioespresso\seofields\services\migrate;


use Craft;
use craft\base\Component;
use craft\elements\Entry;
use ether\seo\models\data\SeoData;
use ether\seo\models\data\SocialData;
use studioespresso\seofields\models\SeoFieldModel;
use yii\helpers\Console;

/**
 * @author    Studio Espresso
 * @package   SeoFields
 * @since     2.0.0
 */
class Ether extends Component
{

    private $titleSeperator;

    public function migrate($oldHandle = 'seo', $newHandle = 'newSeo', $siteId = null, $titleSeperator = '|')
    {
        $query = Entry::find();
        $sites = [];

        $this->titleSeperator = $titleSeperator;
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
            if (!$site) {
                throw new SiteNotFoundException("Site with id '$siteId' not found.");
            }
            $sites[] = $site;
        } else {
            $sites = Craft::$app->getSites()->getAllSites();
        }

        foreach ($sites as $site) {
            echo "Migrating entries for $site->handle\n";
            $query->siteId($site->id);
            $total = clone $query ;
            $total = $total->count();

            Console::startProgress(0, $total);
            $done = 0;
            foreach ($query->all() as $entry) {
                $this->migrateContent($entry, $oldHandle, $newHandle);
                $done++;
                Console::updateProgress($done, $total);
            }
            Console::endProgress();
        }

    }

    private function migrateContent(Entry $entry, $field, $newHandle)
    {
        if ($entry->$field && get_class($entry->$field) === 'ether\seo\models\data\SeoData') {
            /** @var SeoData $oldField */
            $oldField = $entry->$field;
            $newField = new SeoFieldModel();
            $newField->siteId = $entry->siteId;

            $newField->metaTitle = $this->getTitle($oldField);
            $newField->metaDescription = $this->getMarkup($oldField->getDescription());

            /** @var SocialData $facebook */
            $facebook = $oldField->social['facebook'];
            $newField->facebookTitle = $this->removeSeperator($facebook->title);
            $newField->facebookDescription = $this->getMarkup($facebook->description);
            $newField->facebookImage = [(int)$facebook->imageId];

            /** @var SocialData $twitter */
            $twitter = $oldField->social['twitter'];
            $newField->twitterTitle = $this->removeSeperator($twitter->title);
            $newField->twitterDescription = $this->getMarkup($twitter->description);
            $newField->twitterImage = [(int)$twitter->imageId];

            $entry->setFieldValue($newHandle, $newField);

            if (!Craft::$app->getElements()->saveElement($entry)) {
                echo "Error updating '$entry->title'";
            }
        }
    }

    private function getTitle(SeoData $data)
    {
        if (count($data->titleRaw)) {
            $oldTitle = $data->titleRaw[1];
        } else {
            $oldTitle = $this->getMarkup($data->getTitle());
        }
        return $this->removeSeperator($oldTitle);
    }

    private function removeSeperator($title) {
        if ($this->titleSeperator) {
            $str = explode($this->titleSeperator, $title);
            array_pop($str);
            if ($str) {
                return trim(implode($str));
            }
            return $title;
        }
        return $title;
    }

    private function getMarkup($string)
    {
        if ($string instanceof Markup OR (is_object($string) && get_class($string) == 'Twig\Markup')) {
            return !empty($string->__toString()) ? $string->__toString() : null;
        } else {
            return !empty($string) ? $string : null;
        }
    }
}