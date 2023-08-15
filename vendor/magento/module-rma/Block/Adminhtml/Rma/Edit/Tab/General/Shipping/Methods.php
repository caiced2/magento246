<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping;

use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Rma\Helper\Data as RmaHelperData;
use Magento\Rma\Model\Rma;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;

/**
 * Class Methods
 *
 * @api
 * @since 100.0.2
 */
class Methods extends \Magento\Framework\View\Element\Template
{
    /**
     * Tax data
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Json encoder interface
     *
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var RmaHelperData
     */
    private $rmaHelperData;

    /**
     * @var AbstractCarrierOnline[]|array
     */
    private $carriers;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @param RmaHelperData|null $rmaHelperData
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = [],
        RmaHelperData $rmaHelperData = null
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_coreRegistry = $registry;
        $this->_taxData = $taxData;
        $this->priceCurrency = $priceCurrency;
        $this->rmaHelperData = $rmaHelperData ?: ObjectManager::getInstance()->get(RmaHelperData::class);
        parent::__construct($context, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        if ($this->_coreRegistry->registry('current_rma')) {
            $this->setShippingMethods($this->getAvailableShippingMethods());
        }
    }

    /**
     * Get shipping price
     *
     * @param float $price
     * @return float
     */
    public function getShippingPrice($price)
    {
        return $this->priceCurrency->convert($this->_taxData->getShippingPrice($price), true, false);
    }

    /**
     * Get rma shipping data in json format
     *
     * @param array $method
     * @return string
     */
    public function jsonData($method)
    {
        $data = [];
        $data['CarrierTitle'] = $this->escapeHtml($method->getCarrierTitle());
        $data['MethodTitle'] = $this->escapeHtml($method->getMethodTitle());
        $data['Price'] = $this->escapeHtml($this->getShippingPrice($method->getPrice()));
        $data['PriceOriginal'] = $this->escapeHtml($method->getPrice());
        $data['Code'] = $this->escapeHtml($method->getCode());

        return $this->_jsonEncoder->encode($data);
    }

    /**
     * Gets shipping method carrier by shipping method code.
     *
     * @param string $code
     * @param int $storeId
     * @return AbstractCarrierOnline|null
     */
    private function getCarrier(string $code, int $storeId)
    {
        $carrierCode = explode('_', $code, 2)[0];

        if (empty($this->carriers[$carrierCode])) {
            $carrier = $this->rmaHelperData->getCarrier($code, $storeId);
            $this->carriers[$carrierCode] = $carrier ?: null;
        }

        return $this->carriers[$carrierCode];
    }

    /**
     * Gets list of shipping methods which provide possibility to create shipping labels.
     *
     * @return array
     */
    private function getAvailableShippingMethods(): array
    {
        /** @var Rma $rmaModel */
        $rmaModel = $this->_coreRegistry->registry('current_rma');
        $methods = $rmaModel->getShippingMethods();
        if (!$methods) {
            return [];
        }
        $storeId = $rmaModel->getStoreId();

        $allowed = array_filter(
            $methods,
            function ($method) use ($storeId) {
                /** @var Rate $method */
                $carrier = $this->getCarrier($method->getCode(), (int) $storeId);

                return $carrier && $carrier->isShippingLabelsAvailable();
            }
        );

        return $allowed;
    }
}
