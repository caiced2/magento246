<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\MetadataServiceInterface;
use Magento\Rma\Api\Data\TrackInterface;

/**
 * RMA Shipping Model
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Shipping extends \Magento\Sales\Model\AbstractModel implements \Magento\Rma\Api\Data\TrackInterface
{
    /**#@+
     * Data object properties
     */
    const IS_ADMIN = 'is_admin';
    const ENTITY_ID = 'entity_id';
    const RMA_ENTITY_ID = 'rma_entity_id';
    const TRACK_NUMBER = 'track_number';
    const CARRIER_TITLE = 'carrier_title';
    const CARRIER_CODE = 'carrier_code';
    /**#@-*/

    /**
     * Store address
     */
    const XML_PATH_ADDRESS1 = 'sales/magento_rma/address';

    const XML_PATH_ADDRESS2 = 'sales/magento_rma/address1';

    const XML_PATH_CITY = 'sales/magento_rma/city';

    const XML_PATH_REGION_ID = 'sales/magento_rma/region_id';

    const XML_PATH_ZIP = 'sales/magento_rma/zip';

    const XML_PATH_COUNTRY_ID = 'sales/magento_rma/country_id';

    const XML_PATH_CONTACT_NAME = 'sales/magento_rma/store_name';

    /**
     * Constants - value of is_admin field in table
     */
    const IS_ADMIN_STATUS_USER_TRACKING_NUMBER = 0;

    const IS_ADMIN_STATUS_ADMIN_TRACKING_NUMBER = 1;

    const IS_ADMIN_STATUS_ADMIN_LABEL = 2;

    const IS_ADMIN_STATUS_ADMIN_LABEL_TRACKING_NUMBER = 3;

    /**
     * Code of custom carrier
     */
    const CUSTOM_CARRIER_CODE = 'custom';

    /**
     * Tracking info
     *
     * @var array
     */
    protected $_trackingInfo = [];

    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData = null;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Sales order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * Core store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Directory region factory
     *
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * Shipping return shipment factory
     *
     * @var \Magento\Shipping\Model\Shipment\ReturnShipmentFactory
     */
    protected $_returnFactory;

    /**
     * Shipping carrier factory
     *
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * Rma factory
     *
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $_rmaFactory;

    /**
     * Application filesystem
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Shipping\Model\Shipment\ReturnShipmentFactory $returnFactory
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param RmaFactory $rmaFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Shipping\Model\Shipment\ReturnShipmentFactory $returnFactory,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_rmaData = $rmaData;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderFactory = $orderFactory;
        $this->_storeManager = $storeManager;
        $this->_regionFactory = $regionFactory;
        $this->_returnFactory = $returnFactory;
        $this->_carrierFactory = $carrierFactory;
        $this->filesystem = $filesystem;
        $this->_rmaFactory = $rmaFactory;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @inheritdoc
     *
     * @return \Magento\Rma\Api\Data\TrackExtensionInterface
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     *
     * @param \Magento\Rma\Api\Data\TrackExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\TrackExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    //@codeCoverageIgnoreStart

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getRmaEntityId()
    {
        return $this->getData(self::RMA_ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRmaEntityId($entityId)
    {
        return $this->setData(self::RMA_ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getTrackNumber()
    {
        return $this->getData(self::TRACK_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setTrackNumber($trackNumber)
    {
        return $this->setData(self::TRACK_NUMBER, $trackNumber);
    }

    /**
     * @inheritdoc
     */
    public function getCarrierTitle()
    {
        return $this->getData(self::CARRIER_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setCarrierTitle($carrierTitle)
    {
        return $this->setData(self::CARRIER_TITLE, $carrierTitle);
    }

    /**
     * @inheritdoc
     */
    public function getCarrierCode()
    {
        return $this->getData(self::CARRIER_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setCarrierCode($carrierCode)
    {
        return $this->setData(self::CARRIER_CODE, $carrierCode);
    }

    //@codeCoverageIgnoreEnd

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Rma\Model\ResourceModel\Shipping::class);
    }

    /**
     * Prepare and do return of shipment
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function requestToShipment()
    {
        $shipmentStoreId = $this->getRma()->getStoreId();
        $storeInfo = new \Magento\Framework\DataObject(
            $this->_scopeConfig->getValue(
                'general/store_information',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
        );
        /** @var $order \Magento\Sales\Model\Order */
        $order = $this->_orderFactory->create()->load($this->getRma()->getOrderId());
        $this->setOrder($order);
        $shipperAddress = $order->getShippingAddress();
        /** @var \Magento\Quote\Model\Quote\Address $recipientAddress */
        $recipientAddress = $this->_rmaData->getReturnAddressModel($this->getRma()->getStoreId());
        list($carrierCode, $shippingMethod) = explode('_', $this->getCode(), 2);
        $shipmentCarrier = $this->_rmaData->getCarrier($this->getCode(), $shipmentStoreId);
        $baseCurrencyCode = $this->_storeManager->getStore($shipmentStoreId)->getBaseCurrencyCode();

        if (!$shipmentCarrier) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The "%1" carrier is invalid. Verify and try again.', $carrierCode)
            );
        }
        $shipperRegionCode = $this->_regionFactory->create()->load($shipperAddress->getRegionId())->getCode();
        $recipientRegionCode = $recipientAddress->getRegionId();
        $recipientContactName = $this->_rmaData->getReturnContactName($this->getRma()->getStoreId());

        if (!$recipientContactName->getName() ||
            !$recipientContactName->getLastName() ||
            !$recipientAddress->getCompany() ||
            !$storeInfo->getPhone() ||
            !$recipientAddress->getStreetFull() ||
            !$recipientAddress->getCity() ||
            !$shipperRegionCode ||
            !$recipientAddress->getPostcode() ||
            !$recipientAddress->getCountryId()
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'We need more information to create your shipping label(s). '
                    . 'Please verify your store information and shipping settings.'
                )
            );
        }

        /** @var $request \Magento\Shipping\Model\Shipment\ReturnShipment */
        $request = $this->_returnFactory->create();
        $request->setOrderShipment($this);

        $request->setShipperContactPersonName($order->getCustomerName());
        $request->setShipperContactPersonFirstName($order->getCustomerFirstname());
        $request->setShipperContactPersonLastName($order->getCustomerLastname());

        $companyName = $shipperAddress->getCompany();
        if (empty($companyName)) {
            $companyName = $order->getCustomerName();
        }
        $request->setShipperContactCompanyName($companyName);
        $request->setShipperContactPhoneNumber($shipperAddress->getTelephone());
        $request->setShipperEmail($shipperAddress->getEmail());
        $request->setShipperAddressStreet(
            trim($shipperAddress->getStreetLine(1) . ' ' . $shipperAddress->getStreetLine(2))
        );
        $request->setShipperAddressStreet1($shipperAddress->getStreetLine(1));
        $request->setShipperAddressStreet2($shipperAddress->getStreetLine(2));
        $request->setShipperAddressCity($shipperAddress->getCity());
        $request->setShipperAddressStateOrProvinceCode($shipperRegionCode);
        $request->setShipperAddressPostalCode($shipperAddress->getPostcode());
        $request->setShipperAddressCountryCode($shipperAddress->getCountryId());

        $request->setRecipientContactPersonName($recipientContactName->getName());
        $request->setRecipientContactPersonFirstName($recipientContactName->getFirstName());
        $request->setRecipientContactPersonLastName($recipientContactName->getLastName());
        $request->setRecipientContactCompanyName($recipientAddress->getCompany());
        $request->setRecipientContactPhoneNumber($storeInfo->getPhone());
        $request->setRecipientEmail($recipientAddress->getEmail());
        $request->setRecipientAddressStreet($recipientAddress->getStreetFull());
        $request->setRecipientAddressStreet1($recipientAddress->getStreetLine(1));
        $request->setRecipientAddressStreet2($recipientAddress->getStreetLine(2));
        $request->setRecipientAddressCity($recipientAddress->getCity());
        $request->setRecipientAddressStateOrProvinceCode($recipientRegionCode);
        $request->setRecipientAddressRegionCode($recipientRegionCode);
        $request->setRecipientAddressPostalCode($recipientAddress->getPostcode());
        $request->setRecipientAddressCountryCode($recipientAddress->getCountryId());

        $request->setShippingMethod($shippingMethod);
        $request->setPackageWeight($this->getWeight());
        $request->setPackages($this->getPackages());
        $request->setBaseCurrencyCode($baseCurrencyCode);
        $request->setStoreId($shipmentStoreId);

        $referenceData = 'RMA #' . $request->getOrderShipment()->getRma()->getIncrementId() . ' P';
        $request->setReferenceData($referenceData);

        return $shipmentCarrier->returnOfShipment($request);
    }

    /**
     * Retrieve detail for shipment track
     *
     * @return \Magento\Framework\Phrase|string|array
     */
    public function getNumberDetail()
    {
        $carrierInstance = $this->_carrierFactory->create($this->getCarrierCode());
        if (!$carrierInstance) {
            $custom = [];
            $custom['title'] = $this->getCarrierTitle();
            $custom['number'] = $this->getTrackNumber();
            return $custom;
        } else {
            $carrierInstance->setStore($this->getStore());
        }

        if (!($trackingInfo = $carrierInstance->getTrackingInfo($this->getTrackNumber()))) {
            return (string)__('No detail for number "%1"', $this->getTrackNumber());
        }

        return $trackingInfo;
    }

    /**
     * Retrieve hash code of current order
     *
     * @return string
     */
    public function getProtectCode()
    {
        if ($this->getRmaEntityId()) {
            /** @var $rma Rma */
            $rma = $this->_rmaFactory->create()->load($this->getRmaEntityId());
        }
        return (string)$rma->getProtectCode();
    }

    /**
     * Retrieves shipping label for current rma
     *
     * @param Rma|int $rma
     * @return \Magento\Framework\DataObject
     */
    public function getShippingLabelByRma($rma)
    {
        if (!is_int($rma)) {
            $rma = $rma->getId();
        }
        $label = $this->getCollection()->addFieldToFilter(
            'rma_entity_id',
            $rma
        )->addFieldToFilter(
            'is_admin',
            self::IS_ADMIN_STATUS_ADMIN_LABEL
        )->getFirstItem();

        if ($label->getShippingLabel()) {
            $label->setShippingLabel(
                $this->getResource()->getConnection()->decodeVarbinary($label->getShippingLabel())
            );
        }

        return $label;
    }

    /**
     * Check whether custom carrier was used for this track
     *
     * @return bool
     */
    public function isCustom()
    {
        return $this->getCarrierCode() == self::CUSTOM_CARRIER_CODE;
    }
}
