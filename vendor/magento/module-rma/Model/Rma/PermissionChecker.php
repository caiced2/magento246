<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Rma;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\StateException;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Rma;

class PermissionChecker
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var Data
     */
    private $rmaHelper;

    /**
     * @param UserContextInterface $userContext
     * @param Data $rmaHelper
     */
    public function __construct(
        UserContextInterface $userContext,
        Data $rmaHelper
    ) {
        $this->userContext = $userContext;
        $this->rmaHelper = $rmaHelper;
    }

    /**
     * Whether the user is the owner of the RMA
     *
     * @param Rma $rma
     * @return bool
     */
    public function isRmaOwner(Rma $rma)
    {
        return $this->isCustomerContext()
            ? $rma->getCustomerId() == $this->userContext->getUserId()
            : true;
    }

    /**
     * Verifies availability of rma for customer context
     *
     * @return bool
     * @throws \Magento\Framework\Exception\StateException
     */
    public function checkRmaForCustomerContext()
    {
        if ($this->isCustomerContext() && !$this->rmaHelper->isEnabled()) {
            throw new StateException(__('The service is unknown. Verify the service and try again.'));
        }
        return true;
    }

    /**
     * Whether is the customer context
     *
     * @return bool
     */
    public function isCustomerContext()
    {
        return $this->userContext->getUserType() == UserContextInterface::USER_TYPE_CUSTOMER;
    }
}
