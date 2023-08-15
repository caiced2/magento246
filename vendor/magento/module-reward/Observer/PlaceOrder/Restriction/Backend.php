<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Observer\PlaceOrder\Restriction;

class Backend implements \Magento\Reward\Observer\PlaceOrder\RestrictionInterface
{
    /**
     * Reward data
     * @var \Magento\Reward\Helper\Data
     */
    protected $_helper;

    /**
     * Authoriztion interface
     *
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @param \Magento\Reward\Helper\Data $helper
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(
        \Magento\Reward\Helper\Data $helper,
        \Magento\Framework\AuthorizationInterface $authorization
    ) {
        $this->_helper = $helper;
        $this->_authorization = $authorization;
    }

    /**
     * Check if reward points operations is allowed
     *
     * @return bool
     */
    public function isAllowed()
    {
        return $this->_helper->isEnabledOnFront() && $this->_authorization->isAllowed(
            \Magento\Reward\Helper\Data::XML_PATH_PERMISSION_AFFECT
        );
    }
}
