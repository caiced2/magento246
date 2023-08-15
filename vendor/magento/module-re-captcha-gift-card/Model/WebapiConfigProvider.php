<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Model;

use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\GiftCardAccount\Api\GuestGiftCardAccountManagementInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;
use Magento\ReCaptchaWebapiApi\Api\WebapiValidationConfigProviderInterface;
use Magento\GiftCardAccountGraphQl\Model\Resolver\RedeemGiftCard;
use Magento\GiftCardAccountGraphQl\Model\Resolver\ApplyGiftCardToCart;

/**
 * Provide Giftcard redeem related endpoint configuration.
 */
class WebapiConfigProvider implements WebapiValidationConfigProviderInterface
{
    /**
     * Service class list
     */
    private const SERVICE_CLASS_LIST = [
        RedeemGiftCard::class,
        ApplyGiftCardToCart::class
    ];

    /**
     * Api service class list
     */
    private const API_SERVICE_CLASS_LIST = [
        GiftCardAccountManagementInterface::class,
        GuestGiftCardAccountManagementInterface::class
    ];

    /**
     * Captcha id from config.
     */
    private const CAPTCHA_ID = 'giftcard';

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isEnabled;

    /**
     * @var ValidationConfigResolverInterface
     */
    private $configResolver;

    /**
     * @param IsCaptchaEnabledInterface $isEnabled
     * @param ValidationConfigResolverInterface $configResolver
     */
    public function __construct(IsCaptchaEnabledInterface $isEnabled, ValidationConfigResolverInterface $configResolver)
    {
        $this->isEnabled = $isEnabled;
        $this->configResolver = $configResolver;
    }

    /**
     * @inheritDoc
     */
    public function getConfigFor(EndpointInterface $endpoint): ?ValidationConfigInterface
    {
        return ($this->validateEndpointForWebApi($endpoint) || $this->validateEndpointForGraphQl($endpoint))
        && $this->isEnabled->isCaptchaEnabledFor(self::CAPTCHA_ID)
            ? $this->configResolver->get(self::CAPTCHA_ID)
            : null;
    }

    /**
     * Validates endpoint for GraphQl
     *
     * @param EndpointInterface $endpoint
     * @return bool
     */
    private function validateEndpointForGraphQl(EndpointInterface $endpoint): bool
    {
        return ($endpoint->getServiceMethod() === 'resolve') &&
            (in_array($endpoint->getServiceClass(), self::SERVICE_CLASS_LIST, true));
    }

    /**
     * Validates Endpoint for webapi
     *
     * @param EndpointInterface $endpoint
     * @return bool
     */
    private function validateEndpointForWebApi(EndpointInterface $endpoint): bool
    {
        return  ((($endpoint->getServiceMethod() === 'saveByQuoteId') &&
            ($endpoint->getServiceClass() ===
                GiftCardAccountManagementInterface::class))
            || (($endpoint->getServiceMethod() === 'addGiftCard') &&
                ($endpoint->getServiceClass() ===
                GuestGiftCardAccountManagementInterface::class ))
            || (($endpoint->getServiceMethod() === 'checkGiftCard') &&
            (in_array($endpoint->getServiceClass(), self::API_SERVICE_CLASS_LIST, true))));
    }
}
