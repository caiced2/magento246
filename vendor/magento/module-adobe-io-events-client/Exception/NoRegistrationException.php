<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception thrown when a registration isn't found
 */
class NoRegistrationException extends LocalizedException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = "No registration found for this event code")
    {
        parent::__construct(__($message));
    }
}
