<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model\Resolver;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\RewardGraphQl\Model\Config;
use Magento\RewardGraphQl\Model\Formatter\Customer\FormatterInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Fetch customer reward points
 */
class CustomerRewardPoints implements ResolverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RewardFactory
     */
    private $rewardFactory;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var FormatterInterface
     */
    private $customerRewardPointsFormatter;

    /**
     * @param Config $config
     * @param RewardFactory $rewardFactory
     * @param CustomerRegistry $customerRegistry
     * @param FormatterInterface $customerRewardPointsFormatter
     */
    public function __construct(
        Config $config,
        RewardFactory $rewardFactory,
        CustomerRegistry $customerRegistry,
        FormatterInterface $customerRewardPointsFormatter
    ) {
        $this->config = $config;
        $this->rewardFactory = $rewardFactory;
        $this->customerRegistry = $customerRegistry;
        $this->customerRewardPointsFormatter = $customerRewardPointsFormatter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var int $currentWebsiteId */
        $currentWebsiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsite()->getId();

        if ($this->config->isDisabled($currentWebsiteId)) {
            return null;
        }

        /** @var Customer $customer */
        $currentCustomer = $this->customerRegistry->retrieve($context->getUserId());
        if (!$currentCustomer && !$currentCustomer->getId()) {
            throw new GraphQlInputException(
                __('Something went wrong while loading the customer.')
            );
        }

        /** @var StoreInterface $currentStore */
        $currentStore = $context->getExtensionAttributes()->getStore();
        /** @var Reward $rewardInstance */
        $rewardInstance = $this->createRewardInstance($currentCustomer, $currentStore);

        return $this->customerRewardPointsFormatter->format($currentCustomer, $currentStore, $rewardInstance);
    }

    /**
     * Create reward instance for the given customer in the given store
     *
     * @param Customer $customer
     * @param StoreInterface $store
     * @return Reward
     */
    private function createRewardInstance(Customer $customer, StoreInterface $store): Reward
    {
        return $this->rewardFactory->create()->setCustomer(
            $customer
        )->setWebsiteId(
            $store->getWebsite()->getId()
        )->loadByCustomer();
    }
}
