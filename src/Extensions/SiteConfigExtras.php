<?php

namespace Sunnysideup\SunnysideupThemeBackend\Extensions;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: SilverStripe\ORM\DataExtension
  * NEW: SilverStripe\Core\Extension ...  (COMPLEX)
  * EXP: Removed deprecated class SilverStripe\\ORM\\DataExtension - subclass SilverStripe\\Core\\Extension instead. See: https://docs.silverstripe.org/en/6/changelogs/6.0.0/
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DB;
use SilverStripe\SiteConfig\SiteConfig;

/**
 *@author nicolaas [at] sunnysideup.co.nz
 *
 *
 **/

class SiteConfigExtras extends DataExtension
{
    private static $db = [
        'CopyrightNotice' => 'Varchar',
        'PhoneNumber' => 'PhoneField',
        'Email' => 'EmailAddress',
    ];

    private static $has_one = [
        'ClimatePositivePage' => Page::class,
        'ShopifyPartnerPage' => Page::class,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.PageElements',
            [
                TextField::create('CopyrightNotice', 'Copyright notice'),
                TextField::create('PhoneNumber', 'Phone Number'),
                EmailField::create('Email', 'Email'),
                TreeDropdownField::create('ClimatePositivePageID', 'Climate Positive Page', SiteTree::class),
                TreeDropdownField::create('ShopifyPartnerPageID', 'Shopify Partner Page', SiteTree::class),
            ]
        );
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    public function requireDefaultRecords()
    {
        $update = [];
        $siteConfig = SiteConfig::current_site_config();

        if (! $siteConfig->CopyrightNotice) {
            $siteConfig->CopyrightNotice =  date('Y') . ' ' . $siteConfig->Title;
            $update[] = 'created default entry for CopyrightNotice';
        }
        if (count($update)) {
            $siteConfig->write();
            DB::alteration_message($siteConfig->ClassName . ' created/updated: ' . implode(' --- ', $update), 'created');
        }
    }
}
