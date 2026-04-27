<?php

namespace Sunnysideup\SunnysideupThemeBackend\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Core\Extension;

class PageControllerExtension extends Extension
{
    protected static $_random_images_assigned_to_pages = null;

    public function IsHomePage()
    {
        return $this->getOwner()->URLSegment === 'home';
    }

    public function Siblings()
    {
        if ($this->getOwner()->ParentID) {
            return SiteTree::get()
                ->filter(['ShowInMenus' => 1, 'ParentID' => $this->getOwner()->ParentID])
                ->exclude(['ID' => $this->getOwner()->ID]);
        }
    }


    public function HasQuote(): bool
    {
        if($this->getOwner()->IsHomePage()) {
            return true;
        }

        return trim((string) $this->getOwner()->Quote) !== '';
    }

    public function HasRocketShow(): bool
    {
        return !(bool) $this->getOwner()->NoRocketShow;
    }

    public function HasVideo(): bool
    {
        return $this->getOwner()->VimeoVideoID && $this->HasRocketShow();
    }

    public function MenuChildren()
    {
        return $this->getOwner()->Children()->filter('ShowInMenus', 1);
    }



    public function RandomImage(): string
    {
        $imageName = '';
        if($this->getOwner()->RandomImage && in_array($this->getOwner()->RandomImage, $this->getOwner()->getRandomImages(), true)) {
            $imageName = $this->getOwner()->RandomImage;
        } else {
            $array = $this->getOwner()->getRandomImagesAssignedToPages();
            $pos = isset($_GET['testimg']) ? intval($_GET['testimg']) : $this->getOwner()->ID;

            if(!isset($array[$pos]) && !empty($array)) {
                $pos = array_rand($array);
            }

            if(isset($array[$pos])) {
                $imageName = $array[$pos];
            }
        }

        if($imageName) {
            return Controller::join_links($this->getOwner()->getRandomImagesFrontEndFolder(), $imageName);
        } else {
            return '';
        }
    }

    public function getRandomImagesAssignedToPages(): array
    {
        if (self::$_random_images_assigned_to_pages === null) {
            $files = $this->getOwner()->getRequest()->getSession()->get('randomImages');
            if ($files) {
                $files = unserialize($files);
            }

            if (is_array($files) && count($files)) {
                //do nothing
            } else {
                $files = $this->getOwner()->getRandomImages();
                shuffle($files);
                $files = $this->getOwner()->addSiteTreeIdsToFiles($files);
                $this->getOwner()->getRequest()->getSession()->set('randomImages', serialize($files));
            }

            self::$_random_images_assigned_to_pages = $files;
        }

        return self::$_random_images_assigned_to_pages;
    }


    public function canCachePage(): bool
    {
        return !$this->getOwner()->dataRecord instanceof UserDefinedForm;
    }

    public function onAfterInit()
    {
        if (!empty($_POST['Website'])) {
            die('Sorry, but this looks like spam. Please go back the previous page and try again.');
        }

        if($this->getOwner()->getRequest()->getVar('flush')) {
            $this->getOwner()->getRequest()->getSession()->clear('randomImages');
        }

        // $this->owner->addBasicMetatagRequirements();
        $this->getOwner()->InsertGoogleAnalyticsAsHeadTag();
    }


    public function addSiteTreeIdsToFiles(array $files): array
    {
        $newArray = [];
        if($files !== []) {
            $originalFiles = $files;
            $pageIds = SiteTree::get()->column('ID');
            if(count($pageIds)) {
                foreach($pageIds as $id) {
                    if($files === []) {
                        $files = $originalFiles;
                    }

                    $file = array_pop($files);
                    $newArray[$id] = $file;
                }
            }
        }

        return $newArray;
    }

    public function getRandomImages(): array
    {
        if($this->getOwner() && $this->getOwner()->dataRecord && $this->getOwner()->dataRecord->hasMethod('getRandomImages')) {
            return $this->getOwner()->dataRecord->getRandomImages();
        }

        return [];
    }

    public function getRandomImagesFrontEndFolder(): string
    {
        if($this->getOwner() && $this->getOwner()->dataRecord && $this->getOwner()->dataRecord->hasMethod('getRandomImagesFrontEndFolder')) {
            return $this->getOwner()->dataRecord->getRandomImagesFrontEndFolder();
        }

        return '';
    }
}
