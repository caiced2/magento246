<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Model;

use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Class EmailManagement
 */
class EmailManagement
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $localeCurrency;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager = null;

    /**
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SendEmail constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->localeCurrency = $localeCurrency;
        $this->senderResolver = $senderResolver;
        $this->logger = $logger;
    }

    /**
     * @param GiftCardAccountInterface $giftcardAccount
     * @return bool
     */
    public function sendEmail(GiftCardAccountInterface $giftcardAccount)
    {
        $recipientName = $giftcardAccount->getRecipientName();
        $recipientEmail = $giftcardAccount->getRecipientEmail();
        $recipientStore = $giftcardAccount->getRecipientStore();
        if ($recipientStore == null) {
            $recipientStore = $this->storeManager->getWebsite($giftcardAccount->getWebsiteId())->getDefaultStore();
        } else {
            /** @var \Magento\Store\Api\Data\StoreInterface $recipientStore */
            $recipientStore = $this->storeManager->getStore($recipientStore);
        }
        $storeId = $recipientStore->getId();
        $balance = $giftcardAccount->getBalance();
        $code = $giftcardAccount->getCode();
        $balance = $this->localeCurrency->getCurrency($recipientStore->getBaseCurrencyCode())->toCurrency($balance);
        /** @var \Magento\Framework\Mail\TransportInterface $transport */
        $transport = $this->transportBuilder->setTemplateIdentifier(
            $this->scopeConfig->getValue(
                'giftcard/giftcardaccount_email/template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
        )->setTemplateVars(
            [
                'name' => $recipientName,
                'code' => $code,
                'balance' => $balance,
                'store' => $recipientStore,
                'store_name' => $recipientStore->getName(),
            ]
        )->setFrom(
            $this->senderResolver->resolve(
                $this->scopeConfig->getValue(
                    'giftcard/giftcardaccount_email/identity',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                $recipientStore->getCode()
            )
        )->addTo(
            $recipientEmail,
            $recipientName
        )->getTransport();

        try {
            $transport->sendMessage();
            $giftcardAccount->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_SENT)->save();
        } catch (\Magento\Framework\Exception\MailException $e) {
            $this->logger->critical($e);
            return false;
        }

        return true;
    }
}
