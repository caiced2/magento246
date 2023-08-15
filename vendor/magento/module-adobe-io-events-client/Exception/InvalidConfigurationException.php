<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception thrown when a configuration is invalid or not fetchable
 */
class InvalidConfigurationException extends LocalizedException
{
}
