<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * RMA Helper
 */
namespace Magento\Rma\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Shipping;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Rma helper class
 *
 * @api
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 *
 * @since 100.0.2
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Variable to contain country model
     *
     * @var \Magento\Directory\Model\Country
     */
    protected $_countryModel = null;

    /**
     * Variable to contain order items collection for RMA creating
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection
     */
    protected $_orderItems = null;

    /**
     * Allowed hash keys for shipment tracking
     *
     * @var string[]
     */
    protected $_allowedHashKeys = ['rma_id', 'track_id'];

    /**
     * Country factory
     *
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     * Region factory
     *
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * Core store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Rma item factory
     *
     * @var \Magento\Rma\Model\ResourceModel\ItemFactory
     */
    protected $_itemFactory;

    /**
     * Customer session model
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Backend authorization session model
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * Sales quote address factory
     *
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $_addressFactory;

    /**
     * Shipping carrier factory
     *
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * Filter manager
     *
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $_filterManager;

    /**
     * Date time formatter
     *
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Sales\Model\Order\Admin\Item
     */
    protected $adminOrderItem;

    /**
     * Shipping carrier helper
     *
     * @var \Magento\Shipping\Helper\Carrier
     */
    protected $carrierHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Quote\Model\Quote\AddressFactory $addressFactory
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Sales\Model\Order\Admin\Item $adminOrderItem
     * @param \Magento\Shipping\Helper\Carrier $carrierHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Sales\Model\Order\Admin\Item $adminOrderItem,
        \Magento\Shipping\Helper\Carrier $carrierHelper
    ) {
        $this->_countryFactory = $countryFactory;
        $this->_regionFactory = $regionFactory;
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->_itemFactory = $itemFactory;
        $this->_customerSession = $customerSession;
        $this->_authSession = $authSession;
        $this->_addressFactory = $addressFactory;
        $this->_carrierFactory = $carrierFactory;
        $this->_filterManager = $filterManager;
        $this->dateTime = $dateTime;
        $this->adminOrderItem = $adminOrderItem;
        $this->carrierHelper = $carrierHelper;
        parent::__construct($context);
    }

    /**
     * Checks whether RMA module is enabled for frontend in system config
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            Rma::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Checks for ability to create RMA
     *
     * @param  int|\Magento\Sales\Model\Order $order
     * @param  bool $forceCreate - set yes when you don't need to check config setting (for admin side)
     * @return bool
     */
    public function canCreateRma($order, $forceCreate = false)
    {
        $items = $this->getOrderItems($order);
        if ($items->count() && ($forceCreate || $this->isEnabled())) {
            return true;
        }

        return false;
    }

    /**
     * Gets available order items collection for RMA creating
     *
     * @param  int|\Magento\Sales\Model\Order $orderId
     * @param  bool $onlyParents If needs only parent items (only for backend)
     * @return \Magento\Sales\Model\ResourceModel\Order\Item\Collection
     * @throws LocalizedException
     */
    public function getOrderItems($orderId, $onlyParents = false)
    {
        if (is_object($orderId)) {
            $orderId = $orderId->getId();
        }
        if (!is_numeric($orderId)) {
            throw new LocalizedException(__('Please check this order for errors.'));
        }
        if ($this->_orderItems === null || !isset($this->_orderItems[$orderId])) {
            $this->_orderItems[$orderId] = $this->_itemFactory->create()->getOrderItems($orderId);
        }

        if ($onlyParents) {
            foreach ($this->_orderItems[$orderId] as &$item) {
                if ($item->getParentItemId()) {
                    $this->_orderItems[$orderId]->removeItemByKey($item->getId());
                }
                $item->setName($this->adminOrderItem->getName($item));
            }
        }
        return $this->_orderItems[$orderId];
    }

    /**
     * Get url for rma create
     *
     * @param  \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getReturnCreateUrl($order)
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_getUrl('magento_rma/returns/create', ['order_id' => $order->getId()]);
        } else {
            return $this->_getUrl('magento_rma/guest/create', ['order_id' => $order->getId()]);
        }
    }

    /**
     * Get url for rma submit
     *
     * @param OrderInterface $order
     * @return string
     * @since 101.1.0
     */
    public function getReturnSubmitUrl($order)
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_getUrl('magento_rma/returns/submit', ['order_id' => $order->getId()]);
        } else {
            return $this->_getUrl('magento_rma/guest/submit', ['order_id' => $order->getId()]);
        }
    }

    /**
     * Get formatted return address
     *
     * @param string $formatCode
     * @param array $data - array of address data
     * @param int|null $storeId - Store Id
     * @return string
     */
    public function getReturnAddress($formatCode = 'html', $data = [], $storeId = null)
    {
        if (empty($data)) {
            $data = $this->getReturnAddressData($storeId);
        }

        $format = null;

        if (isset($data['countryId'])) {
            $countryModel = $this->_getCountryModel()->load($data['countryId']);
            $format = $countryModel->getFormat($formatCode);
        }

        if (!$format) {
            $path = sprintf('%s%s', \Magento\Customer\Model\Address\Config::XML_PATH_ADDRESS_TEMPLATE, $formatCode);
            $format = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->_filterManager->template($format, ['variables' => $data]);
    }

    /**
     * Get return contact name
     *
     * @param int|null $storeId
     * @return \Magento\Framework\DataObject
     */
    public function getReturnContactName($storeId = null)
    {
        $contactName = new \Magento\Framework\DataObject();
        if ($this->scopeConfig->isSetFlag(
            Rma::XML_PATH_USE_STORE_ADDRESS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )
        ) {
            $admin = $this->_authSession->getUser();
            $contactName->setFirstName($admin->getFirstname());
            $contactName->setLastName($admin->getLastname());
            $contactName->setName($admin->getName());
        } else {
            $name = $this->scopeConfig->getValue(
                Shipping::XML_PATH_CONTACT_NAME,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $contactName->setFirstName('');
            $contactName->setLastName($name);
            $contactName->setName($name);
        }
        return $contactName;
    }

    /**
     * Get return address model
     *
     * @param int|null $storeId
     * @return \Magento\Quote\Model\Quote\Address
     */
    public function getReturnAddressModel($storeId = null)
    {
        /** @var $addressModel \Magento\Quote\Model\Quote\Address */
        $addressModel = $this->_addressFactory->create();
        $addressModel->setData($this->getReturnAddressData($storeId));
        $addressModel->setCountryId($addressModel->getData('countryId'));
        $addressModel->setStreet($addressModel->getData('street1') . "\n" . $addressModel->getData('street2'));
        return $addressModel;
    }

    /**
     * Get return address array depending on config settings
     *
     * @param \Magento\Store\Model\Store|null|int $store
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getReturnAddressData($store = null)
    {
        if (!$store) {
            $store = $this->_storeManager->getStore();
        }

        if ($this->scopeConfig->isSetFlag(
            Rma::XML_PATH_USE_STORE_ADDRESS,
            ScopeInterface::SCOPE_STORE,
            $store
        )
        ) {
            $data = [
                'city' => $this->scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_CITY,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'countryId' => $this->scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_COUNTRY_ID,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'postcode' => $this->scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ZIP,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'region_id' => $this->scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_REGION_ID,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'street2' => $this->scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ADDRESS2,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'street1' => $this->scopeConfig->getValue(
                    \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_ADDRESS1,
                    ScopeInterface::SCOPE_STORE,
                    $store
                )
            ];
        } else {
            $data = [
                'city' => $this->scopeConfig->getValue(
                    Shipping::XML_PATH_CITY,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'countryId' => $this->scopeConfig->getValue(
                    Shipping::XML_PATH_COUNTRY_ID,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'postcode' => $this->scopeConfig->getValue(
                    Shipping::XML_PATH_ZIP,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'region_id' => $this->scopeConfig->getValue(
                    Shipping::XML_PATH_REGION_ID,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'street2' => $this->scopeConfig->getValue(
                    Shipping::XML_PATH_ADDRESS2,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'street1' => $this->scopeConfig->getValue(
                    Shipping::XML_PATH_ADDRESS1,
                    ScopeInterface::SCOPE_STORE,
                    $store
                ),
                'firstname' => $this->scopeConfig->getValue(
                    Shipping::XML_PATH_CONTACT_NAME,
                    ScopeInterface::SCOPE_STORE,
                    $store
                )
            ];
        }

        $data['country'] = !empty($data['countryId']) ? $this->_countryFactory->create()->loadByCode(
            $data['countryId']
        )->getName() : '';
        $region = $this->_regionFactory->create()->load($data['region_id']);
        $data['region_id'] = $region->getCode();
        $data['region'] = $region->getName();
        $data['company'] = $this->scopeConfig->getValue(
            \Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $data['telephone'] = $this->scopeConfig->getValue(
            \Magento\Store\Model\Information::XML_PATH_STORE_INFO_PHONE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $data['email'] = $this->scopeConfig->getValue(
            \Magento\Rma\Model\Config::XML_PATH_EMAIL_COPY_TO,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $data;
    }

    /**
     * Get Country model
     *
     * @return \Magento\Directory\Model\Country
     */
    protected function _getCountryModel()
    {
        if ($this->_countryModel === null) {
            $this->_countryModel = $this->_countryFactory->create();
        }
        return $this->_countryModel;
    }

    /**
     * Get Contact Email Address title
     *
     * @return string
     */
    public function getContactEmailLabel()
    {
        return __('Contact Email Address');
    }

    /**
     * Get key=>value array of "big four" shipping carriers with store-defined labels
     *
     * @param int|\Magento\Store\Model\Store|null $store
     * @return array
     */
    public function getShippingCarriers($store = null)
    {
        $carriers = [];
        foreach ($this->carrierHelper->getOnlineCarrierCodes($store) as $carrierCode) {
            $carriers[$carrierCode] = $this->carrierHelper->getCarrierConfigValue($carrierCode, 'title', $store);
        }
        return $carriers;
    }

    /**
     * Get array of enabled in website and enabled for RMA shipping carriers with their store-defined labels
     *
     * @param int|\Magento\Store\Model\Store|null $store
     * @return array
     */
    public function getAllowedShippingCarriers($store = null)
    {
        $allowedCarriers = $this->getShippingCarriers($store);
        foreach (array_keys($allowedCarriers) as $carrier) {
            if (!$this->carrierHelper->getCarrierConfigValue($carrier, 'active_rma', $store)) {
                unset($allowedCarriers[$carrier]);
            }
        }
        return $allowedCarriers;
    }

    /**
     * Retrieve carrier
     *
     * @param string $code Shipping method code
     * @param int|int[] $storeId
     * @return bool|\Magento\Shipping\Model\Carrier\AbstractCarrierOnline
     */
    public function getCarrier($code, $storeId = null)
    {
        $data = explode('_', $code, 2);
        $carrierCode = $data[0];

        if (!$this->carrierHelper->getCarrierConfigValue($carrierCode, 'active_rma', $storeId)) {
            return false;
        }
        return $this->_carrierFactory->create($carrierCode, $storeId);
    }

    /**
     * Shipping package popup URL getter
     *
     * @param Rma $model
     * @param string $action
     * @return string
     */
    public function getPackagePopupUrlByRmaModel($model, $action = 'package')
    {
        $param = [
            '_direct' => 'rma/tracking/' . $action,
            '_query' => [
                'hash' => $this->urlEncoder->encode("rma_id:{$model->getId()}:{$model->getProtectCode()}"),
            ]
        ];

        $storeId = is_object($model) ? $model->getStoreId() : null;
        $storeModel = $this->_storeManager->getStore($storeId);
        return $storeModel->getUrl('', $param);
    }

    /**
     * Shipping tracking popup URL getter
     *
     * @param Rma|Shipping $track
     * @return string
     * @throws NoSuchEntityException
     */
    public function getTrackingPopupUrlBySalesModel($track)
    {
        if ($track instanceof Rma) {
            return $this->_getTrackingUrl('rma_id', $track);
        } elseif ($track instanceof Shipping) {
            return $this->_getTrackingUrl('track_id', $track, 'getEntityId');
        }

        return '';
    }

    /**
     * Retrieve tracking url with params
     *
     * @param  string $key
     * @param  Shipping|Rma $model
     * @param  string $method - option
     * @return string
     */
    protected function _getTrackingUrl($key, $model, $method = 'getId')
    {
        $urlPart = "{$key}:{$model->{$method}()}:{$model->getProtectCode()}";
        $params = [
            '_direct' => 'rma/tracking/popup',
            '_query' => ['hash' => $this->urlEncoder->encode($urlPart)]
        ];

        $storeModel = $this->_storeManager->getStore($model->getStoreId());
        return $storeModel->getUrl('', $params);
    }

    /**
     * Decode url hash
     *
     * @param  string $hash
     * @return array
     */
    public function decodeTrackingHash($hash)
    {
        $hash = explode(':', $this->urlDecoder->decode($hash));
        if (count($hash) === 3 && in_array($hash[0], $this->_allowedHashKeys)) {
            return ['key' => $hash[0], 'id' => (int)$hash[1], 'hash' => $hash[2]];
        }
        return [];
    }

    /**
     * Get whether selected product is returnable
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int|null $storeId
     * @return bool
     */
    public function canReturnProduct($product, $storeId = null)
    {
        $isReturnable = $product->getIsReturnable();

        if ($isReturnable === null) {
            $isReturnable = \Magento\Rma\Model\Product\Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG;
        }
        switch ($isReturnable) {
            case \Magento\Rma\Model\Product\Source::ATTRIBUTE_ENABLE_RMA_YES:
                return true;
            case \Magento\Rma\Model\Product\Source::ATTRIBUTE_ENABLE_RMA_NO:
                return false;
            default:
                //Use config and NULL
                return $this->scopeConfig->getValue(
                    \Magento\Rma\Model\Product\Source::XML_PATH_PRODUCTS_ALLOWED,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
        }
    }

    /**
     * Get formated date in store timezone
     *
     * @param string $date
     * @return string
     */
    public function getFormatedDate($date)
    {
        $date = new \DateTime($date);

        $date->setTimezone(
            new \DateTimeZone(
                $this->_localeDate->getConfigTimezone(null, $this->_storeManager->getStore())
            )
        );

        return $this->_localeDate->formatDate($date, \IntlDateFormatter::SHORT);
    }

    /**
     * Retrieves RMA item name for backend
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getAdminProductName($item)
    {
        $name = $item->getName();
        $result = [];
        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }

            if (!empty($result)) {
                $implode = [];
                foreach ($result as $val) {
                    $implode[] = isset($val['print_value']) ? $val['print_value'] : $val['value'];
                }
                return $name . ' (' . implode(', ', $implode) . ')';
            }
        }
        return $name;
    }

    /**
     * Retrieves RMA item sku for backend
     *
     * @param  \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getAdminProductSku($item)
    {
        return $this->adminOrderItem->getSku($item);
    }

    /**
     * Parses quantity depending on isQtyDecimal flag
     *
     * @param float $quantity
     * @param \Magento\Rma\Model\Item $item
     * @return int|float
     */
    public function parseQuantity($quantity, $item)
    {
        if ($quantity === null) {
            $quantity = $item->getOrigData('qty_requested');
        }
        if ($item->getIsQtyDecimal()) {
            return sprintf("%01.4f", $quantity);
        } else {
            return (int)($quantity);
        }
    }

    /**
     * Get Qty by status
     *
     * @param \Magento\Rma\Model\Item $item
     * @return int|float
     */
    public function getQty($item)
    {
        $qty = $item->getQtyRequested();

        if ($item->getQtyApproved() && $item->getStatus() == \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED) {
            $qty = $item->getQtyApproved();
        } elseif ($item->getQtyReturned() &&
            ($item->getStatus() == \Magento\Rma\Model\Rma\Source\Status::STATE_RECEIVED ||
            $item->getStatus() == \Magento\Rma\Model\Rma\Source\Status::STATE_REJECTED)
        ) {
            $qty = $item->getQtyReturned();
        } elseif ($item->getQtyAuthorized() &&
            $item->getStatus() == \Magento\Rma\Model\Rma\Source\Status::STATE_AUTHORIZED
        ) {
            $qty = $item->getQtyAuthorized();
        }

        return $this->parseQuantity($qty, $item);
    }
}
