<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration for customer address search.
 */
class Config
{
    /**
     * Configuration values of customer address search
     */
    private const ENABLE_ADDRESS_SEARCH_CONFIG_PATH = 'checkout/options/enable_address_search';
    private const ADDRESS_SEARCH_LIMIT_CONFIG_PATH = 'checkout/options/customer_address_limit';

    /**
     * Configuration value of whether to display billing address on payment method or payment page
     */
    private const DISPLAY_BILLING_ADDRESS_ON_CONFIG_PATH = 'checkout/options/display_billing_address_on';

    /**
     * Configuration value for address search results page size.
     */
    private const ADDRESS_SEARCH_PAGE_SIZE = 'checkout/options/address_search_page_size';

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get information if address search is enabled.
     *
     * @return bool
     */
    public function isEnabledAddressSearch(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::ENABLE_ADDRESS_SEARCH_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get limit of customer addresses from which to display address search instead of address grid.
     *
     * @return int
     */
    public function getSearchLimit(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::ADDRESS_SEARCH_LIMIT_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * If display billing address on payment page is available, otherwise should be display on payment method
     *
     * @return bool
     */
    public function isDisplayBillingOnPaymentPageAvailable(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::DISPLAY_BILLING_ADDRESS_ON_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns address search page size.
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return (int)$this->scopeConfig->getValue(self::ADDRESS_SEARCH_PAGE_SIZE);
    }
}
