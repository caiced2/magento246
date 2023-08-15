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
use Magento\Framework\GraphQl\Query\Uid;
use Magento\RmaGraphQl\Model\ResolverAccess;
use Magento\RmaGraphQl\Model\Validator;

/**
 * RMA resolver
 */
class Rma implements ResolverInterface
{
    /**
     * @var RmaFormatter
     */
    private $rmaFormatter;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param Validator $validator
     * @param RmaFormatter $rmaFormatter
     * @param GetCustomer $getCustomer
     * @param Uid $idEncoder
     * @param ResolverAccess $resolverAccess
     */
    public function __construct(
        Validator $validator,
        RmaFormatter $rmaFormatter,
        GetCustomer $getCustomer,
        Uid $idEncoder,
        ResolverAccess $resolverAccess
    ) {
        $this->rmaFormatter = $rmaFormatter;
        $this->validator = $validator;
        $this->getCustomer = $getCustomer;
        $this->idEncoder = $idEncoder;
        $this->resolverAccess = $resolverAccess;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($context);
        $customer = $this->getCustomer->execute($context);
        $returnId = (int)$this->idEncoder->decode($args['uid']);

        $rma = $this->validator->validateRma($returnId, (int)$customer->getId());

        return $this->rmaFormatter->format($rma);
    }
}
