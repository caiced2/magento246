<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Api;

/**
 * Provides access to the metadata configuration
 *
 * @api
 * @since 1.1.0
 */
interface EventMetadataRegistryInterface
{
    /**
     * Return set of configured metadata
     *
     * @return EventMetadataInterface[]
     */
    public function getDeclaredEventMetadataList(): array;

    /**
     * Return the configured provider
     *
     * @return EventProviderInterface
     */
    public function getDeclaredEventProvider(): EventProviderInterface;
}
