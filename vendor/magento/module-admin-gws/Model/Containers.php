<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Model;

use Magento\Backend\Block\Widget\ContainerInterface;

/**
 * Class Containers
 *
 * Used to remove button controls for restricted admin users that should not have exclusive access to them
 *
 * @api
 * @since 100.0.2
 */
class Containers implements CallbackProcessorInterface
{
    /**
     * @var Role
     */
    protected $_role;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry = null;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page
     */
    protected $cmsPageResource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    protected $categoryResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Role $role
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Cms\Model\ResourceModel\Page $cmsPageResource
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Role $role,
        \Magento\Framework\Registry $registry,
        \Magento\Cms\Model\ResourceModel\Page $cmsPageResource,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_role = $role;
        $this->registry = $registry;
        $this->cmsPageResource = $cmsPageResource;
        $this->categoryResource = $categoryResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Remove control buttons if user does not have exclusive access to current model
     *
     * @param ContainerInterface $container
     * @param string $registryKey
     * @param array $buttons
     * @return void
     */
    private function removeButtonsStoreAccess(ContainerInterface $container, $registryKey, $buttons = [])
    {
        /* @var $model \Magento\Framework\Model\AbstractModel */
        $model = $this->registry->registry($registryKey);
        if ($model) {
            $storeIds = $model->getStoreId();
            if ($model->getId() && !$this->_role->hasExclusiveStoreAccess((array)$storeIds)) {
                foreach ($buttons as $buttonName) {
                    $container->removeButton($buttonName);
                }
            }
        }
    }

