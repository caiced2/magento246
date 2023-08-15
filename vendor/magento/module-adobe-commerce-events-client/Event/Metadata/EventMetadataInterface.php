<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Metadata;

/**
 * Interface for event metadata
 */
interface EventMetadataInterface
{
    /**
     * Returns array of key values pairs.
     * For example:
     * [
     *    'store_id' => 3,
     *    'website_id => 2
     * ]
     *
     * @return array
     * @throws EventMetadataException
     */
    public function get(): array;
}
