<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;

class GiftCardConfigProvider implements ConfigProviderInterface
{
    /**
     * Management service
     *
     * @var GiftCardAccountManagementInterface
     */
    protected $managementService;

    /**
     * Checkout session
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var GiftCardAccountInterface
     */
    protected $giftCardAccount;

    /**
     * @param GiftCardAccountManagementInterface $managementService
     * @param Session $checkoutSession
     */
    public function __construct(
        GiftCardAccountManagementInterface $managementService,
        Session $checkoutSession
    ) {
        $this->managementService = $managementService;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'giftCardAccount' => [
                    'hasUsage' => $this->hasUsage(),
                    'amount'   => $this->getAmount(),
                    'cards'    => $this->getGiftCardAccount()->getGiftCards(),
                    "available_amount" => $this->getGiftCardAccount()->getGiftCardsAmount()
                ]
            ]
        ];
    }

    /**
     * Return giftCardAccount by quote id
     *
     * @return GiftCardAccountInterface
     */
    protected function getGiftCardAccount()
    {
        if ($this->giftCardAccount === null) {
            $this->giftCardAccount = $this->managementService->getListByQuoteId($this->checkoutSession->getQuoteId());
        }
        return $this->giftCardAccount;
    }

    /**
     * Check if giftCardAccount applied
     *
     * @return bool
     */
    protected function hasUsage()
    {
        return !empty($this->getGiftCardAccount()->getGiftCards());
    }

    /**
     * Return giftCardAccount amount
     *
     * @return float
     */
    protected function getAmount()
    {
        return $this->getGiftCardAccount()->getGiftCardsAmountUsed();
    }
}
