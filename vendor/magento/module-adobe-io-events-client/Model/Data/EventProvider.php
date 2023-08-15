<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data;

use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\Framework\DataObject;

/**
 * Event provider data object
 *
 * @api
 * @since 1.1.0
 */
class EventProvider extends DataObject implements EventProviderInterface
{
    private const ID = 'id';
    private const LABEL = 'label';
    private const DESCRIPTION = 'description';
    private const SOURCE = 'source';
    private const PUBLISHER = 'publisher';
    private const INSTANCE_ID = 'instance_id';

    /**
     * Return ID
     *
     * @return string
     */
    public function getId(): string
    {
        return (string)$this->getData(self::ID);
    }

    /**
     * Return Label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return (string)$this->getData(self::LABEL);
    }

    /**
     * Return Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    /**
     * Return Source
     *
     * @return string
     */
    public function getSource(): string
    {
        return (string)$this->getData(self::SOURCE);
    }

    /**
     * Return Publisher
     *
     * @return string
     */
    public function getPublisher(): string
    {
        return (string)$this->getData(self::PUBLISHER);
    }

    /**
     * Get the provider's instance id
     *
     * @return string
     */
    public function getInstanceId(): string
    {
        return (string)$this->getData(self::INSTANCE_ID);
    }
}
