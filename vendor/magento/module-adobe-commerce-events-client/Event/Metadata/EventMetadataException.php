<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Metadata;

use Magento\Framework\Exception\LocalizedException;

/**
 * An exception thrown in case when metadata couldn't be collected.
 */
class EventMetadataException extends LocalizedException
{
}
