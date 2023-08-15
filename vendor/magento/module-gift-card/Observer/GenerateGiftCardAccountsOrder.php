<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\GiftCard\Model\AccountGenerator;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductGiftCard;
use Magento\GiftCard\Model\Giftcard;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;

/**
 * Gift cards generator observer called on order after save
 */
class GenerateGiftCardAccountsOrder implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var AccountGenerator
     */
    private $accountGenerator;

    /**
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     * @param AccountGenerator $accountGenerator
     */
    public function __construct(
        ManagerInterface $eventManager,
        ScopeConfigInterface $scopeConfig,
        AccountGenerator $accountGenerator
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->accountGenerator = $accountGenerator;
    }

    /**
     * Generate gift card accounts after order save.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var \Magento\Sales\Model\Order $order */
        $order =  $event->getOrder();

        $requiredStatus = (int)$this->scopeConfig->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );

        if ($requiredStatus === Item::STATUS_INVOICED) {
            return;
        }

        /** @var Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getProductType() !== ProductGiftCard::TYPE_GIFTCARD) {
                continue;
            }

            $qty = $orderItem->getQtyOrdered();
            $options = $orderItem->getProductOptions();
            if (isset($options['giftcard_created_codes'])) {
                $qty -= count($options['giftcard_created_codes']);
            }

            $this->accountGenerator->generate($orderItem, (int)$qty, $options);
        }
    }
}
