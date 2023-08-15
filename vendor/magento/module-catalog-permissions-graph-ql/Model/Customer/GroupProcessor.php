<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\GroupManagement;
use Magento\GraphQl\Model\Query\ContextInterface;

class GroupProcessor
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var GroupManagement
     */
    private $groupManagement;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param GroupManagement $groupManagement
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        GroupManagement $groupManagement
    ) {
        $this->customerRepository = $customerRepository;
        $this->groupManagement = $groupManagement;
    }

    /**
     * Get the customer group id based on context
     *
     * @param ContextInterface $context
     * @return int
     */
    public function getCustomerGroup(ContextInterface $context = null): int
    {
        try {
            if ($context && $context->getExtensionAttributes()->getIsCustomer() === true) {
                $customerGroupId = $context->getExtensionAttributes()->getCustomerGroupId();
                if ($customerGroupId === null) {
                    $customerGroupId = (int)$this->customerRepository->getById($context->getUserId())->getGroupId();
                }
            } else {
                $customerGroupId = GroupInterface::NOT_LOGGED_IN_ID;
            }
        } catch (\Exception $e) {
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $customerGroupId = (int)$this->getDefaultCustomerGroupId($storeId);
        }

        return $customerGroupId;
    }

    /**
     * Get default customer group id
     *
     * @param int $storeId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getDefaultCustomerGroupId(int $storeId)
    {
        return $this->groupManagement->getDefaultGroup($storeId)->getId() ?? 0;
    }
}
