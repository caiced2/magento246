<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Model\QuickCheck;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;

/**
 *  ReCaptcha Resolover for Giftcard Quickcheck plugin
 */
class ReCaptchaResolver
{
    /**
     * Resolves ReCaptCha
     *
     * @param RequestInterface $request
     * @return string|null
     * @throws InputException
     */
    public function resolve(RequestInterface $request): ?string
    {
        $reCaptchaParam = $request->getParam(CaptchaResponseResolverInterface::PARAM_RECAPTCHA);
        if (empty($reCaptchaParam)) {
            throw new InputException(__('Can not resolve reCAPTCHA response.'));
        }
        return $reCaptchaParam;
    }
}
