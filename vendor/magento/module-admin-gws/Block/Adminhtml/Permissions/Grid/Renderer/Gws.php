<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Block\Adminhtml\Permissions\Grid\Renderer;

/**
 * Website permissions column grid
 *
 */
class Gws extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var array
     */
    public static $websites = [];

    /**
     * @var \Magento\Store\Model\ResourceModel\Group\CollectionFactory
     */
    private $storeGroupCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Store\Model\ResourceModel\Group\CollectionFactory $storeGroupCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Store\Model\ResourceModel\Group\CollectionFactory $storeGroupCollectionFactory,
        array $data = []
    ) {
        $this->storeGroupCollectionFactory = $storeGroupCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Render cell contents
     *
     * Looks on the following data in the $row:
     * - is_all_permissions - bool
     * - website_ids - string, comma-separated
     * - store_group_ids - string, comma-separated
     *
     * @param \Magento\Framework\DataObject $row
     * @return string|\Magento\Framework\Phrase
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getData('gws_is_all')) {
            return __('All');
        }

        // lookup websites and store groups in system
        if (!self::$websites) {
            $storeGroupCollection = $this->storeGroupCollectionFactory->create();
            foreach ($storeGroupCollection as $storeGroup) {
                /* @var $storeGroup \Magento\Store\Model\Group */
                $website = $storeGroup->getWebsite();
                $websiteId = (string)$storeGroup->getWebsiteId();
                self::$websites[$websiteId]['name'] = $website->getName();
                self::$websites[$websiteId][(int)$storeGroup->getId()] = $storeGroup->getName();
            }
        }

        // analyze current row values
        $storeGroupIds = [];
        if ($websiteIds = $row->getData('gws_websites')) {
            $websiteIds = !is_array($websiteIds) ? explode(',', $websiteIds) : $websiteIds;
            foreach (self::$websites as $websiteId => $website) {
                if (in_array($websiteId, $websiteIds)) {
                    unset($website['name']);
                    $storeGroupIds = array_merge($storeGroupIds, array_keys($website));
                }
            }
        } else {
            $websiteIds = [];
            if ($ids = $row->getData('gws_store_groups')) {
                $storeGroupIds = explode(',', $ids);
            }
        }

        // walk through all websties and store groups and draw them
        $output = [];
        foreach (self::$websites as $websiteId => $website) {
            $isWebsite = in_array($websiteId, $websiteIds);
            // show only if something from this website is relevant
            if ($isWebsite || count(array_intersect(array_keys($website), $storeGroupIds))) {
                $output[] = $this->_formatName($website['name'], false, $isWebsite);
                foreach ($website as $storeGroupId => $storeGroupName) {
                    if (is_numeric($storeGroupId) && in_array($storeGroupId, $storeGroupIds)) {
                        $output[] = $this->_formatName($storeGroupName, true);
                    }
                }
            }
        }
        return $output ? implode('<br />', $output) : __('None');
    }

    /**
     * Format a name in cell
     *
     * @param string $name
     * @param bool $isStoreGroup
     * @param bool $isActive
     * @return string
     */
    protected function _formatName($name, $isStoreGroup = false, $isActive = true)
    {
        return '<span style="' .
            (!$isActive ? 'color:#999;text-decoration:line-through;' : '') .
            ($isStoreGroup ? 'padding-left:2em;' : '') .
            '">' .
            str_replace(
                ' ',
                '&nbsp;',
                $name
            ) . '</span>';
    }
}
