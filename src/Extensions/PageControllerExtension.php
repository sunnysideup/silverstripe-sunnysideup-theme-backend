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

    public function MenuChildren()
    {
        return $this->owner->Children()->filter('ShowInMenus', 1);
    }



    public function RandomImage() : string
    {
        $imageName = '';
        $array = $this->getRandomImagesAssignedToPages();
        if($this->owner->RandomImage && in_array($this->owner->RandomImage, $array, true)) {
            $imageName = $this->owner->RandomImage;
        }
        else {
            if (isset($_GET['testimg'])) {
                $pos = intval($_GET['testimg']);
            } else {
                $pos = $this->owner->ID;
            }
            if(! isset($array[$pos])) {
                $pos = array_rand($array);
            }
            $imageName = $array[$pos];
        }
        return Controller::join_links($this->getRandomImagesFrontEndFolder() , $imageName);
    }

    public function getRandomImagesAssignedToPages() : array
    {
        if (self::$_random_images === null) {
            $files = $this->owner->getRequest()->getSession()->get('randomImages');
            if ($files) {
                $files = unserialize($files);
            }
            if (is_array($files) && count($files)) {
                //do nothing
            } else {
                $files = $this->getRandomImages();
                shuffle($files);
                $files = $this->addSiteTreeIdsToFiles($files);
                $this->owner->getRequest()->getSession()->set('randomImages', serialize($files));
            }
            self::$_random_images = $files;
        }
        return self::$_random_images;
    }

    protected function addSiteTreeIdsToFiles(array $files) : array
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


    public function canCachePage(): bool
    {
        if ($this->owner->dataRecord instanceof UserDefinedForm) {
            return false;
        }
        return true;
    }

    protected function init()
    {
        parent::init();
        if (! empty($_POST['Website'])) {
            die('Sorry, but this looks like spam. Please go back the previous page and try again.');
        }
        $this->addBasicMetatagRequirements();
        $this->InsertGoogleAnalyticsAsHeadTag();
    }
}
