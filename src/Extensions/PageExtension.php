<?php

namespace Sunnysideup\SunnysideupThemeBackend\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TextField;
use SilverStripe\CMS\Model\SiteTreeExtension;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;

class PageExtension extends SiteTreeExtension
{

    private static $image_dir = 'vendor/sunnysideup/sunnysideup-theme-backend/images';

    protected static $_random_images = null;

    private static $db = [
        'Quote' => 'Varchar',
        'VimeoVideoID' => 'Int',
        'RandomImage' => 'Varchar(255)',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;

        $fields->addFieldToTab(
            'Root.Quote',
            TextField::create('Quote', 'Quote')
        );

        $fields->addFieldToTab(
            'Root.Video',
            TextField::create('VimeoVideoID', 'Vimeo Video ID')
        );

        $fields->addFieldToTab(
            'Root.RandomImage',
            $this->getSelectRandomImageField()
        );

        return $fields;
    }

    protected function getSelectRandomImageField() : VerboseOptionsetField
    {

        $descriptions = [];
        $list = $this->getRandomImages();
        $source = array_combine($list, $list);
        foreach($list as $image) {
            $descriptions[$image] = '<img src="'.$this->getRandomImagesFrontEndFolder() .'/' . $image.'" />';
        }

        return (new VerboseOptionsetField('RandomImage', 'Random Image (optional)'))
            ->setSource($source)
            ->setSourceDescriptions($descriptions);
    }


    public function getRandomImages() :array
    {
        if (self::$_random_images === null) {
            $cache = Injector::inst()->get(CacheInterface::class . '.randomImageCache');
            $files = $cache->get('images');
            if ($files) {
                $files = explode(',', $files);
            }
            if (is_array($files) && count($files)) {
                //do nothing
            } else {
                $files = scandir( $this->getRandomImagesFolderAbsolute()) ?? [];
                foreach ($files as $key => $file) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($ext !== 'jpg') {
                        unset($files[$key]);
                    }
                }
                $cache->set('images', implode(',', $files));
            }
            self::$_random_images = $files;
        }
        return self::$_random_images;
    }

    public function getRandomImagesFolderAbsolute(): string
    {
        return Controller::join_links( Director::baseFolder(),  $this->getRandomImageFolder());
    }

    public function getRandomImagesFrontEndFolder() : string
    {
        return Injector::inst()->get(ResourceURLGenerator::class)->urlForResource($this->getRandomImageFolder());
    }

    public function getRandomImageFolder(): string
    {
        return Config::inst()->get(PageExtension::class, 'image_dir');
    }

}
