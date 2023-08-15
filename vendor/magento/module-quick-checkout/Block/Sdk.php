<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Block;

use Magento\Framework\View\Element\Template;
use Magento\QuickCheckout\Model\Config;
use Magento\Framework\View\Element\Template\Context;

/**
 * Render SDK script tag
 *
 * @api
 */
class Sdk extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Get SDK URL
     *
     * @return string
     */
    public function getSdkUrl() : string
    {
        return $this->config->getSdkUrl();
    }

    /**
     * Get publishable key
     *
     * @return string
     */
    public function getPublishableKey(): string
    {
        return (string)$this->config->getPublishableKey();
    }
}
