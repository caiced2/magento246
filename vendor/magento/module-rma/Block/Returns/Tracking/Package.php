<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Returns\Tracking;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;
use Magento\Shipping\Helper\Carrier;

/**
 * Block to show Rma packages
 *
 * @api
 * @since 100.0.2
 */
class Package extends \Magento\Shipping\Block\Tracking\Popup
{
    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param array $data
     * @param SerializerInterface|null $serializer
     * @param Carrier|null $shippingHelper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        DateTimeFormatterInterface $dateTimeFormatter,
        \Magento\Rma\Helper\Data $rmaData,
        array $data = [],
        SerializerInterface $serializer = null,
        Carrier $shippingHelper = null
    ) {
        $data['shippingHelper'] = $shippingHelper ?? ObjectManager::getInstance()->get(Carrier::class);
        $this->_rmaData = $rmaData;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
        parent::__construct($context, $registry, $dateTimeFormatter, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setPackageInfo($this->_registry->registry('rma_package_shipping'));
    }

    /**
     * Get packages of RMA
     *
     * @return array
     */
    public function getPackages()
    {
        $packages = $this->getPackageInfo()->getPackages();
        if (empty($packages)) {
            return [];
        }

        return $this->serializer->unserialize($packages);
    }

    /**
     * Print package url for creating pdf
     *
     * @return string
     */
    public function getPrintPackageUrl()
    {
        $data = [
            '_query' => ['hash' => $this->getRequest()->getParam('hash')]
        ];

        return $this->getUrl('*/*/packageprint', $data);
    }

    /**
     * Return name of container type by its code
     *
     * @param string $code
     * @return string
     */
    public function getContainerTypeByCode($code)
    {
        $carrierCode = $this->getPackageInfo()->getCarrierCode();
        $carrier = $this->_rmaData->getCarrier($carrierCode, $this->_storeManager->getStore()->getId());
        if ($carrier) {
            $containerTypes = $carrier->getContainerTypes();
            $containerType = !empty($containerTypes[$code]) ? $containerTypes[$code] : '';
            return $containerType;
        }
        return '';
    }
}
