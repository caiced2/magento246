<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RmaGraphQl\Model\Formatter\Rma;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\RmaGraphQl\Model\ResolverAccess;
use Magento\RmaGraphQl\Model\Rma\Comment;
use Magento\RmaGraphQl\Model\Rma\RequestRma;

/**
 *  Request return mutation resolver
 */
class RequestReturn implements ResolverInterface
{
    /**
     * @var Comment
     */
    private $comment;

    /**
     * @var Rma
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
     * @var RequestRma
     */
    private $requestRma;

    /**
     * @param Comment $comment
     * @param Rma $rmaFormatter
     * @param ResolverAccess $resolverAccess
     * @param GetCustomer $getCustomer
     * @param Uid $idEncoder
     * @param RequestRma $requestRma
     */
    public function __construct(
        Comment $comment,
        Rma $rmaFormatter,
        ResolverAccess $resolverAccess,
        GetCustomer $getCustomer,
        Uid $idEncoder,
        RequestRma $requestRma
    ) {
        $this->comment = $comment;
        $this->rmaFormatter = $rmaFormatter;
        $this->resolverAccess = $resolverAccess;
        $this->getCustomer = $getCustomer;
        $this->idEncoder = $idEncoder;
        $this->requestRma = $requestRma;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($context);

        if (empty($args['input']['items'])) {
            throw new LocalizedException(__('We can\'t create a return right now. Please try again later.'));
        }

        $orderId = (int)$this->idEncoder->decode($args['input']['order_uid']);
        $customer = $this->getCustomer->execute($context);

        $rma = $this->requestRma->execute($orderId, (int)$customer->getId(), $args['input']);

        if (isset($args['input']['comment_text'])) {
            $this->comment->addComment($rma, $args['input']['comment_text'], true, false);
        }

        return [
            'return' => $this->rmaFormatter->format($rma),
            'model' => $rma,
        ];
    }
}
