<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CheckoutStaging\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoresConfig;

class PreviewQuotaManager
{
    const LIFETIME = 60;

    const QUOTA_LIFETIME_CONFIG_KEY = 'checkout/cart/preview_quota_lifetime';

    /**
     * @var StoresConfig
     */
    private $storesConfig;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ResourceModel\PreviewQuota\CollectionFactory
     */
    private $previewQuotasCollFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * PreviewQuotaManager constructor.
     * @param StoresConfig $storesConfig
     * @param CartRepositoryInterface $cartRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResourceModel\PreviewQuota\CollectionFactory $previewQuotasCollFactory
     * @param DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        StoresConfig $storesConfig,
        CartRepositoryInterface $cartRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceModel\PreviewQuota\CollectionFactory $previewQuotasCollFactory,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->storesConfig = $storesConfig;
        $this->cartRepository = $cartRepository;
        $this->previewQuotasCollFactory = $previewQuotasCollFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @return void
     */
    public function flushOutdated()
    {
        /** @var ResourceModel\PreviewQuota\Collection $previewQuotas */
        $previewQuotas = $this->previewQuotasCollFactory->create();

        $ids = $previewQuotas->getAllIds();
        if (empty($ids)) {
            return;
        }

        $lifetimes = $this->storesConfig->getStoresConfigByPath(
            self::QUOTA_LIFETIME_CONFIG_KEY
        );
        foreach ($lifetimes as $storeId => $lifetime) {
            $now = $this->dateTimeFactory->create(
                'now',
                new \DateTimeZone('UTC')
            )->sub(
                new \DateInterval(
                    sprintf('PT%sS', $lifetime*self::LIFETIME)
                )
            );

            $this->searchCriteriaBuilder->addFilter('entity_id', $ids, 'in');
            $this->searchCriteriaBuilder->addFilter(
                CartInterface::KEY_STORE_ID,
                $storeId,
                'eq'
            );
            $this->searchCriteriaBuilder->addFilter(
                CartInterface::KEY_UPDATED_AT,
                $now->format('Y-m-d H:i:s'),
                'to'
            );

            $carts = $this->cartRepository->getList(
                $this->searchCriteriaBuilder->create()
            )->getItems();

            foreach ($carts as $cart) {
                $this->cartRepository->delete($cart);
            }
        }
    }
}
