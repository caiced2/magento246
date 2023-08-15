<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\Framework\Api\Data\ImageContentInterface;

/**
 * Filters image columns from event payload.
 */
class ImageFieldFilter implements DataFilterInterface
{
    /**
     * Recurses through the input array and unsets any keys equivalent to ImageContentInterface::BASE64_ENCODED_DATA.
     *
     * @param string $eventCode
     * @param array $eventData
     * @return array
     */
    public function filter(string $eventCode, array $eventData): array
    {
        foreach ($eventData as $key => $value) {
            if ($key == ImageContentInterface::BASE64_ENCODED_DATA) {
                unset($eventData[$key]);
            }
            if (is_array($value)) {
                $eventData[$key] = $this->filter($eventCode, $value);
            }
        }

        return $eventData;
    }
}
