<?php

namespace Sunnysideup\SunnysideupThemeBackend\Extensions;

use SilverStripe\Forms\DropdownField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TextField;
use SilverStripe\CMS\Model\SiteTreeExtension;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;

class PageExtension extends SiteTreeExtension
{

    private static $random_image_dir = '/images/';

    private static $db = [
        'Quote' => 'Varchar',
        'VimeoVideoID' => 'Int',
        'RandomImage' => 'Varchar',
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

    protected function getSelectRandomImageField()
    {
        $fields = parent::getCMSFields();

        $descriptions = [];
        $list = $this->getRandomImages();
        $source = array_combine($list, $list);
        foreach($list as $image) {
            $descriptions[$image] = '<img src="'.$this->imageNameToFileName($imageName).'" />';
        }

        $fields->addFieldToTab(
            'Root.Tab',
            (new VerboseOptionsetField('RandomImage', 'Random Image (optional)'))
                ->setSource($source)
                ->setSourceDescriptions($descriptions)
            );
    }

    protected function imageNameToFileName(string $imageName) : string
    {
        return $this->Config()->get('random_image_dir') . '/' . $imageName;
    }

    public function RandomImage() : string
    {
        $imageName = '';
        if($this->RandomImage) {
            $imageName = $this->RandomImage;
        }
        else {
            $array = $this->getRandomImages();
            $pos = array_rand($array);
            $imageName = $array[$pos];
        }
        return $this->imageNameToFileName($imageName);
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
                $files = scandir(Director::baseFolder() . $this->owner->Config()->get('random_image_dir'));
                foreach ($files as $key => $file) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($ext !== 'jpg') {
                        unset($files[$key]);
                    }
                }
                $files = $cache->get('images', implode(',', $files));
            }
            self::$_random_images = $files;
        }
        return self::$_random_images;
    }
}
