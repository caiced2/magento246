<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerWebsiteRestriction\Model\Resolver;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\LoginAsCustomer\Model\IsLoginAsCustomerEnabledForCustomerResultFactory;
use Magento\LoginAsCustomerApi\Api\Data\IsLoginAsCustomerEnabledForCustomerResultInterface;
use Magento\LoginAsCustomerApi\Api\IsLoginAsCustomerEnabledForCustomerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\WebsiteRestriction\Model\Config;
use Magento\WebsiteRestriction\Model\ConfigInterface;

/**
 * Is Login as Customer is not restricted for website
 */
class IsLoginAsCustomerNotRestrictedForWebsite implements IsLoginAsCustomerEnabledForCustomerInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var IsLoginAsCustomerEnabledForCustomerResultFactory
     */
    private $resultFactory;

    /**
     * @param ConfigInterface $config
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param RequestInterface $request
     * @param IsLoginAsCustomerEnabledForCustomerResultFactory $resultFactory
     */
    public function __construct(
        ConfigInterface $config,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        RequestInterface $request,
        IsLoginAsCustomerEnabledForCustomerResultFactory $resultFactory
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(int $customerId): IsLoginAsCustomerEnabledForCustomerResultInterface
    {
        $messages = [];
        $storeId = (int)$this->request->getParam('store_id') ?: $this->getStoreIdByCustomerId($customerId);
        if ($this->config->isRestrictionEnabled($storeId) && $this->getRestrictionMode($storeId) === 0) {
            $messages[] = __('Login as Customer cannot be logged in restricted website.');
        }

        return $this->resultFactory->create(['messages' => $messages]);
    }

    /**
     * Returns customer's store id
     *
     * @param int $customerId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreIdByCustomerId(int $customerId): int
    {
        $customer = $this->customerRepository->getById($customerId);
        $storeId = (int)$customer->getStoreId();
        return $storeId;
    }

    /**
     * Get store restriction mode.
     *
     * @param int $storeId
     * @return int
     */
    private function getRestrictionMode(int $storeId): int
    {
        return (int)$this->scopeConfig->getValue(
            Config::XML_PATH_RESTRICTION_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
