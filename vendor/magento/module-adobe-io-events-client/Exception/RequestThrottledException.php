<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception thrown when a request fails due to throttling
 */
class RequestThrottledException extends LocalizedException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = "Request was throttled")
    {
        parent::__construct(__($message));
    }
}
