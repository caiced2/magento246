<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model;

use Magento\AdminGws\Model\Observer\AbstractObserver;
use Magento\AdminGws\Model\ResourceModel\CollectionsFactory;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogEvent\Model\Event;
use Magento\CheckoutAgreements\Model\Agreement;
use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\GiftRegistry\Model\Entity;
use Magento\Reminder\Model\Rule;
use Magento\Review\Model\Review;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlRewrite;

/**
 * Controllers AdminGws validator
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Controllers extends AbstractObserver implements CallbackProcessorInterface
{
    /**#@+
     * Actions
     */
    const ACTION_DENIED = 'denied';
    const ACTION_NEW = 'new';
    const ACTION_GENERATE = 'generate';
    const ACTION_EDIT = 'edit';
    const ACTION_ADD = 'add';
    const ACTION_SAVE = 'save';
    const ACTION_FETCH_RATES = 'fetchrates';
    const ACTION_SAVE_RATES = 'saverates';
    const ACTION_RUN = 'run';
    const ACTION_MATCH = 'match';
    const ACTION_DELETE = 'delete';
    const ACTION_NEW_WEBSITE = 'newwebsite';
    const ACTION_NEW_GROUP = 'newgroup';
    const ACTION_NEW_STORE = 'newstore';
    const ACTION_EDIT_WEBSITE = 'editwebsite';
    const ACTION_EDIT_GROUP = 'editgroup';
    const ACTION_EDIT_STORE = 'editstore';
    const ACTION_DELETE_WEBSITE = 'deletewebsite';
    const ACTION_DELETE_WEBSITE_POST = 'deletewebsitepost';
    const ACTION_DELETE_GROUP = 'deletegroup';
    const ACTION_DELETE_GROUP_POST = 'deletegrouppost';
    const ACTION_DELETE_STORE = 'deletestore';
    const ACTION_DELETE_STORE_POST = 'deletestorepost';
    const ACTION_DUPLICATE = 'duplicate';
    /**#@-*/

    /**#@-*/
    protected $_request;

    /**
     * @var bool
     */
    protected $_isForwarded = false;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_registry = null;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager = null;

    /**
     * @var ResponseInterface
     */
    protected $_response = null;

    /**
     * @var UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var Session
     */
    protected $_backendSession;

    /**
     * @var CollectionsFactory
     */
    protected $_collectionsFactory;

    /**
     * @var ProductFactory
     */
    protected $_productFactoryRes;

    /**
     * @var ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param Role $role
     * @param UrlInterface $backendUrl
     * @param Session $backendSession
     * @param CollectionsFactory $collectionsFactory
     * @param ProductFactory $productFactoryRes
     * @param Registry $registry
     * @param RequestInterface $request
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param ResponseInterface $response
     * @param ActionFlag $actionFlag
     * @param ManagerInterface $messageManager
     * @param CategoryRepositoryInterface $categoryRepository
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Role $role,
        UrlInterface $backendUrl,
        Session $backendSession,
        CollectionsFactory $collectionsFactory,
        ProductFactory $productFactoryRes,
        Registry $registry,
        RequestInterface $request,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ResponseInterface $response,
        ActionFlag $actionFlag,
        ManagerInterface $messageManager,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->_registry = $registry;
        $this->_backendUrl = $backendUrl;
        $this->_backendSession = $backendSession;
        $this->_collectionsFactory = $collectionsFactory;
        $this->_productFactoryRes = $productFactoryRes;
        $this->_actionFlag = $actionFlag;
        $this->_objectManager = $objectManager;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->_response = $response;
        $this->messageManager = $messageManager;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($role);
    }

    /**
     * Check user has requested scope access.
     *
     * @return bool
     */
    private function hasRequestedScopeAccess()
    {
        // allow specific store view scope
        $storeCode = $this->_request->getParam('store');
        if ($storeCode) {
            $store = $this->_storeManager->getStore($storeCode);
            if ($store) {
                if ($this->_role->hasStoreAccess($store->getId())) {
                    return true;
                }
            }
        } elseif ($websiteCode = $this->_request->getParam('website')) {
            try {
                $website = $this->_storeManager->getWebsite($websiteCode);
                if ($website) {
                    if ($this->_role->hasWebsiteAccess($website->getId(), true)) {
                        return true;
                    }
                }
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
            } catch (LocalizedException $e) {
                // redirect later from non-existing website
            }
        }
        return false;
    }

    /**
     * Make sure the System Configuration pages are used in proper scopes
     *
     * @return void
     */
    public function validateSystemConfig()
    {
        if ($this->hasRequestedScopeAccess()) {
            return;
        }

        $redirectPath = 'adminhtml/system_config/edit';

        $section = $this->_request->getParam('section');
        if ($section) {
            $redirectPath .= '/section/' . $section;
        }

        // redirect to first allowed website or store scope
        if ($this->_role->getWebsiteIds()) {
            $this->_redirect(
                $this->_backendUrl->getUrl(
                    $redirectPath,
                    ['website' => $this->getAnyStoreView()->getWebsite()->getCode()]
                )
            );
            return;
        }

        $store = $this->getAnyAccessibleStoreView();
        if ($store instanceof StoreInterface) {
            $this->_redirect(
                $this->_backendUrl->getUrl(
                    $redirectPath,
                    ['store' => $store->getId()]
                )
            );
        } else {
            $this->_redirect('admin/noroute');
        }
    }

    /**
     * Get either default or any store view
     *
     * @return Store|null
     */
    protected function getAnyStoreView()
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($store) {
            return $store;
        }
        foreach ($this->_storeManager->getStores() as $store) {
            return $store;
        }
        return null;
    }

    /**
     * Get any allowed store view or default one.
     *
     * @return StoreInterface|null
     */
    private function getAnyAccessibleStoreView()
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($store && $this->_role->hasStoreAccess($store->getId())) {
            return $store;
        }
        foreach ($this->_storeManager->getStores() as $store) {
            if ($this->_role->hasStoreAccess($store->getId())) {
                return $store;
            }
        }
        return null;
    }

    /**
     * Validate misc catalog product requests
     *
     * @return void
     */
    public function validateCatalogProduct()
    {
        if (!$this->validateNoWebsiteGeneric([self::ACTION_NEW, self::ACTION_DELETE, self::ACTION_DUPLICATE])) {
            return;
        }
    }

    /**
     * Validate catalog product edit page
     *
     * @return void
     */
    public function validateCatalogProductEdit()
    {
        // redirect from disallowed scope
        if (!$this->_isAllowedStoreInRequest()) {
            $this->_redirect(['*/*/*', 'id' => $this->_request->getParam('id')]);
        }
    }

    /**
     * Validate catalog product review save, edit action
     *
     * @return void
     */
    public function validateCatalogProductReview()
    {
        $reviewStores = $this->_objectManager->create(
            Review::class
        )->load(
            $this->_request->getParam('id')
        )->getStores();

        $storeIds = $this->_role->getStoreIds();

        $allowedIds = array_intersect($reviewStores, $storeIds);
        if (empty($allowedIds)) {
            $this->_redirect();
        }
    }

    /**
     * Validate catalog product massStatus
     *
     * @return void
     */
    public function validateCatalogProductMassActions()
    {
        if ($this->_role->getIsAll()) {
            return;
        }

        $store = $this->_storeManager->getStore(
            $this->_request->getParam('store', Store::DEFAULT_STORE_ID)
        );
        if (!$this->_role->hasStoreAccess($store->getId())) {
            $this->_forward();
        }
    }

    /**
     * Avoid viewing disallowed customer
     *
     * @return void
     */
    public function validateCustomerEdit()
    {
        $customerId = $this->_request->getParam('id');
        if ($customerId && !$this->isThereAccessToCustomer((int)$customerId)) {
            $this->_redirect('admin/denied');
        }
    }

    /**
     * Avoid viewing disallowed customer balance
     *
     * @return void
     */
    public function validateCustomerbalance()
    {
        if (!($id = $this->_request->getParam('id'))) {
            $this->_forward();
            return;
        }
        $customer = $this->_objectManager->create(Customer::class)->load($id);
        if (!$customer->getId() || !in_array($customer->getWebsiteId(), $this->_role->getRelevantWebsiteIds())) {
            $this->_forward();
        }
    }

    /**
     * Disallow submitting gift cards without website-level permissions
     *
     * @param Action $controller
     * @return void
     */
    public function validateGiftCardAccount($controller)
    {
        $controller->setShowCodePoolStatusMessage(false);
        if (!$this->_role->getIsWebsiteLevel()) {
            $action = $this->getActionName();
            if (in_array($action, [self::ACTION_NEW, self::ACTION_GENERATE])
                || $action == self::ACTION_EDIT && !$this->_request->getParam('id')
            ) {
                $this->_forward();
            }
        }
    }

    /**
     * Prevent viewing wrong categories and creation pages
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateCatalogCategories()
    {
        $forward = false;
        switch ($this->getActionName()) {
            case self::ACTION_ADD:
                /**
                 * adding is not allowed from beginning if user has scope specified permissions
                 */
                $forward = true;
                $parentId = $this->_request->getParam('parent');
                if ($parentId) {
                    $forward = !$this->_validateCatalogSubCategoryAddPermission($parentId);
                }
                break;
            case self::ACTION_EDIT:
                if (!$this->_request->getParam('id')) {
                    $parentId = $this->_request->getParam('parent');
                    if ($parentId) {
                        $forward = !$this->_validateCatalogSubCategoryAddPermission($parentId);
                    }
                } else {
                    try {
                        $category = $this->categoryRepository->get($this->_request->getParam('id'));
                    } catch (NoSuchEntityException $e) {
                        $category = null;
                    }
                    if (!$category) {
                        // no viewing wrong categories
                        $forward = true;
                    }
                }
                break;
        }

        // redirect to first allowed root category
        if ($forward) {
            $firstRootId = current(array_keys($this->_role->getAllowedRootCategories()));
            if (count($this->_role->getAllowedRootCategories()) > 0 && $firstRootId) {
                   $this->_redirect(['*/*/*', 'id' => $firstRootId]);
            } else {
                $this->_forward();
            }
        }
    }

    /**
     * Disallow viewing categories in disallowed scopes
     *
     * @param Action $controller
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateCatalogCategoryView($controller) // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
    }

    /**
     * Disallow submitting catalog event in wrong scope
     *
     * @return void
     */
    public function validateCatalogEvents()
    {
        // instead of generic (we are capped by allowed store groups root categories)
        // check whether attempting to create event for wrong category
        if (self::ACTION_NEW === $this->getActionName()) {
            $categoryId = $this->_request->getParam('category_id');
            if (!$categoryId) {
                $this->_forward();
                return;
            }

            try {
                $category = $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
                $this->_forward();
                return;
            }

            if (!$this->_isCategoryAllowed($category) || !$this->_role->getIsWebsiteLevel()) {
                $this->_forward();
                return;
            }
        }
    }

    /**
     * Disallow viewing wrong catalog events or viewing them in disallowed scope
     *
     * @return void
     */
    public function validateCatalogEventEdit()
    {
        if (!$this->_request->getParam('id') && $this->_role->getIsWebsiteLevel()) {
            return;
        }

        // avoid viewing disallowed events
        $catalogEvent = $this->_objectManager->create(
            Event::class
        )->load(
            $this->_request->getParam('id')
        );

        try {
            $category = $this->categoryRepository->get($catalogEvent->getCategoryId());
        } catch (NoSuchEntityException $e) {
            $this->_forward();
            return;
        }

        if (!$this->_isCategoryAllowed($category)) {
            $this->_forward();
            return;
        }

        // redirect from disallowed store scope
        if (!$this->_isAllowedStoreInRequest()) {
            $this->_redirect(
                [
                    '*/*/*',
                    'store' => $this->getAnyStoreView()->getId(),
                    'id' => $catalogEvent->getId(),
                ]
            );
        }
    }

    /**
     * Disallow any creation order activity, if there is no website-level access
     *
     * @return void
     */
    public function validateSalesOrderCreation()
    {
        if (!$this->_role->getWebsiteIds()) {
            $this->_forward();
        }

        // check whether there is disallowed website in request?
    }

    // TODO allow viewing sales information only from allowed websites

    /**
     * Don't allow to create or delete entity, if there is no website permissions
     *
     * Returns false if disallowed
     *
     * @param string|array $denyActions
     * @param string $saveAction
     * @param string $idFieldName
     * @return bool
     */
    public function validateNoWebsiteGeneric(
        $denyActions = [self::ACTION_NEW, self::ACTION_DELETE],
        $saveAction = self::ACTION_SAVE,
        $idFieldName = 'id'
    ) {
        if (!is_array($denyActions)) {
            $denyActions = [$denyActions];
        }
        if (!$this->_role->getWebsiteIds() && (in_array($this->_request->getActionName(), $denyActions)
            || $saveAction === $this->_request->getActionName() && 0 == $this->_request->getParam($idFieldName))
        ) {
            $this->_forward();
            return false;
        }
        return true;
    }

    /**
     * Validate Manage Stores pages actions
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateSystemStore()
    {
        // due to design of the original controller, need to run this check only once, on the first dispatch
        if ($this->_registry->registry('magento_admingws_system_store_matched')) {
            return;
        } elseif (in_array(
            $this->getActionName(),
            [
                self::ACTION_SAVE,
                self::ACTION_NEW_WEBSITE,
                self::ACTION_NEW_GROUP,
                self::ACTION_NEW_STORE,
                self::ACTION_EDIT_WEBSITE,
                self::ACTION_EDIT_GROUP,
                self::ACTION_EDIT_STORE,
                self::ACTION_DELETE_WEBSITE,
                self::ACTION_DELETE_WEBSITE_POST,
                self::ACTION_DELETE_GROUP,
                self::ACTION_DELETE_GROUP_POST,
                self::ACTION_DELETE_STORE,
                self::ACTION_DELETE_STORE_POST
            ]
        )
        ) {
            $this->_registry->register('magento_admingws_system_store_matched', true, true);
        }

        switch ($this->getActionName()) {
            case self::ACTION_SAVE:
                $params = $this->_request->getParams();
                if (isset($params['website'])) {
                    $this->_forward();
                } elseif (isset($params['store']) || isset($params['group'])) {
                    if (!$this->_role->getWebsiteIds()) {
                        $this->_forward();
                    }
                    // preventing saving stores/groups for wrong website is handled by their models
                }
                break;
            case self::ACTION_NEW_WEBSITE:
                $this->_forward();
                break;
            case self::ACTION_NEW_GROUP:
                // break intentionally omitted
            case self::ACTION_NEW_STORE:
                if (!$this->_role->getWebsiteIds()) {
                    $this->_forward();
                }
                break;
            case self::ACTION_EDIT_WEBSITE:
                if (!$this->_role->hasWebsiteAccess($this->_request->getParam('website_id'))) {
                    $this->_forward();
                }
                break;
            case self::ACTION_EDIT_GROUP:
                if (!$this->_role->hasStoreGroupAccess($this->_request->getParam('group_id'))) {
                    $this->_forward();
                }
                break;
            case self::ACTION_EDIT_STORE:
                if (!$this->_role->hasStoreAccess($this->_request->getParam('store_id'))) {
                    $this->_forward();
                }
                break;
            case self::ACTION_DELETE_WEBSITE:
                // break intentionally omitted
            case self::ACTION_DELETE_WEBSITE_POST:
                $this->_forward();
                break;
            case self::ACTION_DELETE_GROUP:
                // break intentionally omitted
            case self::ACTION_DELETE_GROUP_POST:
                $group = $this->_role->getGroup($this->_request->getParam('item_id'));
                if ($group) {
                    if ($this->_role->hasWebsiteAccess($group->getWebsiteId(), true)) {
                        return;
                    }
                }
                $this->_forward();
                break;
            case self::ACTION_DELETE_STORE:
                // break intentionally omitted
            case self::ACTION_DELETE_STORE_POST:
                $store = $this->_storeManager->getStore($this->_request->getParam('item_id'));
                if ($store) {
                    if ($this->_role->hasWebsiteAccess($store->getWebsiteId(), true)) {
                        return;
                    }
                }
                $this->_forward();
                break;
        }
    }

    /**
     * Redirect to a specific page
     *
     * @param array|string $url
     * @return void
     */
    protected function _redirect($url = null)
    {
        $this->_actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        if (null === $url) {
            $url = $this->_backendUrl->getUrl('adminhtml/noroute');
        } elseif (is_array($url)) {
            $url = $this->_backendUrl->getUrl(array_shift($url), $url);
        } elseif (false === strpos($url, 'http', 0)) {
            $url = $this->_backendUrl->getUrl($url);
        }
        $this->_response->setRedirect($url);
    }

    /**
     * Forward current request
     *
     * @param string $action
     * @param string $module
     * @param string $controller
     * @return void
     */
    protected function _forward($action = self::ACTION_DENIED, $module = null, $controller = null)
    {
        // avoid cycling
        if ($this->getActionName() === $action && (null === $module ||
                $this->_request->getModuleName() === $module) && (null === $controller ||
                $this->_request->getControllerName() === $controller)
        ) {
            return;
        }

        $this->_request->initForward();

        if ($module) {
            $this->_request->setModuleName($module);
        }
        if ($controller) {
            $this->_request->setControllerName($controller);
        }
        $this->_request->setActionName($action)->setDispatched(false);
        $this->_isForwarded = true;
    }

    /**
     * Check whether a disallowed store is in request
     *
     * @param string $idFieldName
     * @return bool
     */
    protected function _isAllowedStoreInRequest($idFieldName = 'store')
    {
        $storeId = $this->_request->getParam($idFieldName);
        if (empty($storeId)) {
            return true;
        }
        $store = $this->_storeManager->getStore($storeId);
        return $this->_role->hasStoreAccess($store->getId());
    }

    /**
     * Check whether specified category is allowed
     *
     * @param Category $category
     * @return bool
     */
    protected function _isCategoryAllowed($category)
    {
        $categoryPath = $category->getPath();
        foreach ($this->_role->getAllowedRootCategories() as $rootPath) {
            if ($categoryPath === $rootPath || 0 === strpos($categoryPath, "{$rootPath}/")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate Order view actions
     *
     * @return bool
     */
    public function validateSalesOrderViewAction()
    {
        $id = $this->_request->getParam('order_id');
        if ($id) {
            $object = $this->_objectManager->create(Order::class)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Creditmemo view actions
     *
     * @return bool
     */
    public function validateSalesOrderCreditmemoViewAction()
    {
        $id = $this->_request->getParam('creditmemo_id');
        if (!$id) {
            $id = $this->_request->getParam('id');
        }
        if ($id) {
            $object = $this->_objectManager->create(Creditmemo::class)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Invoice view actions
     *
     * @return bool
     */
    public function validateSalesOrderInvoiceViewAction()
    {
        $id = $this->_request->getParam('invoice_id');
        if (!$id) {
            $id = $this->_request->getParam('id');
        }
        if ($id) {
            $object = $this->_objectManager->create(Invoice::class)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Shipment view actions
     *
     * @return bool
     */
    public function validateSalesOrderShipmentViewAction()
    {
        $id = $this->_request->getParam('shipment_id');
        if (!$id) {
            $id = $this->_request->getParam('id');
        }
        if ($id) {
            $object = $this->_objectManager->create(Shipment::class)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Creditmemo creation actions
     *
     * @return bool
     */
    public function validateSalesOrderCreditmemoCreateAction()
    {
        if ($id = $this->_request->getParam('order_id')) {
            $className = Order::class;
        } elseif ($id = $this->_request->getParam('invoice_id')) {
            $className = Invoice::class;
        } elseif ($id = $this->_request->getParam('creditmemo_id')) {
            $className = Creditmemo::class;
        } else {
            return true;
        }

        if ($id) {
            $object = $this->_objectManager->create($className)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Invoice creation actions
     *
     * @return bool
     */
    public function validateSalesOrderInvoiceCreateAction()
    {
        if ($id = $this->_request->getParam('order_id')) {
            $className = Order::class;
        } elseif ($id = $this->_request->getParam('invoice_id')) {
            $className = Invoice::class;
        } else {
            return true;
        }

        if ($id) {
            $object = $this->_objectManager->create($className)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Shipment creation actions
     *
     * @return bool
     */
    public function validateSalesOrderShipmentCreateAction()
    {
        if ($id = $this->_request->getParam('order_id')) {
            $className = Order::class;
        } elseif ($id = $this->_request->getParam('shipment_id')) {
            $className = Shipment::class;
        } else {
            return true;
        }

        if ($id) {
            $object = $this->_objectManager->create($className)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
                // Set current store to order/shipment store ID if the current store is not accessible by current user
                if (!$this->_role->hasStoreAccess($this->_storeManager->getStore()->getId())) {
                    $this->_storeManager->setCurrentStore($store);
                }
            }
        }
        return true;
    }

    /**
     * Validate Order mass actions
     *
     * @return bool
     */
    public function validateSalesOrderMassAction()
    {
        $ids = $this->_request->getParam('order_ids', []);
        if ($ids) {
            if ($ids && is_array($ids)) {
                foreach ($ids as $id) {
                    $object = $this->_objectManager->create(Order::class)->load($id);
                    if ($object && $object->getId()) {
                        $store = $object->getStoreId();
                        if (!$this->_role->hasStoreAccess($store)) {
                            $this->_forward();
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Validate Order edit action
     *
     * @return bool
     */
    public function validateSalesOrderEditStartAction()
    {
        $id = $this->_request->getParam('order_id');
        if ($id) {
            $object = $this->_objectManager->create(Order::class)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Shipment tracking actions
     *
     * @return bool
     */
    public function validateSalesOrderShipmentTrackAction()
    {
        $id = $this->_request->getParam('track_id');
        if ($id) {
            $object = $this->_objectManager->create(Track::class)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return $this->validateSalesOrderShipmentCreateAction();
    }

    /**
     * Validate Terms and Conditions management edit action
     *
     * @return bool
     */
    public function validateCheckoutAgreementEditAction()
    {
        $id = $this->_request->getParam('id');
        if ($id) {
            $object = $this->_objectManager->create(Agreement::class)->load($id);
            if ($object && $object->getId()) {
                $stores = $object->getStoreId();
                foreach ($stores as $store) {
                    if ($store == 0 || !$this->_role->hasStoreAccess($store)) {
                        $this->_forward();
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Validate URL Rewrite Management edit action
     *
     * @return bool
     */
    public function validateUrlRewriteEditAction()
    {
        $id = $this->_request->getParam('id');
        if ($id) {
            $object = $this->_objectManager->create(UrlRewrite::class)->load($id);
            if ($object && $object->getId()) {
                if (!$this->_role->hasStoreAccess($object->getStoreId())) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Admin User management actions
     *
     * @return bool
     */
    public function validateAdminUserAction()
    {
        $id = $this->_request->getParam('user_id');
        if ($id) {
            $limited = $this->_collectionsFactory->create()->getUsersOutsideLimitedScope(
                $this->_role->getIsAll(),
                $this->_role->getWebsiteIds(),
                $this->_role->getStoreGroupIds()
            );

            if (in_array($id, $limited)) {
                $this->_forward();
                return false;
            }
        }
        return true;
    }

    /**
     * Validate Admin Role management actions
     *
     * @return bool
     */
    public function validateAdminRoleAction()
    {
        $id = $this->_request->getParam('rid', $this->_request->getParam('role_id'));
        if ($id) {
            $limited = $this->_collectionsFactory->create()->getRolesOutsideLimitedScope(
                $this->_role->getIsAll(),
                $this->_role->getWebsiteIds(),
                $this->_role->getStoreGroupIds()
            );
            if (in_array($id, $limited)) {
                $this->_forward();
                return false;
            }
        }
        return true;
    }

    /**
     * Validate Attribute management actions
     *
     * @return bool
     */
    public function validateCatalogProductAttributeActions()
    {
        if (!$this->_role->getIsAll()) {
            $this->_forward();
            return false;
        }
        return true;
    }

    /**
     * Validate Attribute creation action
     *
     * @return bool
     */
    public function validateCatalogProductAttributeCreateAction()
    {
        if (!$this->_role->getIsAll() && !$this->_request->getParam('attribute_id')) {
            $this->_forward();
            return false;
        }

        return true;
    }

    /**
     * Validate Products in Catalog Product MassDelete Action
     *
     * @return void
     */
    public function catalogProductMassDeleteAction()
    {
        $productIds = $this->_request->getParam('product');
        $productNotExclusiveIds = [];
        $productExclusiveIds = [];

        $productsWebsites = $this->_productFactoryRes->create()->getWebsiteIdsByProductIds($productIds);

        foreach ($productsWebsites as $productId => $productWebsiteIds) {
            if (!$this->_role->hasExclusiveAccess($productWebsiteIds)) {
                $productNotExclusiveIds[] = $productId;
            } else {
                $productExclusiveIds[] = $productId;
            }
        }

        if (!empty($productNotExclusiveIds)) {
            $productNotExclusiveIds = implode(', ', $productNotExclusiveIds);
            $message = __('More permissions are needed to delete the "%1" item.', $productNotExclusiveIds);
            $this->messageManager->addError($message);
        }

        $this->_request->setParam('product', $productExclusiveIds);
    }

    /**
     * Validate Attribute set creation, deletion and saving actions
     *
     * @return bool
     */
    public function validateAttributeSetActions()
    {
        $this->_forward();
        return false;
    }

    /**
     * Validate permission for adding new sub category to specified parent id
     *
     * @param int $categoryId
     *
     * @return bool
     */
    protected function _validateCatalogSubCategoryAddPermission($categoryId)
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            /**
             * viewing for parent category allowed and
             * user has exclusive access to root category
             * so we can allow user to add sub category
             */
            return $this->_isCategoryAllowed($category)
            && $this->_role->hasExclusiveCategoryAccess($category->getPath());
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Block index actions for all GWS limited users.
     *
     * @return bool
     */
    public function blockIndexAction()
    {
        $this->_forward();
        return false;
    }

    /**
     * Validates hierarchy actions for all GWS limited users.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateCmsHierarchyAction()
    {
        $websiteId = null;
        $storeId = null;
        if ($this->_request->getPost('website')) {
            if ($website = $this->_storeManager->getWebsite($this->_request->getPost('website'))) {
                $websiteId = $website->getId();
            }
        }
        if ($this->_request->getPost('store')) {
            if ($store = $this->_storeManager->getStore($this->_request->getPost('store'))) {
                $storeId = $store->getId();
                $websiteId = $store->getWebsite()->getWebsiteId();
            }
        }
        if (!$this->_role->getIsAll()) {
            if (!$this->_role->hasExclusiveAccess([$websiteId]) || null === $websiteId) {
                if (!$this->_role->hasExclusiveStoreAccess([$storeId]) || null === $storeId) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate misc Manage Currency Rates requests
     *
     * @return bool
     */
    public function validateManageCurrencyRates()
    {
        if (in_array($this->getActionName(), [self::ACTION_FETCH_RATES, self::ACTION_SAVE_RATES])) {
            $this->_forward();
            return false;
        }

        return true;
    }

    /**
     * Validate misc Transactional Emails
     *
     * @return bool
     */
    public function validateTransactionalEmails()
    {
        if (in_array($this->getActionName(), [self::ACTION_DELETE, self::ACTION_SAVE, self::ACTION_NEW])) {
            $this->_forward();
            return false;
        }

        return true;
    }

    /**
     * Block save action for all GWS limited users
     *
     * @return bool
     */
    public function blockCustomerGroupSave()
    {
        $this->_forward();
        return false;
    }

    /**
     * Block save and delete action for all GWS limited users
     *
     * @return bool
     */
    public function blockTaxChange()
    {
        $this->_forward();
        return false;
    }

    /**
     * Validate Giftregistry actions : edit, add, share, delete
     *
     * @return bool
     */
    public function validateGiftregistryEntityAction()
    {
        $id = $this->_request->getParam('id', $this->_request->getParam('entity_id'));
        if ($id) {
            $websiteId = $this->_objectManager->create(
                Entity::class
            )->getResource()->getWebsiteIdByEntityId(
                $id
            );
            if (!in_array($websiteId, $this->_role->getWebsiteIds())) {
                $this->_forward();
                return false;
            }
        } else {
            $this->_forward();
            return false;
        }
        return true;
    }

    /**
     * Validate customer attribute actions
     *
     * @return bool
     */
    public function validateCustomerAttributeActions()
    {
        $actionName = $this->getActionName();
        $attributeId = $this->_request->getParam('attribute_id');
        $websiteId = $this->_request->getParam('website');
        if (in_array($actionName, [self::ACTION_NEW, self::ACTION_DELETE])
            || in_array($actionName, [self::ACTION_EDIT, self::ACTION_SAVE])
            && !$attributeId
            || $websiteId
            && !$this->_role->hasWebsiteAccess($websiteId, true)
        ) {
            $this->_forward();
            return false;
        }
        return true;
    }

    /**
     * Deny certain actions at rule entity in disallowed scopes
     *
     * @return bool|void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validateRuleEntityAction()
    {
        $request = $this->_request;
        $denyActions = [
            self::ACTION_EDIT,
            self::ACTION_NEW,
            self::ACTION_DELETE,
            self::ACTION_SAVE,
            self::ACTION_RUN,
            self::ACTION_MATCH
        ];
        $denyChangeDataActions = [self::ACTION_DELETE, self::ACTION_SAVE, self::ACTION_RUN, self::ACTION_MATCH];
        $denyCreateDataActions = [self::ACTION_SAVE];
        $actionName = $request->getActionName();

        // Deny access if role has no allowed website ids and there are considering actions to deny
        if (!$this->_role->getWebsiteIds() && in_array($actionName, $denyActions)) {
            $this->_forward();
            return false;
        }

        // Stop further validating if role has any allowed website ids and
        // there are considering any action which is not in deny list
        if (!in_array($actionName, $denyActions)) {
            return true;
        }

        // Stop further validating if there is no an appropriate entity id in request params
        $ruleId = $request->getParam('rule_id', $request->getParam('segment_id', $request->getParam('id', null)));
        if (!$ruleId && !in_array($actionName, $denyCreateDataActions)) {
            return true;
        }

        $controllerName = $request->getControllerName();

        // Determine entity model class name
        switch ($controllerName) {
            case 'promo_catalog':
                $entityModelClassName = \Magento\CatalogRule\Model\Rule::class;
                break;
            case 'promo_quote':
                $entityModelClassName = \Magento\SalesRule\Model\Rule::class;
                break;
            case 'reminder':
                $entityModelClassName = Rule::class;
                break;
            case 'customersegment':
                $entityModelClassName = Segment::class;
                break;
            default:
                $entityModelClassName = null;
                break;
        }

        if (null === $entityModelClassName) {
            return true;
        }

        $entityObject = $this->_objectManager->create($entityModelClassName);
        if (!$entityObject) {
            return true;
        }

        $ruleWebsiteIds = $request->getParam('website_ids', []);
        if ($ruleId) {
            // Deny action if specified rule entity doesn't exist
            $entityObject->load($ruleId);
            if (!$entityObject->getId()) {
                $this->_forward();
                return false;
            }
            $ruleWebsiteIds = array_unique(
                array_merge($ruleWebsiteIds, (array)$entityObject->getOrigData('website_ids'))
            );
        }

        // Deny actions what lead to changing data if role has no exclusive access to assigned to rule entity websites
        if (!$this->_role->hasExclusiveAccess($ruleWebsiteIds) && in_array($actionName, $denyChangeDataActions)) {
            $this->_forward();
            return false;
        }

        // Deny action if role has no access to assigned to rule entity websites
        if (!$this->_role->hasWebsiteAccess($ruleWebsiteIds)) {
            $this->_forward();
            return false;
        }

        return true;
    }

    /**
     * Validate applying catalog rules action
     *
     * @return bool
     */
    public function validatePromoCatalogApplyRules()
    {
        $this->_forward();
        return false;
    }

    /**
     * Promo catalog index action
     *
     * @param Action $controller
     * @return $this
     */
    public function promoCatalogIndexAction($controller)
    {
        $controller->setDirtyRulesNoticeMessage(
            __(
                'You need more permissions to apply some of the rule updates.'
            )
        );
        return $this;
    }

    /**
     * Block editing of RMA attributes on disallowed websites
     *
     * @return bool|string
     */
    public function validateRmaAttributeEditAction()
    {
        $websiteCode = $this->_request->getParam('website');

        if (!$websiteCode) {
            $allowedWebsitesIds = $this->_role->getWebsiteIds();

            if (!count($allowedWebsitesIds)) {
                $this->_forward();
                return false;
            }

            $this->_redirect(
                $this->_backendUrl->getUrl(
                    'adminhtml/rma_item_attribute/edit',
                    ['website' => $allowedWebsitesIds[0], '_current' => true]
                )
            );
            return false;
        }

        try {
            $website = $this->_storeManager->getWebsite($websiteCode);

            if (!$website || !$this->_role->hasWebsiteAccess($website->getId(), true)) {
                $this->_forward();
                return false;
            }
        } catch (LocalizedException $e) {
            $this->_forward();
            return false;
        }

        return true;
    }

    /**
     * Block RMA attributes deleting for all GWS enabled users
     *
     * @return bool
     */
    public function validateRmaAttributeDeleteAction()
    {
        $this->_forward();
        return false;
    }

    /**
     * Block deleting of options of attributes for all GWS enabled users
     *
     * @return bool|string
     */
    public function validateRmaAttributeSaveAction()
    {
        $option = $this->_request->getPost('option');
        if (!empty($option[self::ACTION_DELETE])) {
            unset($option[self::ACTION_DELETE]);
            $this->_request->setPostValue('option', $option);
        }

        return $this->validateRmaAttributeEditAction();
    }

    /**
     * Check access to load customer review
     */
    public function validateReviewCustomer()
    {
        $reviewId = $this->_request->getParam('id');
        $customerId = $this->_request->getParam('customerId');
        if ($reviewId) {
            $review = $this->_objectManager->create(Review::class)->load($reviewId);
            $customerId = $review->getCustomerId();
        }

        if ($customerId && !$this->isThereAccessToCustomer((int)$customerId)) {
            $this->_redirect('admin/denied');
        }
    }

    /**
     * Is there access to load customer
     *
     * @param int $customerId
     * @return bool
     */
    private function isThereAccessToCustomer(int $customerId): bool
    {
        try {
            $customer = $this->_objectManager->create(Customer::class)->load($customerId);
            if ($customer->getId() && !in_array($customer->getWebsiteId(), $this->_role->getRelevantWebsiteIds())) {
                return false;
            }
        } catch (LocalizedException $exception) {
            $permissionMessage = __('More permissions are needed to view this item.')->render();
            if ($exception->getMessage() === $permissionMessage) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return an action name of the request lowercase
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getActionName()
    {
        return strtolower($this->_request->getActionName());
    }
}