    /**
     * Remove customer attribute creation button from grid container
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeAddNewCustomerAttributeButton(ContainerInterface $container)
    {
        if (!$this->_role->getIsAll()) {
            $container->removeButton('add');
        }
    }

    /**
     * Remove customer attribute deletion button from form container
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeDeleteCustomerAttributeButton(ContainerInterface $container)
    {
        if ($this->_role->getIsAll()) {
            $container->removeButton('delete');
        }
    }

    /**
     * Remove product attribute add button
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeCatalogProductAttributeAddButton(ContainerInterface $container)
    {
        $container->removeButton('add');
    }

    /**
     * Remove product attribute save buttons
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeCatalogProductAttributeButtons(ContainerInterface $container)
    {
        $container->removeButton('save');
        $container->removeButton('save_and_edit_button');
        $container->removeButton('delete');
    }

    /**
     * Remove buttons for save and reindex on process edit page.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeProcessEditButtons(ContainerInterface $container)
    {
        $container->removeButton('save');
        $container->removeButton('reindex');
    }

    /**
     * Remove control buttons for website-level roles on Manage Gift Card Accounts page
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeGiftCardAccountAddButton(ContainerInterface $container)
    {
        if (!$this->_role->getIsWebsiteLevel()) {
            $container->removeButton('add');
        }
    }

    /**
     * Remove control buttons for website-level roles on Gift Card Account Edit page
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeGiftCardAccountControlButtons(ContainerInterface $container)
    {
        if (!$this->_role->getIsWebsiteLevel()) {
            $container->removeButton('delete');
            $container->removeButton('save');
            $container->removeButton('send');
        }
    }

    /**
     * Remove buttons from TargetRule grid for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeTargetRuleGridButtons(ContainerInterface $container)
    {
        $container->removeButton('add');
    }

    /**
     * Remove buttons from TargetRule Edit/View for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeTargetRuleEditButtons(ContainerInterface $container)
    {
        $container->removeButton('save');
        $container->removeButton('save_and_continue_edit');
        $container->removeButton('delete');
    }

    /**
     * Remove add button for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeCustomerGroupAddButton(ContainerInterface $container)
    {
        $container->removeButton('add');
    }

    /**
     * Remove control buttons for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeCustomerGroupControlButtons(ContainerInterface $container)
    {
        $container->removeButton('save');
        $container->removeButton('delete');
    }

    /**
     * Remove add button for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeTaxRuleAddButton(ContainerInterface $container)
    {
        $container->removeButton('add');
    }

    /**
     * Remove control buttons for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeTaxRuleControlButtons(ContainerInterface $container)
    {
        $container->removeButton('save');
        $container->removeButton('save_and_continue');
        $container->removeButton('delete');
    }

    /**
     * Remove add button for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeTaxRateAddButton(ContainerInterface $container)
    {
        $container->removeButton('add');
    }

    /**
     * Remove control buttons for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeTaxRateControlButtons(ContainerInterface $container)
    {
        $container->removeButton('save');
        $container->removeButton('delete');
    }

    /**
     * Remove button "Add RMA Attribute" for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeRmaAddAttributeButton(ContainerInterface $container)
    {
        $container->removeButton('add');
    }

    /**
     * Remove rule entity grid buttons for users who does not have any permissions
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeRuleEntityGridButtons(ContainerInterface $container)
    {
        $container->removeButton('apply_rules');
        // Remove "Add" button if role has no allowed website ids
        if (!$this->_role->getWebsiteIds()) {
            $container->removeButton('add');
        }
    }

    /**
     * Remove "Delete Attribute" button for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeRmaDeleteAttributeButton(ContainerInterface $container)
    {
        $container->removeButton('delete');
    }

    /**
     * Restrict customer grid container
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function widgetCustomerGridContainer(ContainerInterface $container)
    {
        if (!$this->_role->getWebsiteIds()) {
            $container->removeButton('add');
        }
    }

    /**
     * Restrict system stores page container
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function widgetManageStores(ContainerInterface $container)
    {
        $container->removeButton('add');
        if (!$this->_role->getWebsiteIds()) {
            $container->removeButton('add_group');
            $container->removeButton('add_store');
        }
    }

    /**
     * Restrict product grid container
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function widgetProductGridContainer(ContainerInterface $container)
    {
        if (!$this->_role->getWebsiteIds()) {
            $container->removeButton('add_new');
        }
    }

    /**
     * Remove buttons from gift wrapping edit form for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeGiftWrappingEditButtons(ContainerInterface $container)
    {
        // Remove delete button
        $container->removeButton('delete');
    }

    /**
     * Remove buttons from rating edit form (in Manage Ratings) for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeRatingEditButtons(ContainerInterface $container)
    {
        // Remove edit buttons
        $container->removeButton('delete');
        $container->removeButton('reset');
        $container->removeButton('save');
    }

    /**
     * Remove Save Hierarchy button if GWS permissions are applicable
     *
     * @param ContainerInterface $container
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function removeCmsHierarchyFormButtons(ContainerInterface $container)
    {
        $websiteId = null;
        $storeId = null;

        if ($container->getRequest()->getParam('website')) {
            if ($website = $this->storeManager->getWebsite($container->getRequest()->getParam('website'))) {
                $websiteId = $website->getId();
            }
        }
        if ($container->getRequest()->getParam('store')) {
            if ($store = $this->storeManager->getStore($container->getRequest()->getParam('store'))) {
                $storeId = $store->getId();
                $websiteId = $store->getWebsite()->getWebsiteId();
            }
        }

        if (!$this->_role->getIsAll()) {
            if (!$this->_role->hasExclusiveAccess([$websiteId]) || $websiteId === null) {
                if (!$this->_role->hasExclusiveStoreAccess([$storeId]) || $storeId === null) {
                    $container->removeButton('save');
                }
            }
        }
    }

    /**
     * Remove control buttons if user does not have exclusive access to current page
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeCmsPageButtons(ContainerInterface $container)
    {
        $this->removeButtonsStoreAccess($container, 'cms_page', ['save', 'saveandcontinue', 'delete']);
    }

    /**
     * Remove control buttons if user does not have exclusive access to current block
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeCmsBlockButtons(ContainerInterface $container)
    {
        $this->removeButtonsStoreAccess($container, 'cms_block', ['save', 'saveandcontinue', 'delete']);
    }

    /**
     * Remove buttons for banner editing if user does not have exclusive access
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function validateBannerPermissions(ContainerInterface $container)
    {
        $this->removeButtonsStoreAccess(
            $container,
            'current_banner',
            ['save', 'save_and_edit_button', 'delete', 'reset']
        );
    }

    /**
     * Remove control buttons if user does not have exclusive access to current reward rate
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeRewardRateButtons(ContainerInterface $container)
    {
        /* @var $model \Magento\Reward\Model\ResourceModel\Reward\Rate */
        $model = $this->registry->registry('current_reward_rate');
        if ($model) {
            if ($model->getId() && !in_array($model->getWebsiteId(), $this->_role->getWebsiteIds())) {
                $container->removeButton('save');
                $container->removeButton('delete');
            }
        }
    }

    /**
     * Remove buttons for widget instance editing if user does not have exclusive access
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeWidgetInstanceButtons(ContainerInterface $container)
    {
        $widgetInstance = $container->getWidgetInstance();
        if ($widgetInstance->getId()) {
            $storeIds = $widgetInstance->getStoreIds();
            if (!$this->_role->hasExclusiveStoreAccess((array)$storeIds)) {
                $container->removeButton('save');
                $container->removeButton('save_and_edit_button');
                $container->removeButton('delete');
            }
        }
    }

    /**
     * Remove fetch button if user doesn't have exclusive access to order
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeSalesTransactionControlButtons(ContainerInterface $container)
    {
        $model = $this->registry->registry('current_transaction');
        if ($model) {
            $websiteId = $model->getOrderWebsiteId();
            if (!$this->_role->hasWebsiteAccess($websiteId, true)) {
                $container->removeButton('fetch');
            }
        }
    }

    /**
     * Remove rule entity edit buttons for users who does not have any permissions or does not have full permissions
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeRuleEntityEditButtons(ContainerInterface $container)
    {
        $controllerName = $container->getRequest()->getControllerName();

        // Determine rule entity object registry key
        switch ($controllerName) {
            case 'promo_catalog':
                $registryKey = 'current_promo_catalog_rule';
                break;
            case 'promo_quote':
                $registryKey = 'current_promo_quote_rule';
                break;
            case 'reminder':
                $registryKey = 'current_reminder_rule';
                break;
            case 'customersegment':
                $registryKey = 'current_customer_segment';
                break;
            default:
                $registryKey = null;
                break;
        }

        if ($registryKey === null) {
            return;
        }

        /** @var $model \Magento\Rule\Model\AbstractModel */
        $model = $this->registry->registry($registryKey);
        if ($model) {
            $websiteIds = $model->getWebsiteIds();
            $container->removeButton('save_apply');
            if ($model->getId() && !$this->_role->hasExclusiveAccess((array)$websiteIds)) {
                $container->removeButton('save');
                $container->removeButton('save_and_continue_edit');
                $container->removeButton('run_now');
                $container->removeButton('match_customers');
                $container->removeButton('delete');
            }
        }
    }

    /**
     * Remove rule entity edit buttons for users who does not have any permissions or does not have full permissions
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeSegmentRuleEntityEditButtons(ContainerInterface $container)
    {
        $registryKey = 'current_customer_segment';
        /** @var $model \Magento\CustomerSegment\Model\Segment */
        $model = $this->registry->registry($registryKey);
        if ($model) {
            $websiteIds = $model->getWebsiteIds();
            $container->removeButton('save_apply');
            if ($model->getId() && !$this->_role->hasExclusiveAccess((array)$websiteIds)) {
                $container->removeButton('save');
                $container->removeButton('save_and_continue_edit');
                $container->removeButton('run_now');
                $container->removeButton('match_customers');
                $container->removeButton('delete');
            }
        }
    }

    /**
     * Remove Revision Button
     *
     * Removing buttons from revision edit page which can't be used
     * by users with limited permissions
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeRevisionEditButtons(ContainerInterface $container)
    {
        /* @var $model \Magento\Cms\Model\Page */
        $model = $this->registry->registry('cms_page');
        if ($model && $model->getId()) {
            $storeIds = $this->cmsPageResource->lookupStoreIds($model->getId());
            if (!$this->_role->hasExclusiveStoreAccess($storeIds)) {
                $container->removeButton('publish');
                $container->removeButton('save_publish');
            }
        }
    }

    /**
     * Remove Publish Button
     *
     * Removing publish button from preview screen to disallow
     * publishing for users with limited permissions
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removePreviewPublishButton(ContainerInterface $container)
    {
        $model = $this->registry->registry('cms_page');
        if ($model && $model->getId()) {
            $storeIds = $this->cmsPageResource->lookupStoreIds($model->getId());
            if (!$this->_role->hasExclusiveStoreAccess($storeIds)) {
                $container->removeButton('publish');
            }
        }
    }

    /**
     * Remove buttons from transactional email template grid for all GWS limited users
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeEmailTemplateGridButtons(ContainerInterface $container)
    {
        $container->removeButton('add');
    }

    /**
     * Remove Transactional Emails edit page control buttons for limited user
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function removeTransactionalEmailsEditButtons(ContainerInterface $container)
    {
        $container->removeButton('save');
        $container->removeButton('delete');
    }

    /**
     * Restrict event grid container
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function widgetCatalogEventGridContainer(ContainerInterface $container)
    {
        if (!$this->_role->getWebsiteIds()) {
            $container->removeButton('add');
        }
    }
}
