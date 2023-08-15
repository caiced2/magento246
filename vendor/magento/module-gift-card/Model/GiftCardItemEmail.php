<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Sends email with info about created gift cards.
 */
class GiftCardItemEmail
{
    /**
     * Gift card data
     *
     * @var \Magento\GiftCard\Helper\Data
     */
    private $giftCardData;

    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @param CurrencyInterface $localeCurrency
     * @param TransportBuilder $transportBuilder
     * @param \Magento\GiftCard\Helper\Data $giftCardData
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CurrencyInterface $localeCurrency,
        TransportBuilder $transportBuilder,
        \Magento\GiftCard\Helper\Data $giftCardData,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->localeCurrency = $localeCurrency;
        $this->transportBuilder = $transportBuilder;
        $this->giftCardData = $giftCardData;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Sends gift card item email.
     *
     * @param OrderItem $giftCardOrderItem
     * @param array $codes
     * @param int $isRedeemable
     * @param float|null $amount
     * @param int $generatedCodesCount
     * @return void
     */
    public function send(
        OrderItem $giftCardOrderItem,
        array $codes,
        int $generatedCodesCount,
        int $isRedeemable,
        $amount
    ) {
        $storeId = $giftCardOrderItem->getStoreId();
        $sender = $giftCardOrderItem->getProductOptionByCode('giftcard_sender_name');
        $senderName = $giftCardOrderItem->getProductOptionByCode('giftcard_sender_name');
        $senderEmail = $giftCardOrderItem->getProductOptionByCode('giftcard_sender_email');
        if ($senderEmail) {
            $sender = "{$sender} <{$senderEmail}>";
        }

        /** @var \Magento\GiftCard\Block\Generated $codeList */
        $codeList = $this->giftCardData->getEmailGeneratedItemsBlock()
            ->setCodes($codes)
            ->setArea(Area::AREA_FRONTEND)
            ->setIsRedeemable($isRedeemable)
            ->setStore($giftCardOrderItem->getStore());

        $baseCurrencyCode = $giftCardOrderItem->getStore()
            ->getBaseCurrencyCode();
        $balance = $this->localeCurrency->getCurrency($baseCurrencyCode)
            ->toCurrency($amount);

        $templateData = [
            'name' => $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_name'),
            'email' => $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_email'),
            'sender_name_with_email' => $sender,
            'sender_name' => $senderName,
            'gift_message' => $giftCardOrderItem->getProductOptionByCode('giftcard_message'),
            'giftcards' => $codeList->toHtml(),
            'balance' => $balance,
            'is_multiple_codes' => 1 < $generatedCodesCount,
            'store' => $giftCardOrderItem->getStore(),
            'store_name' => $giftCardOrderItem->getStore()->getName(),
            'is_redeemable' => $isRedeemable,
        ];

        $emailIdentity = $this->scopeConfig->getValue(
            Giftcard::XML_PATH_EMAIL_IDENTITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $templateOptions = [
            'area' => Area::AREA_FRONTEND,
            'store' => $storeId,
        ];
        $recipientAddress = $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_email');
        $recipientName = $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_name');
        $template = $giftCardOrderItem->getProductOptionByCode('giftcard_email_template');

        $transport = $this->transportBuilder->setTemplateIdentifier($template)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateData)
            ->setFrom($emailIdentity)
            ->addTo($recipientAddress, $recipientName)
            ->getTransport();
        $transport->sendMessage();
    }
}
