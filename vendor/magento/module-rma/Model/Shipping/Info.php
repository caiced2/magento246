<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Shipping;

/**
 * RMA Shipping Info Model
 */
class Info extends \Magento\Framework\DataObject
{
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
    protected $_rmaData;

    /**
     * Rma factory
     *
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $_rmaFactory;

    /**
     * Rma shipping factory
     *
     * @var \Magento\Rma\Model\ShippingFactory
     */
    protected $_shippingFactory;

    /**
     * Constructor
     *
     * By default is looking for first argument as array and assigns it as object
     * attributes This behavior may change in child classes
     *
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param \Magento\Rma\Model\ShippingFactory $shippingFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Rma\Model\ShippingFactory $shippingFactory,
        array $data = []
    ) {
        $this->_rmaData = $rmaData;
        $this->_rmaFactory = $rmaFactory;
        $this->_shippingFactory = $shippingFactory;
        parent::__construct($data);
    }

    /**
     * Generating tracking info
     *
     * @param string $hash
     * @return \Magento\Shipping\Model\Info
     */
    public function loadByHash($hash)
    {
        $data = $this->_rmaData->decodeTrackingHash($hash);

        if (!empty($data)) {
            $this->setData($data['key'], $data['id']);
            $this->setProtectCode($data['hash']);

            if ($this->getRmaId() > 0) {
                $this->getTrackingInfoByRma();
            } else {
                $this->getTrackingInfoByTrackId();
            }
        }
        return $this;
    }

    /**
     * Generating tracking info
     *
     * @param string $hash
     * @return \Magento\Shipping\Model\Info
     */
    public function loadPackage($hash)
    {
        $data = $this->_rmaData->decodeTrackingHash($hash);
        $package = [];
        if (!empty($data)) {
            $this->setData($data['key'], $data['id']);
            $this->setProtectCode($data['hash']);
            if ($rma = $this->_initRma()) {
                $package = $rma->getShippingLabel();
            }
        }
        return $package;
    }

    /**
     * Retrieve tracking info
     *
     * @return array
     */
    public function getTrackingInfo()
    {
        return $this->_trackingInfo;
    }

    /**
     * Instantiate RMA model
     *
     * @return \Magento\Rma\Model\Rma || false
     */
    protected function _initRma()
    {
        /* @var $model \Magento\Rma\Model\Rma */
        $model = $this->_rmaFactory->create();
        $rma = $model->load($this->getRmaId());
        if (!$rma->getEntityId() || $this->getProtectCode() !== $rma->getProtectCode()) {
            return false;
        }
        return $rma;
    }

    /**
     * Retrieve all tracking by RMA id
     *
     * @return array
     */
    public function getTrackingInfoByRma()
    {
        $shipTrack = [];
        $rma = $this->_initRma();
        if ($rma) {
            $increment_id = $rma->getIncrementId();
            $tracks = $rma->getTrackingNumbers();
            $trackingInfos = [];

            foreach ($tracks as $track) {
                $trackingInfos[] = $track->getNumberDetail();
            }
            $shipTrack[$increment_id] = $trackingInfos;
        }
        $this->_trackingInfo = $shipTrack;
        return $this->_trackingInfo;
    }

    /**
     * Retrieve tracking by tracking entity id
     *
     * @return array
     */
    public function getTrackingInfoByTrackId()
    {
        /** @var $track \Magento\Rma\Model\Shipping */
        $track = $this->_shippingFactory->create()->load($this->getTrackId());
        if ($track->getId() && $this->getProtectCode() === $track->getProtectCode()) {
            $this->_trackingInfo = [[$track->getNumberDetail()]];
        }
        return $this->_trackingInfo;
    }
}
