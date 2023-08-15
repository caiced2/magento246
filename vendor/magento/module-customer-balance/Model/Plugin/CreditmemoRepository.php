<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Plugin;

use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * Class CreditmemoRepository
 */
class CreditmemoRepository
{
    /**
     * @var \Magento\Sales\Api\Data\CreditmemoExtensionFactory
     */
    private $extensionFactory;

    /**
     * Init plugin
     *
     * @param \Magento\Sales\Api\Data\CreditmemoExtensionFactory $creditmemoExtensionFactory
     */
    public function __construct(
        \Magento\Sales\Api\Data\CreditmemoExtensionFactory $creditmemoExtensionFactory
    ) {
        $this->extensionFactory = $creditmemoExtensionFactory;
    }

    /**
     * Get creditmemo customer balance
     *
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $resultEntity
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $subject,
        \Magento\Sales\Api\Data\CreditmemoInterface $resultEntity
    ) {
        /** @var \Magento\Sales\Api\Data\CreditmemoExtension $extensionAttributes */
        $extensionAttributes = $resultEntity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setBaseCustomerBalanceAmount($resultEntity->getBaseCustomerBalanceAmount());
        $extensionAttributes->setCustomerBalanceAmount($resultEntity->getCustomerBalanceAmount());
        $resultEntity->setExtensionAttributes($extensionAttributes);

        return $resultEntity;
    }

    /**
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\CreditmemoSearchResultInterface $resultCreditmemo
     * @return \Magento\Sales\Api\Data\CreditmemoSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $subject,
        \Magento\Sales\Api\Data\CreditmemoSearchResultInterface $resultCreditmemo
    ) {
        /** @var CreditmemoInterface $creditmemo */
        foreach ($resultCreditmemo->getItems() as $creditmemo) {
            $this->afterGet($subject, $creditmemo);
        }
        return $resultCreditmemo;
    }
}
