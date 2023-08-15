<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Plugin\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\CustomerBalance\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Method\Free;

/**
 * Plugin for removing Store Credit if free payment method isn't available
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LayoutProcessorPlugin
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helperData
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helperData,
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
        $this->scopeConfig = $scopeConfig;
        $this->helperData = $helperData;
    }

    /**
     * Remove store credit component if free payment not allowed
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(LayoutProcessor $subject, $jsLayout)
    {
        $path = $this->arrayManager->findPath('storeCredit', $jsLayout);
        if ($path &&
            !$this->scopeConfig->isSetFlag(Free::XML_PATH_PAYMENT_FREE_ACTIVE)
        ) {
            $jsLayout = $this->arrayManager->remove($path, $jsLayout);
        }
        return $jsLayout;
    }
}
