<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer\PlaceOrder\Restriction;

use Magento\Authorization\Model\UserContextInterface;

class Api implements \Magento\Reward\Observer\PlaceOrder\RestrictionInterface
{
    /**
     * @var Frontend
     */
    protected $frontendRestriction;

    /**
     * @var Backend
     */
    protected $backendRestriction;

    /**
     * @var UserContextInterface
     */
    protected $userContext;

    /**
     * Backend user types
     *
     * @var int[]
     */
    protected $backendUsers = [
        UserContextInterface::USER_TYPE_ADMIN,
        UserContextInterface::USER_TYPE_INTEGRATION,
    ];

    /**
     * @param Frontend $frontend
     * @param Backend $backend
     * @param UserContextInterface $userContext
     */
    public function __construct(Frontend $frontend, Backend $backend, UserContextInterface $userContext)
    {
        $this->frontendRestriction = $frontend;
        $this->backendRestriction = $backend;
        $this->userContext = $userContext;
    }

    /**
     * Check if reward points operations is allowed
     *
     * @return bool
     */
    public function isAllowed()
    {
        return in_array($this->userContext->getUserType(), $this->backendUsers)
            ? $this->backendRestriction->isAllowed()
            : $this->frontendRestriction->isAllowed();
    }
}
