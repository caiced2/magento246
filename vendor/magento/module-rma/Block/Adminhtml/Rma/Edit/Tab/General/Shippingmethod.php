<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Shipping\Helper\Carrier;

/**
 * Shipping Method Block at RMA page
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Shippingmethod extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\AbstractGeneral
{
    /**
     * PSL Button statuses
     */
    const PSL_DISALLOWED = 0;

    const PSL_ALLOWED = 1;

    const PSL_DISABLED = 2;

    /**
     * Variable to store RMA instance
     *
     * @var null|\Magento\Rma\Model\Rma
     */
    protected $_rma;

    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData;

    /**
     * Tax data
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxData;

    /**
     * Rma shipping factory
     *
     * @var \Magento\Rma\Model\ShippingFactory
     */
    protected $_shippingFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var array
     */
    private $carrierCodeList = [
        'dhl',
        'fedex'
    ];

    /**
     * Json instance
     *
     * @var Json
     */
    private $json;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Rma\Model\ShippingFactory $shippingFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @param Json|null $json
     * @param Carrier|null $carrierHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Rma\Model\ShippingFactory $shippingFactory,
        PriceCurrencyInterface $priceCurrency,
        array $data = [],
        Json $json = null,
        ?Carrier $carrierHelper = null
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_taxData = $taxData;
        $this->_rmaData = $rmaData;
        $this->_shippingFactory = $shippingFactory;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        $data['carrierHelper'] = $carrierHelper ?? ObjectManager::getInstance()->get(Carrier::class);
        parent::__construct($context, $registry, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $buttonStatus = \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod::PSL_DISALLOWED;
        if ($this->_getShippingAvailability() && $this->getRma() && $this->getRma()->isAvailableForPrintLabel()) {
            $buttonStatus = \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod::PSL_ALLOWED;
        } elseif ($this->getRma() && $this->getRma()->getButtonDisabledStatus()) {
            $buttonStatus = \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod::PSL_DISABLED;
        }

        $this->setIsPsl($buttonStatus);
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
     * Defines whether Shipping method settings allow to create shipping label
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getShippingAvailability()
    {
        $carriers = [];
        if ($this->getRma()) {
            $carriers = $this->_rmaData->getAllowedShippingCarriers($this->getRma()->getStoreId());
        }
        return !empty($carriers);
    }

    /**
     * Retrieve shipment model instance
     *
     * @return \Magento\Rma\Model\Shipping
     */
    public function getShipment()
    {
        /** @var $shipping \Magento\Rma\Model\Shipping */
        $shipping = $this->_shippingFactory->create();
        return $shipping->getShippingLabelByRma($this->getRma());
    }

    /**
     * Return price according to store
     *
     * @param string $price
     * @return float
     */
    public function getShippingPrice($price)
    {
        return $this->priceCurrency->convertAndFormat(
            $this->_taxData->getShippingPrice($price),
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->_storeManager->getStore($this->getRma()->getStoreId())
        );
    }

    /**
     * Get packed products in packages
     *
     * @return array
     */
    public function getPackages()
    {
        $packages = $this->getShipment()->getPackages();
        if (!$packages) {
            return [];
        }
        return $this->json->unserialize($packages);
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
        $carrierCode = $this->getShipment()->getCarrierCode();
        if (!$carrierCode) {
            return false;
        }
        $address = $order->getShippingAddress();
        $shipperAddressCountryCode = $address->getCountryId();
        $recipientAddressCountryCode = $this->_rmaData->getReturnAddressModel($storeId)->getCountryId();

        return $shipperAddressCountryCode != $recipientAddressCountryCode && $this->canDisplayCustomValue();
    }

    /**
     * Checks carrier for possibility to display custom value
     *
     * @return bool
     */
    public function canDisplayCustomValue()
    {
        $carrierCode = $this->getShipment()->getCarrierCode();

        if (!$carrierCode) {
            return false;
        }

        return in_array($carrierCode, $this->carrierCodeList);
    }

    /**
     * Get print label button html
     *
     * @return string
     */
    public function getPrintLabelButton()
    {
        $data['id'] = $this->getRma()->getId();
        $url = $this->getUrl('adminhtml/rma/printLabel', $data);

        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('Print Shipping Label'), 'onclick' => 'setLocation(\'' . $url . '\')']
        )->toHtml();
    }

    /**
     * Show packages button html
     *
     * @return string
     */
    public function getShowPackagesButton()
    {
        return $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            ['label' => __('Show Packages'), 'onclick' => 'showPackedWindow();']
        )->toHtml();
    }

    /**
     * Print button for creating pdf
     *
     * @return string
     */
    public function getPrintButton()
    {
        $data['id'] = $this->getRma()->getId();
        return $this->getUrl('adminhtml/rma/printPackage', $data);
    }

    /**
     * Return name of container type by its code
     *
     * @param string $code
     * @return string
     */
    public function getContainerTypeByCode($code)
    {
        $carrierCode = $this->getShipment()->getCarrierCode();
        $carrier = $this->_rmaData->getCarrier($carrierCode, $this->getRma()->getStoreId());
        if ($carrier) {
            $containerTypes = $carrier->getContainerTypes();
            $containerType = !empty($containerTypes[$code]) ? $containerTypes[$code] : '';
            return $containerType;
        }
        return '';
    }

    /**
     * Return name of delivery confirmation type by its code
     *
     * @param string $code
     * @return string
     */
    public function getDeliveryConfirmationTypeByCode($code)
    {
        $storeId = $this->getRma()->getStoreId();
        $countryId = $this->_rmaData->getReturnAddressModel($storeId)->getCountryId();
        $carrierCode = $this->getShipment()->getCarrierCode();
        $carrier = $this->_rmaData->getCarrier($carrierCode, $this->getRma()->getStoreId());
        if ($carrier) {
            $params = new \Magento\Framework\DataObject(['country_recipient' => $countryId]);
            $confirmationTypes = $carrier->getDeliveryConfirmationTypes($params);
            $containerType = !empty($confirmationTypes[$code]) ? $confirmationTypes[$code] : '';
            return $containerType;
        }
        return '';
    }

    /**
     * Display formatted price
     *
     * @param float $price
     * @return string
     */
    public function displayPrice($price)
    {
        return $this->getRma()->getOrder()->formatPriceTxt($price);
    }

    /**
     * Display formatted customs price
     *
     * @param float $price
     * @return string
     */
    public function displayCustomsPrice($price)
    {
        $rmaInfo = $this->getRma()->getOrder();
        return $rmaInfo->getBaseCurrency()->formatTxt($price);
    }

    /**
     * Get ordered qty of item
     *
     * @param int $itemId
     * @return int|null
     */
    public function getQtyOrderedItem($itemId)
    {
        if ($itemId) {
            return $this->getRma()->getOrder()->getItemById($itemId)->getQtyOrdered() * 1;
        } else {
            return;
        }
    }

    /**
     * Return content types of package
     *
     * @return array
     */
    public function getContentTypes()
    {
        $order = $this->getRma()->getOrder();
        $storeId = $this->getRma()->getStoreId();
        $address = $order->getShippingAddress();

        $carrierCode = $this->getShipment()->getCarrierCode();
        $carrier = $this->_rmaData->getCarrier($carrierCode, $storeId);

        $countryShipper = $this->_scopeConfig->getValue(
            \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_COUNTRY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($carrier) {
            $params = new \Magento\Framework\DataObject(
                [
                    'method' => $carrier->getMethod(),
                    'country_shipper' => $countryShipper,
                    'country_recipient' => $address->getCountryId(),
                ]
            );
            return $carrier->getContentTypes($params);
        }
        return [];
    }

    /**
     * Return name of content type by its code
     *
     * @param string $code
     * @return string
     */
    public function getContentTypeByCode($code)
    {
        $contentTypes = $this->getContentTypes();
        if (!empty($contentTypes[$code])) {
            return $contentTypes[$code];
        }
        return '';
    }
}
