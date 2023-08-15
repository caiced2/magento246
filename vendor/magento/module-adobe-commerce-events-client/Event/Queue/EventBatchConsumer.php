<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Queue;

use Magento\Framework\MessageQueue\BatchConsumer;

/**
 * This class is used to override interval setting for the original batch consumer.
 */
class EventBatchConsumer extends BatchConsumer
{

}
