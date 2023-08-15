<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\WebsiteRestriction\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class CreateServicePage
 * @package Magento\WebsiteRestriction\Setup\Patch
 */
class CreateServicePage implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * CreateServicePage constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param PageFactory $pageFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PageFactory $pageFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->pageFactory = $pageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $cmsPages = [
            [
                'title' => '503 Service Unavailable',
                'page_layout' => '1column',
                'identifier' => 'service-unavailable',
                'content' => "<div class=\"page-title\"><h1>We're Offline...</h1></div>\r\n"
                    . "<p>...but only for just a bit. We're working to make the Magento Enterprise Demo a better "
                    . "place for you!</p>",
                'is_active' => '1',
                'stores' => [0],
                'sort_order' => 0,
            ],
            [
                'title' => 'Welcome to our Exclusive Online Store',
                'page_layout' => '1column',
                'identifier' => 'private-sales',
                'content' => '<div class="private-sales-index">
        <div class="box">
        <div class="content">
        <h1>Welcome to our Exclusive Online Store</h1>
        <p>If you are a registered member, please <a href="{{store url="customer/account/login"}}">sign in here</a>.</p>
        </div>
        </div>
        </div>',
                'is_active' => '1',
                'stores' => [0],
                'sort_order' => 0
            ],
        ];

        /**
         * Insert default and system pages
         */
        foreach ($cmsPages as $data) {
            $this->pageFactory->create()->setData($data)->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
