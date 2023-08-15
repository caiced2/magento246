<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Model\Entity\Attribute\Backend\Plugin;

use Magento\PricePermissions\Observer\ObserverData;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend as EavAbstractBackend;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Plugin for Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
 */
class AbstractBackend
{
    /**
     * @var ObserverData
     */
    private $observerData;

    /**
     * @param ObserverData $observerData
     */
    public function __construct(ObserverData $observerData)
    {
        $this->observerData = $observerData;
    }

    /**
     * Set default price of a new product for user not allowed to read product prices
     *
     * @param EavAbstractBackend $subject
     * @param mixed $object
     * @return void
     */
    public function beforeValidate(EavAbstractBackend $subject, $object)
    {
        if ($object instanceof ProductInterface
            && !$this->observerData->isCanReadProductPrice()
            && $object->isObjectNew()
            && $subject->getAttribute()->getFrontendInput() === 'price'
        ) {
            $object->setData(
                $subject->getAttribute()->getAttributeCode(),
                $this->observerData->getDefaultProductPriceString()
            );
        }
    }
}
