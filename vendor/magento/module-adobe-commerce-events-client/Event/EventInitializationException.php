<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception thrown in case an event fails to initialize
 *
 * @api
 * @since 1.1.0
 */
class EventInitializationException extends LocalizedException
{
}
