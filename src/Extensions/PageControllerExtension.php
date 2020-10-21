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

    private static $image_dir = 'vendor/sunnysideup/sunnysideup-theme-backend/images';


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

    private static $_random_image = null;

    private static $_random_images = null;

    public function RandomImage() : string
    {
        if (! self::$_random_image) {
            $array = $this->getRandomImagesAssignedToPages();
            if (isset($_GET['testimg'])) {
                $pos = intval($_GET['testimg']);
            } else {
                $pos = $this->owner->ID;
            }
            if(! isset($array[$pos])) {
                $pos = array_rand($array);
            }
            self::$_random_image = Controller::join_links($this->getRandomImagesFrontEndLocation() , $array[$pos]);
        }
        return self::$_random_image;
    }

    public function getRandomImagesAssignedToPages()
    {
        if (self::$_random_images === null) {
            $files = $this->owner->getRequest()->getSession()->get('randomImages');
            if ($files) {
                $files = unserialize($files);
            }
            if (is_array($files) && count($files)) {
                //do nothing
            } else {
                $files = scandir( $this->getRandomImagesFolder()) ?? [];
                foreach ($files as $key => $file) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($ext !== 'jpg') {
                        unset($files[$key]);
                    }
                }
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

    public function getRandomImagesFolder()
    {
        return Controller::join_links( Director::baseFolder() ,  Config::inst()->get(self::class, 'image_dir'));
    }

    public function getRandomImagesFrontEndLocation()
    {
        return Injector::inst()->get(ResourceURLGenerator::class)->urlForResource(Config::inst()->get(self::class, 'image_dir'));
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
