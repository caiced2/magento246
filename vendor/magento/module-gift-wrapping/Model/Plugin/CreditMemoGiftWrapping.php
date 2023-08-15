<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Model\Plugin;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;

class CreditMemoGiftWrapping
{
    /**
     * @var CreditmemoExtensionFactory
     */
    private $creditmemoExtensionFactory;

    /**
     * Init plugin
     *
     * @param CreditmemoExtensionFactory $creditmemoExtensionFactory
     */
    public function __construct(
        CreditmemoExtensionFactory $creditmemoExtensionFactory
    ) {
        $this->creditmemoExtensionFactory = $creditmemoExtensionFactory;
    }

    /**
     * Get Gift Wrapping
     *
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $creditMemo
     * @return CreditmemoInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        CreditmemoRepositoryInterface $subject,
        CreditmemoInterface $creditMemo
    ) {
        /** @var \Magento\Sales\Api\Data\CreditmemoExtension $extensionAttributes */
        $extensionAttributes = $creditMemo->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->creditmemoExtensionFactory->create();
        }

        $extensionAttributes->setGwBasePrice($creditMemo->getGwBasePrice());
        $extensionAttributes->setGwPrice($creditMemo->getGwPrice());
        $extensionAttributes->setGwItemsBasePrice($creditMemo->getGwItemsBasePrice());
        $extensionAttributes->setGwItemsPrice($creditMemo->getGwItemsPrice());
        $extensionAttributes->setGwCardBasePrice($creditMemo->getGwCardBasePrice());
        $extensionAttributes->setGwCardPrice($creditMemo->getGwCardPrice());
        $extensionAttributes->setGwBaseTaxAmount($creditMemo->getGwBaseTaxAmount());
        $extensionAttributes->setGwTaxAmount($creditMemo->getGwTaxAmount());
        $extensionAttributes->setGwItemsBaseTaxAmount($creditMemo->getGwItemsBaseTaxAmount());
        $extensionAttributes->setGwItemsTaxAmount($creditMemo->getGwItemsTaxAmount());
        $extensionAttributes->setGwCardBaseTaxAmount($creditMemo->getGwCardBaseTaxAmount());
        $extensionAttributes->setGwCardTaxAmount($creditMemo->getGwCardTaxAmount());

        $creditMemo->setExtensionAttributes($extensionAttributes);

        return $creditMemo;
    }

    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoSearchResultInterface $creditMemoSearchResult
     * @return CreditmemoSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        CreditmemoRepositoryInterface $subject,
        CreditmemoSearchResultInterface $creditMemoSearchResult
    ) {
        /** @var CreditmemoInterface $entity */
        foreach ($creditMemoSearchResult->getItems() as $creditMemo) {
            $this->afterGet($subject, $creditMemo);
        }
        return $creditMemoSearchResult;
    }
}
