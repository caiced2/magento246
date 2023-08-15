<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Adminhtml permissions row block
 *
 */
namespace Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Category\AbstractCategory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Store\Model\ResourceModel\Website\Collection as WebsiteCollection;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;

/**
 * @api
 * @since 100.0.2
 */
class Row extends AbstractCategory
{
    /**
     * Index of option for all values
     */
    const FORM_SELECT_ALL_VALUES = -1;

    /**
     * @var string
     */
    protected $_template = 'catalog/category/tab/permissions/row.phtml';

    /**
     * @var GroupCollectionFactory
     */
    protected $_groupCollectionFactory;

    /**
     * @var WebsiteCollectionFactory
     */
    protected $_websiteCollectionFactory;

    /**
     * @param Context $context
     * @param Tree $categoryTree
     * @param Registry $registry
     * @param CategoryFactory $categoryFactory
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Tree $categoryTree,
        Registry $registry,
        CategoryFactory $categoryFactory,
        WebsiteCollectionFactory $websiteCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory,
        array $data = []
    ) {
        $this->_websiteCollectionFactory = $websiteCollectionFactory;
        $this->_groupCollectionFactory = $groupCollectionFactory;
        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $data);
    }

    /**
     * @return AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'delete_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                //'label' => __('Remove Permission'),
                'class' => 'delete action-delete' . ($this->isReadonly() ? ' disabled' : ''),
                'disabled' => $this->isReadonly(),
                'type' => 'button',
                'id' => '<%- data.html_id %>_delete_button'
            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * Check edit by websites
     *
     * @return bool
     */
    public function canEditWebsites()
    {
        return !$this->_storeManager->hasSingleStore();
    }

    /**
     * Check is block readonly
     *
     * @return bool
     */
    public function isReadonly()
    {
        return $this->getCategory()->getPermissionsReadonly();
    }

    /**
     * @return string|int|null
     */
    public function getDefaultWebsiteId()
    {
        return $this->_storeManager->getDefaultStoreView()->getWebsiteId();
    }

    /**
     * Retrieve list of permission grants
     *
     * @return array
     */
    public function getGrants()
    {
        return [
            'grant_catalog_category_view' => __('Browsing Category'),
            'grant_catalog_product_price' => __('Display Product Prices'),
            'grant_checkout_items' => __('Add to Cart')
        ];
    }

    /**
     * Retrieve field class name
     *
     * @param string $fieldId
     * @return string
     */
    public function getFieldClassName($fieldId)
    {
        return strtr($fieldId, '_', '-') . '-value';
    }

    /**
     * Retrieve websites collection
     *
     * @return WebsiteCollection
     */
    public function getWebsiteCollection()
    {
        if (!$this->hasData('website_collection')) {
            $collection = $this->_websiteCollectionFactory->create();
            $this->setData('website_collection', $collection);
        }

        return $this->getData('website_collection');
    }

    /**
     * Retrieve customer group collection
     *
     * @return GroupCollection
     */
    public function getCustomerGroupCollection()
    {
        if (!$this->hasData('customer_group_collection')) {
            $collection = $this->_groupCollectionFactory->create();
            $this->setData('customer_group_collection', $collection);
        }

        return $this->getData('customer_group_collection');
    }

    /**
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    /**
     * @return int
     */
    public function getOptionForSelectAll()
    {
        return self::FORM_SELECT_ALL_VALUES;
    }
}
