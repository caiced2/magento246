<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\ResourceModel\Reward\History\Grid\Options;

use Magento\Reward\Model\Source\Website;

/**
 * @codeCoverageIgnore
 */
class Websites implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * System Store Model
     *
     * @var \Magento\Reward\Model\Source\Website
     */
    protected $_systemStore;

    /**
     * @param Website $systemStore
     */
    public function __construct(Website $systemStore)
    {
        $this->_systemStore = $systemStore;
    }

    /**
     * Return websites array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_systemStore->toOptionArray(false);
    }
}
