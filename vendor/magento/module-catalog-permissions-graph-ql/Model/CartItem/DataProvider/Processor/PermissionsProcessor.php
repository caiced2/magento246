<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\CartItem\DataProvider\Processor;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\CatalogPermissions\Helper\Data as CatalogPermissionsData;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Index;
use Magento\CatalogPermissionsGraphQl\Model\Customer\GroupProcessor;
use Magento\CatalogPermissionsGraphQl\Model\Store\StoreProcessor;
use Magento\Framework\App\ObjectManager;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\Processor\ItemDataProcessorInterface;
use Magento\CatalogPermissions\App\ConfigInterface;

class PermissionsProcessor implements ItemDataProcessorInterface
{
    /**
     * @var GroupProcessor
     */
    private $groupProcessor;

    /**
     * @var CatalogPermissionsData
     */
    private $catalogPermissionsData;

    /**
     * Catalog permissions config
     *
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var BulkPreloader
     */
    private $bulkPreloader;

    /**
     * @var bool|null
     */
    private $isAllowedForCustomerGroup = null;

    /**
     * @param Product $product
     * @param GroupProcessor $groupProcessor
     * @param StoreProcessor $storeProcessor
     * @param Index $index
     * @param CatalogPermissionsData $catalogPermissionsData
     * @param ConfigInterface $config
     * @param BulkPreloader|null $bulkPreloader
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        Product $product,
        GroupProcessor $groupProcessor,
        StoreProcessor $storeProcessor,
        Index $index,
        CatalogPermissionsData $catalogPermissionsData,
        ConfigInterface $config,
        BulkPreloader $bulkPreloader = null
    ) {
        $this->groupProcessor = $groupProcessor;
        $this->catalogPermissionsData = $catalogPermissionsData;
        $this->config = $config;
        $this->bulkPreloader = $bulkPreloader ?: ObjectManager::getInstance()->get(BulkPreloader::class);
    }

    /**
     * Process cart item data
     *
     * @param array $cartItemData
     * @param ContextInterface $context
     * @return array
     */
    public function process(array $cartItemData, ContextInterface $context): array
    {
        if ($this->config->isEnabled()) {
            $customerGroupId = $this->groupProcessor->getCustomerGroup($context);
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $permissionsIndex = $this->bulkPreloader->getBySku($cartItemData['sku'], $customerGroupId, $storeId);
            if ($this->isAllowedForCustomerGroup === null) {
                $this->isAllowedForCustomerGroup = $this->catalogPermissionsData->isAllowedCheckoutItems(
                    $storeId,
                    $customerGroupId
                );
            }
            if ($permissionsIndex) {
                if ($permissionsIndex['grant_checkout_items'] == Permission::PERMISSION_DENY
                    || ($permissionsIndex['grant_checkout_items'] != Permission::PERMISSION_ALLOW
                    && !$this->isAllowedForCustomerGroup)
                ) {
                    $cartItemData['grant_checkout'] = false;
                }
            } elseif (!$this->isAllowedForCustomerGroup) {
                $cartItemData['grant_checkout'] = false;
            }
        }

        return $cartItemData;
    }
}
