<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;

/**
 * Adds reCaptcha configuration to checkout.
 */
class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     */
    public function __construct(
        IsCaptchaEnabledInterface $isCaptchaEnabled
    ) {

        $this->isCaptchaEnabled = $isCaptchaEnabled;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return [
            'recaptcha_giftcard' => $this->isCaptchaEnabled->isCaptchaEnabledFor('giftcard')
        ];
    }
}
