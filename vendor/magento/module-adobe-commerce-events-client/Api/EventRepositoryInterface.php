<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Api;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Event storage interface
 *
 * @api
 * @since 1.1.0
 */
interface EventRepositoryInterface
{
    /**
     * Returns event by event id.
     *
     * @param int $entityId
     * @return EventInterface
     */
    public function getById(int $entityId): EventInterface;

    /**
     * Saving the event.
     *
     * @param EventInterface $event
     * @return EventInterface
     * @throws AlreadyExistsException
     */
    public function save(EventInterface $event): EventInterface;
}
