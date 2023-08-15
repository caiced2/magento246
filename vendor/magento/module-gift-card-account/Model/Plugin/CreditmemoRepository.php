<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoExtension;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Plugin for Creditmemo repository.
 */
class CreditmemoRepository
{
    /**
     * @var CreditmemoExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $giftCardAccountRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param CreditmemoExtensionFactory $extensionFactory
     * @param GiftCardAccountRepositoryInterface $giftCardAccountRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CreditmemoExtensionFactory $extensionFactory,
        GiftCardAccountRepositoryInterface $giftCardAccountRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->giftCardAccountRepository = $giftCardAccountRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get order by ID
     *
     * @param int $orderId
     *
     * @return OrderInterface
     */
    private function getOrderById(int $orderId): OrderInterface
    {
        return $this->orderRepository->get($orderId);
    }

    /**
     * Get order items by ID
     *
     * @param OrderInterface $order
     *
     * @return OrderItemInterface[]
     */
    private function getOrderItems(OrderInterface $order): array
    {
        $orderItemsById = [];
        foreach ($order->getItems() as $item) {
            $orderItemsById[$item->getItemId()] = $item;
        }

        return $orderItemsById;
    }

    /**
     * Removed gift card account
     *
     * @param array $codes
     * @param int $qty
     *
     * @return void
     */
    private function removeGiftCardAccount(array $codes, int $qty): void
    {
        $accounts = $this->giftCardAccountRepository->getList(
            $this->criteriaBuilder->addFilter('code', $codes, 'in')
                ->setPageSize($qty)
                ->create()
        )->getItems();
        foreach ($accounts as $account) {
            $this->giftCardAccountRepository->delete($account);
        }
    }

    /**
     * Get credit memo with extensionAttributes
     *
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $entity
     *
     * @return CreditmemoInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(CreditmemoRepositoryInterface $subject, CreditmemoInterface $entity): CreditmemoInterface
    {
        /** @var CreditmemoExtension $extensionAttributes */
        $extensionAttributes = $entity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setBaseGiftCardsAmount($entity->getBaseGiftCardsAmount());
        $extensionAttributes->setGiftCardsAmount($entity->getGiftCardsAmount());
        $entity->setExtensionAttributes($extensionAttributes);
        return $entity;
    }

    /**
     * Get credit memo list
     *
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoSearchResultInterface $entities
     *
     * @return CreditmemoSearchResultInterface
     */
    public function afterGetList(CreditmemoRepositoryInterface $subject, CreditmemoSearchResultInterface $entities)
    {
        /** @var CreditmemoInterface $entity */
        foreach ($entities->getItems() as $entity) {
            $this->afterGet($subject, $entity);
        }

        return $entities;
    }

    /**
     * Remove gift card account for refund
     *
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $entity
     *
     * @return CreditmemoInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CreditmemoRepositoryInterface $subject, CreditmemoInterface $entity): CreditmemoInterface
    {
        $order = ($entity->getOrder()) ?: $this->getOrderById((int)$entity->getOrderId());
        $orderItems = $this->getOrderItems($order);
        if ($orderItems) {
            foreach ($entity->getItems() as $creditMemoItem) {
                $orderItem = $orderItems[$creditMemoItem->getOrderItemId()];
                if ($orderItem->getProductOptionByCode('giftcard_created_codes')) {
                    $this->removeGiftCardAccount(
                        $orderItem->getProductOptionByCode('giftcard_created_codes'),
                        abs((int)$creditMemoItem->getQty())
                    );
                }
            }
        }

        return $entity;
    }
}
