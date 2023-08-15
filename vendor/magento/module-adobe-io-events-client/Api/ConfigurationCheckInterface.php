<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Api;

/**
 * Configuration validator
 *
 * @api
 * @since 1.1.0
 */
interface ConfigurationCheckInterface
{
    /**
     * Checks configuration and returns success/failure results for each component
     *
     * @return \Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterface
     */
    public function checkConfiguration(): \Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterface;
}
