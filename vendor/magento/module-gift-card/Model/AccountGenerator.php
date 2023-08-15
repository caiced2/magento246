<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\GiftCardAccount\Model\Pool;
use Magento\Framework\App\ObjectManager;

/**
 * Generates giftcard accounts for giftcard order item.
 */
class AccountGenerator
{
    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Url model
     *
     * @var UrlInterface
     */
    private $urlModel;

    /**
     * Order Item Repository
     *
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @var GiftCardItemEmail
     */
    private $cardItemEmail;

    /**
     * @var Pool
     */
    private $giftCardAccountPool;

    /**
     * @param EventManagerInterface $eventManager
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ManagerInterface $messageManager
     * @param UrlInterface $urlModel
     * @param ScopeConfigInterface $scopeConfig
     * @param GiftCardItemEmail $cardItemEmail
     * @param Pool|null $giftCardAccountPool
     */
    public function __construct(
        EventManagerInterface $eventManager,
        OrderItemRepositoryInterface $orderItemRepository,
        ManagerInterface $messageManager,
        UrlInterface $urlModel,
        ScopeConfigInterface $scopeConfig,
        GiftCardItemEmail $cardItemEmail,
        Pool $giftCardAccountPool = null
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->messageManager = $messageManager;
        $this->urlModel = $urlModel;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->cardItemEmail = $cardItemEmail;
        $this->giftCardAccountPool = $giftCardAccountPool
            ?: ObjectManager::getInstance()->get(Pool::class);
    }

    /**
     * Generates giftcard accounts.
     *
     * @param OrderItem $orderItem
     * @param int $qty
     * @param array $options
     * @return void
     */
    public function generate(OrderItem $orderItem, int $qty, array $options)
    {
        if ($qty <= 0) {
            return;
        }

        $hasFailedCodes = false;
        $isRedeemable = (int)$orderItem->getProductOptionByCode('giftcard_is_redeemable') ?? 0;
        $lifetime = $orderItem->getProductOptionByCode('giftcard_lifetime') ?? 0;
        $amount = $orderItem->getBasePrice();
        $websiteId = $orderItem->getStore()->getWebsiteId();

        $data = new \Magento\Framework\DataObject();
        $data->setWebsiteId($websiteId)
            ->setAmount($amount)
            ->setLifetime($lifetime)
            ->setIsRedeemable($isRedeemable)
            ->setOrderItem($orderItem);

        $codes =  $options['giftcard_created_codes'] ?? [];
        $generatedCodesCount = 0;

        for ($i = 0; $i < $qty; $i++) {
            try {
                $code = new \Magento\Framework\DataObject();
                if ($this->giftCardAccountPool->getPoolUsageInfo()->getPercent() == 100) {
                    $this->giftCardAccountPool->generatePool();
                }
                $this->eventManager->dispatch(
                    'magento_giftcardaccount_create',
                    ['request' => $data, 'code' => $code]
                );
                $codes[] = $code->getCode();
                $generatedCodesCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $hasFailedCodes = true;
            }
        }

        if ($generatedCodesCount && $orderItem->getProductOptionByCode('giftcard_recipient_email')) {
            $this->cardItemEmail->send(
                $orderItem,
                $codes,
                $generatedCodesCount,
                $isRedeemable,
                $amount
            );
            $options['email_sent'] = 1;
        }

        $options['giftcard_created_codes'] = $codes;
        $orderItem->setProductOptions($options);
        // order item could be saved later after this order even
        if ($orderItem->getId()) {
            $this->orderItemRepository->save($orderItem);
        }

        if ($hasFailedCodes) {
            $url = $this->urlModel->getUrl('adminhtml/giftcardaccount');
            $message = __(
                'Some gift card accounts were not created properly. '
                . 'You can create gift card accounts manually <a href="%1">here</a>.',
                $url
            );

            $this->messageManager->addErrorMessage($message);
        }
    }
}
