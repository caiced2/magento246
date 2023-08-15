<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping;

use Magento\Shipping\Model\Carrier\Source\GenericInterface;

/**
 * Shipment packaging
 *
 * @api
 * @since 100.0.2
 */
class Packaging extends \Magento\Backend\Block\Template
{
    /**
     * Variable to store RMA instance
     *
     * @var null|\Magento\Rma\Model\Rma
     */
    protected $_rma = null;

    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Sales order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * Source size model
     *
     * @var \Magento\Shipping\Model\Carrier\Source\GenericInterface
     */
    protected $_sourceSizeModel;

    /**
     * Usps container format
     * @var string
     */
    private static $containerVariable = 'VARIABLE';

    /**
     * Usps container format
     * @var string
     */
    private static $containerRectangular = 'RECTANGULAR';

    /**
     * Usps container format
     * @var string
     */
    private static $containerNonRectangular = 'NONRECTANGULAR';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Shipping\Model\Carrier\Source\GenericInterface $sourceSizeModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        GenericInterface $sourceSizeModel,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_rmaData = $rmaData;
        $this->_orderFactory = $orderFactory;
        $this->_sourceSizeModel = $sourceSizeModel;
        parent::__construct($context, $data);
    }

    /**
     * Declare rma instance
     *
     * @return  \Magento\Rma\Model\Item
     */
    public function getRma()
    {
        if ($this->_rma === null) {
            $this->_rma = $this->_coreRegistry->registry('current_rma');
        }
        return $this->_rma;
    }

    /**
     * Retrieve carrier
     *
     * @return string
     */
    public function getCarrier()
    {
        return $this->_rmaData->getCarrier($this->getRequest()->getParam('method'), $this->getRma()->getStoreId());
    }

    /**
     * Retrieve carrier method
     *
     * @return null|string
     */
    public function getCarrierMethod()
    {
        $code = explode('_', $this->getRequest()->getParam('method'), 2);

        if (is_array($code) && isset($code[1])) {
            return $code[1];
        } else {
            return null;
        }
    }

    /**
     * Return container types of carrier
     *
     * @return string[]|bool
     */
    public function getContainers()
    {
        $order = $this->getRma()->getOrder();
        $storeId = $this->getRma()->getStoreId();
        $address = $order->getShippingAddress();
        $carrier = $this->getCarrier();

        $countryRecipient = $this->_rmaData->getReturnAddressModel($storeId)->getCountryId();
        if ($carrier) {
            $params = new \Magento\Framework\DataObject(
                [
                    'method' => $this->getCarrierMethod(),
                    'country_shipper' => $address->getCountryId(),
                    'country_recipient' => $countryRecipient,
                ]
            );
            return $carrier->getContainerTypes($params);
        }
        return [];
    }

    /**
     * Can display customs value
     *
     * @return bool
     */
    public function displayCustomsValue()
    {
        $storeId = $this->getRma()->getStoreId();
        $order = $this->getRma()->getOrder();
        $address = $order->getShippingAddress();
        $shipperAddressCountryCode = $address->getCountryId();
        $recipientAddressCountryCode = $this->_rmaData->getReturnAddressModel($storeId)->getCountryId();

        if ($shipperAddressCountryCode != $recipientAddressCountryCode) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return delivery confirmation types of current carrier
     *
     * @return array|bool
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getDeliveryConfirmationTypes()
    {
        $storeId = $this->getRma()->getStoreId();
        $code = $this->getRequest()->getParam('method');
        if (!empty($code)) {
            list($carrierCode, $methodCode) = explode('_', $code, 2);
            $carrier = $this->_rmaData->getCarrier($carrierCode, $storeId);
            $countryId = $this->_rmaData->getReturnAddressModel($storeId)->getCountryId();
            $params = new \Magento\Framework\DataObject(['country_recipient' => $countryId]);

            if ($carrier && is_array($carrier->getDeliveryConfirmationTypes($params))) {
                return $carrier->getDeliveryConfirmationTypes($params);
            }
        }
        return [];
    }

    /**
     * Check whether girth is allowed for current carrier
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function isGirthAllowed()
    {
        $storeId = $this->getRma()->getStoreId();
        $carrierMethodCode = $this->getRequest()->getParam('method');
        $girth = false;
        if (!empty($carrierMethodCode)) {
            list($carrierCode, $methodCode) = explode('_', $carrierMethodCode, 2);
            $carrier = $this->_rmaData->getCarrier($carrierCode, $storeId);
            $countryId = $this->_rmaData->getReturnAddressModel($storeId)->getCountryId();

            $girth = $carrier->isGirthAllowed($countryId, $carrierMethodCode);
        }
        return $girth;
    }

    /**
     * Return content types of package
     *
     * @return array
     */
    public function getContentTypes()
    {
        $storeId = $this->getRma()->getStoreId();
        $code = $this->getRequest()->getParam('method');
        if (!empty($code)) {
            list($carrierCode, $methodCode) = explode('_', $code, 2);
            $carrier = $this->_rmaData->getCarrier($carrierCode, $storeId);
            $countryId = $this->_rmaData->getReturnAddressModel($storeId)->getCountryId();

            /** @var $order \Magento\Sales\Model\Order */
            $order = $this->_orderFactory->create()->load($this->getRma()->getOrderId());
            $shipperAddress = $order->getShippingAddress();
            if ($carrier) {
                $params = new \Magento\Framework\DataObject(
                    [
                        'method' => $methodCode,
                        'country_shipper' => $shipperAddress->getCountryId(),
                        'country_recipient' => $countryId,
                    ]
                );
                return $carrier->getContentTypes($params);
            }
        }

        return [];
    }

    /**
     * Return customizable containers status
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getCustomizableContainersStatus()
    {
        $storeId = $this->getRma()->getStoreId();
        $code = $this->getRequest()->getParam('method');
        $carrier = $this->_rmaData->getCarrier($code, $storeId);
        if ($carrier) {
            $getCustomizableContainers = $carrier->getCustomizableContainerTypes();

            if (in_array(key($this->getContainers()), $getCustomizableContainers)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get source size model
     *
     * @return array
     */
    public function getSourceSizeModel()
    {
        return $this->_sourceSizeModel->toOptionArray();
    }

    /**
     * Check size and girth parameter
     *
     * @return array
     */
    public function checkSizeAndGirthParameter()
    {
        $carrier = $this->getCarrier();
        $size = $this->getSourceSizeModel();
        $containerKey = key($this->getContainers());

        $girthEnabled = false;
        $sizeEnabled = false;
        if ($carrier && isset($size[0]['value'])) {
            if (in_array($containerKey, [self::$containerNonRectangular, self::$containerVariable])) {
                $girthEnabled = true;
            }

            if (in_array(
                $containerKey,
                [self::$containerNonRectangular, self::$containerRectangular, self::$containerVariable]
            )) {
                $sizeEnabled = true;
            }
        }

        return [$girthEnabled, $sizeEnabled];
    }
}
