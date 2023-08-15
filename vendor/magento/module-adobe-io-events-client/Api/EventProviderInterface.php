<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Api;

/**
 * Interface for the event provider data object
 *
 * @api
 * @since 1.1.0
 */
interface EventProviderInterface
{
    /**
     * Return ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Return Label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Return Description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Return Source
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Return Publisher
     *
     * @return string
     */
    public function getPublisher(): string;
}
