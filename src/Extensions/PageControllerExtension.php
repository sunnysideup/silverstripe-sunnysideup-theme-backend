<?php

namespace Sunnysideup\SunnysideupThemeBackend\Extensions;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Core\Extension;

class PageControllerExtension extends ContentController
{


    public function IsHomePage()
    {
        return $this->URLSegment === 'home';
    }

    public function Siblings()
    {
        if ($this->ParentID) {
            return SiteTree::get()
                ->filter(['ShowInMenus' => 1, 'ParentID' => $this->ParentID])
                ->exclude(['ID' => $this->ID]);
        }
    }

    public function MenuChildren()
    {
        return $this->Children()->filter('ShowInMenus', 1);
    }

    public function RandomImage() : string
    {
        return $this->getRandomImage();
    }

    public function canCachePage(): bool
    {
        if ($this->dataRecord instanceof UserDefinedForm) {
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
