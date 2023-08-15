<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RmaGraphQl\Model\Formatter\Tracking as TrackingFormatter;

/**
 * Tracking resolver
 */
class Tracking implements ResolverInterface
{
    /**
     * @var TrackingFormatter
     */
    private $trackingFormatter;

    /**
     * @param TrackingFormatter $trackingFormatter
     */
    public function __construct(TrackingFormatter $trackingFormatter)
    {
        $this->trackingFormatter = $trackingFormatter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $rma = $value['model'];
        $tracks = $rma->getTrackingNumbers();

        if (isset($args['uid'])) {
            $tracks = [$tracks->getItemById($args['uid'])];
        }

        $result = [];

        foreach ($tracks as $track) {
            $result[] = $this->trackingFormatter->format($track);
        }

        return $result;
    }
}
