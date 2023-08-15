<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Model\Counter;

use Magento\ScalableInventory\Api\Counter\ItemInterface;

/**
 * @codeCoverageIgnore
 * @deprecated split database solution is deprecated and will be removed
 */
class Item implements ItemInterface
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var float
     */
    private $qty;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getQty()
    {
        return $this->qty;
    }
}
