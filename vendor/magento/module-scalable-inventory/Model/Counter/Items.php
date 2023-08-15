<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Model\Counter;

use Magento\ScalableInventory\Api\Counter\ItemInterface;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Items implements ItemsInterface
{
    /**
     * @var ItemInterface[]
     */
    private $items;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var string
     */
    private $operator;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setWebsiteId($websiteId)
    {
        $this->websiteId = $websiteId;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getWebsiteId()
    {
        return $this->websiteId;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getOperator()
    {
        return $this->operator;
    }
}
