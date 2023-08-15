<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerWebsiteRestriction\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\WebsiteRestriction\Model\Config;
use Magento\WebsiteRestriction\Model\ConfigInterface;

/**
 * Disable restricted websites in Login as Customer pop-up.
 */
class StoreOptionsRestrictedPlugin
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
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @param ConfigInterface $config
     * @param ScopeConfigInterface $scopeConfig
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        ConfigInterface $config,
        ScopeConfigInterface $scopeConfig,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * Disable restricted websites in Login as Customer pop-up.
     *
     * @param OptionSourceInterface $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterToOptionArray(OptionSourceInterface $subject, array $result): array
    {
        foreach ($result as $websiteCode => $websiteData) {
            $websiteId = (int)$this->websiteRepository->get($websiteCode)->getId();
            if ($this->isRestrictionEnabled($websiteId) && $this->getRestrictionMode($websiteId) === 0) {
                foreach (array_keys($websiteData['value']) as $groupCode) {
                    $result[$websiteCode]['value'][$groupCode]['disabled'] = true;
                }
            }
        }

        return $result;
    }

    /**
     * If website restriction is active.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isRestrictionEnabled(int $websiteId): bool
    {
        return (bool)(int)$this->scopeConfig->getValue(
            Config::XML_PATH_RESTRICTION_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get website restriction mode.
     *
     * @param int $websiteId
     * @return int
     */
    private function getRestrictionMode(int $websiteId): int
    {
        return (int)$this->scopeConfig->getValue(
            Config::XML_PATH_RESTRICTION_MODE,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
