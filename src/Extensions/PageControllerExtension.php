<?php

namespace Sunnysideup\SunnysideupThemeBackend\Extensions;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use SilverStripe\Core\Injector\Injector;

class PageControllerExtension extends Extension
{
    protected static $_random_images_assigned_to_pages = null;

    public function IsHomePage()
    {
        return $this->owner->URLSegment === 'home';
    }

    public function Siblings()
    {
        if ($this->owner->ParentID) {
            return SiteTree::get()
                ->filter(['ShowInMenus' => 1, 'ParentID' => $this->owner->ParentID])
                ->exclude(['ID' => $this->owner->ID]);
        }
    }


    public function HasQuote(): bool
    {
        if($this->owner->IsHomePage()) {
            return true;
        }
        return trim((string) $this->owner->Quote) !== '';
    }

    public function HasRocketShow(): bool
    {
        return $this->owner->NoRocketShow ? false : true;
    }

    public function HasVideo(): bool
    {
        return $this->owner->VimeoVideoID && $this->HasRocketShow();
    }

    public function MenuChildren()
    {
        return $this->owner->Children()->filter('ShowInMenus', 1);
    }



    public function RandomImage(): string
    {
        $imageName = '';
        if($this->owner->RandomImage && in_array($this->owner->RandomImage, $this->owner->getRandomImages(), true)) {
            $imageName = $this->owner->RandomImage;
        } else {
            $array = $this->owner->getRandomImagesAssignedToPages();
            if (isset($_GET['testimg'])) {
                $pos = intval($_GET['testimg']);
            } else {
                $pos = $this->owner->ID;
            }
            if(!isset($array[$pos]) && !empty($array)) {
                $pos = array_rand($array);
            }
            if(isset($array[$pos])) {
                $imageName = $array[$pos];
            }
        }
        if($imageName) {
            return Controller::join_links($this->owner->getRandomImagesFrontEndFolder(), $imageName);
        } else {
            return '';
        }
    }

    public function getRandomImagesAssignedToPages(): array
    {
        if (self::$_random_images_assigned_to_pages === null) {
            $files = $this->owner->getRequest()->getSession()->get('randomImages');
            if ($files) {
                $files = unserialize($files);
            }
            if (is_array($files) && count($files)) {
                //do nothing
            } else {
                $files = $this->owner->getRandomImages();
                shuffle($files);
                $files = $this->owner->addSiteTreeIdsToFiles($files);
                $this->owner->getRequest()->getSession()->set('randomImages', serialize($files));
            }
            self::$_random_images_assigned_to_pages = $files;
        }
        return self::$_random_images_assigned_to_pages;
    }


    public function canCachePage(): bool
    {
        if ($this->owner->dataRecord instanceof UserDefinedForm) {
            return false;
        }
        return true;
    }

    public function onAfterInit()
    {
        if (!empty($_POST['Website'])) {
            die('Sorry, but this looks like spam. Please go back the previous page and try again.');
        }
        if($this->owner->getRequest()->getVar('flush')) {
            $this->owner->getRequest()->getSession()->clear('randomImages');
        }
        // $this->owner->addBasicMetatagRequirements();
        $this->owner->InsertGoogleAnalyticsAsHeadTag();
    }


    public function addSiteTreeIdsToFiles(array $files): array
    {
        $newArray = [];
        if(count($files)) {
            $originalFiles = $files;
            $pageIds = SiteTree::get()->column('ID');
            if(count($pageIds)) {
                foreach($pageIds as $id) {
                    if(empty($files)) {
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
        if($this->owner && $this->owner->dataRecord && $this->owner->dataRecord->hasMethod('getRandomImages')) {
            return $this->owner->dataRecord->getRandomImages();
        }
        return [];
    }

    public function getRandomImagesFrontEndFolder(): string
    {
        if($this->owner && $this->owner->dataRecord && $this->owner->dataRecord->hasMethod('getRandomImagesFrontEndFolder')) {
            return $this->owner->dataRecord->getRandomImagesFrontEndFolder();
        }
        return '';
    }
}
