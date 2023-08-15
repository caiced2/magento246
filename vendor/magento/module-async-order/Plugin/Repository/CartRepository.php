<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Plugin\Repository;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Quote\Api\CartRepositoryInterface;

class CartRepository
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        DeploymentConfig $deploymentConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Around Get Active
     *
     * @param CartRepositoryInterface $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param array $sharedStoreIds
     * @return CartRepositoryInterface|mixed
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function aroundGetActive(
        CartRepositoryInterface $subject,
        \Closure $proceed,
        $cartId,
        array $sharedStoreIds = []
    ) {
        if ($this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $subject->get($cartId);
        }

        return $proceed($cartId, $sharedStoreIds);
    }
}
