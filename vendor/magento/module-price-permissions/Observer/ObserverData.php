<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

class ObserverData
{
    /**
     * Edit Product Price flag
     *
     * @var bool
     */
    protected $canEditProductPrice = true;

    /**
     * Read Product Price flag
     *
     * @var bool
     */
    protected $canReadProductPrice = true;

    /**
     * Edit Product Status flag
     *
     * @var bool
     */
    protected $canEditProductStatus = true;

    /**
     * String representation of the default product price
     *
     * @var string
     */
    protected $defaultProductPriceString;

    /**
     * @return boolean
     */
    public function isCanEditProductPrice()
    {
        return $this->canEditProductPrice;
    }

    /**
     * @param boolean $canEditProductPrice
     * @return void
     */
    public function setCanEditProductPrice($canEditProductPrice)
    {
        $this->canEditProductPrice = $canEditProductPrice;
    }

    /**
     * @return bool
     */
    public function isCanReadProductPrice()
    {
        return $this->canReadProductPrice;
    }

    /**
     * @param bool $canReadProductPrice
     * @return void
     */
    public function setCanReadProductPrice($canReadProductPrice)
    {
        $this->canReadProductPrice = $canReadProductPrice;
    }

    /**
     * @return bool
     */
    public function isCanEditProductStatus()
    {
        return $this->canEditProductStatus;
    }

    /**
     * @param bool $canEditProductStatus
     * @return void
     */
    public function setCanEditProductStatus($canEditProductStatus)
    {
        $this->canEditProductStatus = $canEditProductStatus;
    }

    /**
     * @return string
     */
    public function getDefaultProductPriceString()
    {
        return $this->defaultProductPriceString;
    }

    /**
     * @param string $defaultProductPriceString
     * @return void
     */
    public function setDefaultProductPriceString($defaultProductPriceString)
    {
        $this->defaultProductPriceString = $defaultProductPriceString;
    }
}
