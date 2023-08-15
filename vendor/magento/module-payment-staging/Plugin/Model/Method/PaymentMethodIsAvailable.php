<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaymentStaging\Plugin\Model\Method;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class PaymentMethodIsAvailable
 */
class PaymentMethodIsAvailable
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * PaymentMethodIsAvailable constructor.
     *
     * @param VersionManager $versionManager
     */
    public function __construct(
        VersionManager $versionManager
    ) {
        $this->versionManager = $versionManager;
    }

    /**
     * @param MethodInterface $subject
     * @param \Closure $proceed
     * @param CartInterface $quote
     * @return bool
     */
    public function aroundIsAvailable(
        MethodInterface $subject,
        \Closure $proceed,
        CartInterface $quote = null
    ) {
        if ($this->versionManager->isPreviewVersion() && !$subject->isOffline()) {
            return false;
        }

        return $proceed($quote);
    }
}
