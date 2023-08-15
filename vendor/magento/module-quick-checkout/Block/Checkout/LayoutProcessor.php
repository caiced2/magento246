<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\QuickCheckout\Model\Config;

class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * LayoutProcessor constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {
        if (!$this->config->isEnabled()) {
            // phpcs:disable
            unset($jsLayout['components']['checkout']['children']['steps']['children']['bolt-logout-info']);
            unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['before-form']['children']['bolt-address-list']);
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children']['quick_checkout']);
            // phpcs:enable
        }

        return $jsLayout;
    }
}
