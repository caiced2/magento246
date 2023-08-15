<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model;

use Magento\Framework\Event\Observer as EventObserver;

/**
 * Blocks assess limiter
 *
 * @api
 * @since 100.0.2
 */
class Blocks extends \Magento\AdminGws\Model\Observer\AbstractObserver implements CallbackProcessorInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    protected $categoryResource;

    /**
     * @param \Magento\AdminGws\Model\Role $role
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     */
    public function __construct(
        \Magento\AdminGws\Model\Role $role,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource
    ) {
        $this->categoryResource = $categoryResource;
        parent::__construct($role);
    }

    /**
     * Check whether category can be moved
     *
     * @param EventObserver $observer
     * @return void
     */
    public function catalogCategoryIsMoveable($observer)
    {
        if ($this->_role->getIsAll()) {
            // because observer is passed through directly
            return;
        }
        $category = $observer->getEvent()->getOptions()->getCategory();
        if (!$this->_role->hasExclusiveCategoryAccess($category->getData('path'))) {
            $observer->getEvent()->getOptions()->setIsMoveable(false);
        }
    }

    /**
     * Check whether sub category can be added
     *
     * @param EventObserver $observer
     * @return void
     */
    public function catalogCategoryCanBeAdded($observer)
    {
        if ($this->_role->getIsAll()) {
            // because observer is passed through directly
            return;
        }

        $category = $observer->getEvent()->getCategory();
        /*
         * we can do checking only if we have current category
         */
        if ($category) {
            $categoryPath = $category->getPath();
            /*
             * If admin user has exclusive access to current category
             * he can add sub categories to it
             */
            if ($this->_role->hasExclusiveCategoryAccess($categoryPath)) {
                $observer->getEvent()->getOptions()->setIsAllow(true);
            } else {
                $observer->getEvent()->getOptions()->setIsAllow(false);
            }
        }
    }

    /**
     * Check whether root category can be added
     *
     * Note: only user with full access can add root categories
     *
     * @param EventObserver $observer
     * @return void
     */
    public function catalogRootCategoryCanBeAdded($observer)
    {
        if ($this->_role->getIsAll()) {
            // because observer is passed through directly
            return;
        }

        //if user has website or store restrictions he can't add root category
        $observer->getEvent()->getOptions()->setIsAllow(false);
    }

    /**
     * Disable fields in tab "Main" of edit product attribute form
     *
     * @param EventObserver $observer
     * @return void
     */
    public function disableCatalogProductAttributeEditTabMainFields($observer)
    {
        foreach ($observer->getEvent()->getBlock()->getForm()->getElements() as $element) {
            if ($element->getType() == 'fieldset') {
                foreach ($element->getElements() as $field) {
                    $field->setReadonly(true);
                    $field->setDisabled(true);
                }
            }
        }
    }

    /**
     * Disable fields in tab "Manage Label / Options" of edit product attribute form
     *
     * @param EventObserver $observer
     * @return void
     */
    public function disableCatalogProductAttributeEditTabOptionsFields($observer)
    {
        $observer->getEvent()->getBlock()->setReadOnly(true);
    }

    /**
     * Remove product attribute create button on product edit page
     *
     * @param EventObserver $observer
     * @return void
     */
    public function disallowCreateAttributeButtonDisplay($observer)
    {
        if ($this->_role->getIsAll()) {
            // because observer is passed through directly
            return;
        }

        $observer->getEvent()->getBlock()->setCanShow(false);
    }

    /**
     * Remove attribute set management buttons on attribute set edit page
     *
     * @param EventObserver $observer
     * @return void
     */
    public function removeAttributeSetControls($observer)
    {
        if ($this->_role->getIsAll()) {
            // because observer is passed through directly
            return;
        }

        $block = $observer->getEvent()->getBlock();

        $block->unsetChild('add_group_button');
        $block->unsetChild('delete_group_button');
        $block->unsetChild('save_button');
        $block->unsetChild('delete_button');
        $block->unsetChild('rename_button');

        $block->setIsReadOnly(true);
    }

    /**
     * Remove attribute set creation button on attribute set listing page
     *
     * @param EventObserver $observer
     * @return void
     */
    public function removeAddNewAttributeSetButton($observer)
    {
        if ($this->_role->getIsAll()) {
            // because observer is passed through directly
            return;
        }

        $block = $observer->getEvent()->getBlock();

        $block->unsetChild('addButton');
    }

    /**
     * Disables "Display Countdown Ticker On" checkboxes if user have not enough rights
     *
     * @param EventObserver $observer
     * @return void
     */
    public function restrictCatalogEventEditForm($observer)
    {
        if ($this->_role->getIsAll()) {
            return;
        }
        $setDisabled = false;
        if (!$this->_role->getIsWebsiteLevel()) {
            $setDisabled = true;
        } else {
            $categoryId = $observer->getEvent()->getBlock()->getEvent()->getCategoryId();
            $path = $this->categoryResource->getCategoryPathById($categoryId);
            if (!$this->_role->hasExclusiveCategoryAccess($path)) {
                $setDisabled = true;
            }
        }
        if ($setDisabled) {
            $element = $observer->getEvent()->getBlock()->getForm()->getElement('display_state_array');
            $element->setDisabled(
                [
                    \Magento\CatalogEvent\Model\Event::DISPLAY_CATEGORY_PAGE,
                    \Magento\CatalogEvent\Model\Event::DISPLAY_PRODUCT_PAGE,
                ]
            );
        }
    }

    /**
     * Set required Subscribers From field in newsletter queue form
     *
     * @param EventObserver $observer
     * @return void
     */
    public function setIsRequiredSubscribersFromFieldForNewsletterQueueForm($observer)
    {
        $observer->getEvent()->getBlock()->getForm()->getElement(
            'stores'
        )->setRequired(
            true
        )->addClass(
            'required-entry'
        );
    }

    /**
     * Set websites readonly flag for store-level users on mass update attributes
     *
     * @param EventObserver $observer
     * @return void
     */
    public function catalogProductMassUpdateWebsites($observer)
    {
        $observer->getEvent()->getBlock()->setWebsitesReadonly(!$this->_role->getIsWebsiteLevel());
    }

    /**
     * Remove 'delete' button for store-level roles on Catalog Product page
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function catalogProductPrepareMassaction($observer)
    {
        if ($this->_role->getIsStoreLevel()) {
            $massBlock = $observer->getEvent()->getBlock()->getMassactionBlock();
            /* @var $massBlock \Magento\Backend\Block\Widget\Grid\Massaction */
            if ($massBlock) {
                $massBlock->removeItem('delete');
            }
        }

        return $this;
    }

    /**
     * Remove 'delete' action from Gift Wrapping grid for all GWS limited users
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function removeGiftWrappingForbiddenMassactions($observer)
    {
        $massBlock = $observer->getEvent()->getBlock()->getMassactionBlock();
        /** @var $massBlock \Magento\Backend\Block\Widget\Grid\Massaction */
        if ($massBlock) {
            $massBlock->removeItem('delete');
        }
        return $this;
    }

    /**
     * Remove action column and massaction functionality from grid for users with limited permissions.
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function removeProcessListButtons($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $block->setMassactionIdField(false);
        $column = $block->getColumn('action');
        if ($column) {
            $column->setActions([]);
        }

        return $this;
    }

    /**
     * Removing not allowed massactions for user with store level permissions.
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function removeNotAllowedMassactionsForOrders($observer)
    {
        if ($this->_role->getIsWebsiteLevel()) {
            return $this;
        }
        $massBlock = $observer->getEvent()->getBlock()->getMassactionBlock();
        /* @var $massBlock \Magento\Backend\Block\Widget\Grid\Massaction */
        if ($massBlock) {
            $massBlock->removeItem('cancel_order')->removeItem('hold_order')->removeItem('unhold_order');
        }

        return $this;
    }

    /**
     * Remove control buttons for limited user on Manage Currency Rates
     *
     * @param EventObserver $observer
     * @return void
     */
    public function removeManageCurrencyRatesButtons($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block) {
            $block->unsetChild('save_button')->unsetChild('import_button')->unsetChild('import_services');
        }
    }

    /**
     * Disable Rule-based Settings for all GWS limited users
     *
     * @param EventObserver $observer
     * @return void
     */
    public function readonlyTargetRuleProductAttribute($observer)
    {
        /* @var $block \Magento\TargetRule\Block\Adminhtml\Product */
        $block = $observer->getEvent()->getBlock();
        if ($block) {
            $access = $this->_role->hasWebsiteAccess($block->getProduct()->getWebsiteIds(), true);
            if (!$block->getProduct()->isObjectNew() && !$access || $block->getProduct()->isReadonly()) {
                $block->setIsReadonly(true);
            }
        }
    }

    /**
     * Validate permissions for Catalog Permission tab Settings for all GWS limited users
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function validateCatalogPermissions($observer)
    {
        /* @var $block \Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions */
        $block = $observer->getEvent()->getBlock();
        if ($block) {
            /* @var $row \Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions\Row */
            $row = $block->getChildBlock('row');
            if ($this->_role->getIsWebsiteLevel()) {
                $websiteIds = $this->_role->getWebsiteIds();
                $block->setAdditionConfigData(['limited_website_ids' => $websiteIds]);
            } elseif ($this->_role->getIsStoreLevel()) {
                $block->getCategory()->setPermissionsReadonly(true);
                $addButton = $block->getChildBlock('add_button');
                if ($addButton) {
                    $addButton->setDisabled(true)->setClass($addButton->getClass() . ' disabled');
                }
                if ($row) {
                    $deleteButton = $row->getChildBlock('delete_button');
                    if ($deleteButton) {
                        $addButton->setDisabled(true)->setClass($deleteButton->getClass() . ' disabled');
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Validate permissions for Banner Content tab for all GWS limited users
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function disableAllStoreViewsContentFeild($observer)
    {
        $model = $observer->getEvent()->getModel();
        if (!$this->_role->getIsAll() && $model) {
            $model->setCanSaveAllStoreViewsContent(false);
        }
        return $this;
    }

    /**
     * Add append restriction flag to hierarchy nodes
     *
     * @param EventObserver $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareCmsHierarchyNodes($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $nodes = $block->getNodes();
        if ($nodes) {
            if (is_array($nodes)) {
                $nodesAssoc = [];
                foreach ($nodes as $node) {
                    $nodesAssoc[$node['node_id']] = $node;
                }

                foreach ($nodesAssoc as $nodeId => $node) {
                    // define parent page/node
                    $parent = isset(
                        $nodesAssoc[$node['parent_node_id']]
                    ) ? $nodesAssoc[$node['parent_node_id']] : null;
                    $parentDenied = $parent !== null && isset(
                        $parent['append_denied']
                    ) && $parent['append_denied'] === true;

                    // If appending is denied for parent - deny it for child
                    if ($parentDenied || !$node['page_id']) {
                        $nodesAssoc[$nodeId]['append_denied'] = $parentDenied;
                    } else {
                        $nodesAssoc[$nodeId]['append_denied'] = !$this->_role->hasStoreAccess(
                            $node['assigned_to_stores']
                        );
                    }
                }
                $block->setNodes(array_values($nodesAssoc));
            }
        }

        return $this;
    }

    /**
     * Remove Import possibility for all GWS limited users
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function removeTaxRateImport($observer)
    {
        $observer->getEvent()->getBlock()->setIsReadonly(true);
        return $this;
    }

    /**
     * Disable "Delete Attribute Option" Button for all GWS limited users
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function disableRmaAttributeDeleteOptionButton($observer)
    {
        $deleteButton = $observer->getEvent()->getBlock()->getChildBlock('delete_button');

        if ($deleteButton) {
            $deleteButton->setDisabled(true);
        }

        return $this;
    }

    /**
     * Disable tax class and rate editable multiselects on the "Manage Tax Rule" page
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function disableTaxRelatedMultiselects($observer)
    {
        /**
         * @var $form \Magento\Framework\Data\Form
         */
        $form = $observer->getEvent()->getBlock()->getForm();
        $form->getElement('tax_customer_class')->setDisabled(true);
        $form->getElement('tax_product_class')->setDisabled(true);
        $form->getElement('tax_rate')->setDisabled(true);

        return $this;
    }
}
