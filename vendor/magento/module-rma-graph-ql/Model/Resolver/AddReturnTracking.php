<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RmaGraphQl\Model\Formatter\Rma as RmaFormatter;
use Magento\RmaGraphQl\Model\Formatter\Tracking as TrackingFormatter;
use Magento\RmaGraphQl\Model\Rma\Tracking\AddTracking;
use Magento\RmaGraphQl\Model\ResolverAccess;

/**
 * Add return tracking mutation resolver
 */
class AddReturnTracking implements ResolverInterface
{
    /**
     * @var RmaFormatter
     */
    private $rmaFormatter;

    /**
     * @var TrackingFormatter
     */
    private $trackingFormatter;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var AddTracking
     */
    private $addTracking;

    /**
     * AddReturnTracking constructor.
     * @param RmaFormatter $rmaFormatter
     * @param TrackingFormatter $trackingFormatter
     * @param GetCustomer $getCustomer
     * @param ResolverAccess $resolverAccess
     * @param AddTracking $addTracking
     */
    public function __construct(
        RmaFormatter $rmaFormatter,
        TrackingFormatter $trackingFormatter,
        GetCustomer $getCustomer,
        ResolverAccess $resolverAccess,
        AddTracking $addTracking
    ) {

        $this->rmaFormatter = $rmaFormatter;
        $this->trackingFormatter = $trackingFormatter;
        $this->getCustomer = $getCustomer;
        $this->resolverAccess = $resolverAccess;
        $this->addTracking = $addTracking;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($context);
        $customer = $this->getCustomer->execute($context);

        $rmaData = $this->addTracking->execute($customer, $args['input']);

        return [
            'return' => $this->rmaFormatter->format($rmaData['rma']),
            'return_shipping_tracking' => $this->trackingFormatter->format($rmaData['shipping'])
        ];
    }
}
