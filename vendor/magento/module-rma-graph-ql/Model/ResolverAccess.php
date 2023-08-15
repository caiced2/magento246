<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Rma\Helper\Data as RmaHelper;

/**
 * Check access to resolvers
 */
class ResolverAccess
{
    /**
     * @var RmaHelper
     */
    private $helper;

    /**
     * @param RmaHelper $helper
     */
    public function __construct(RmaHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Check access to resolvers
     *
     * @param ContextInterface $context
     * @throws GraphQlAuthorizationException
     * @throws LocalizedException
     */
    public function isAllowed(ContextInterface $context): void
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (!$this->helper->isEnabled()) {
            throw new GraphQlNoSuchEntityException(__('RMA is disabled.'));
        }
    }
}
