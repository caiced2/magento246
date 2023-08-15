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
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\RmaGraphQl\Model\Formatter\Rma as RmaFormatter;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\RmaGraphQl\Model\ResolverAccess;
use Magento\RmaGraphQl\Model\Rma\Comment;
use Magento\RmaGraphQl\Model\Validator;

/**
 * Resolver to add a comment to an existing return.
 */
class AddReturnComment implements ResolverInterface
{
    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

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
     * @var Comment
     */
    private $comment;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param RmaRepositoryInterface $rmaRepository
     * @param RmaFormatter $rmaFormatter
     * @param GetCustomer $getCustomer
     * @param Uid $idEncoder
     * @param ResolverAccess $resolverAccess
     * @param Comment $comment
     * @param Validator $validator
     */
    public function __construct(
        RmaRepositoryInterface $rmaRepository,
        RmaFormatter $rmaFormatter,
        GetCustomer $getCustomer,
        Uid $idEncoder,
        ResolverAccess $resolverAccess,
        Comment $comment,
        Validator $validator
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->rmaFormatter = $rmaFormatter;
        $this->getCustomer = $getCustomer;
        $this->idEncoder = $idEncoder;
        $this->resolverAccess = $resolverAccess;
        $this->comment = $comment;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($context);
        $customer = $this->getCustomer->execute($context);
        $returnId = (int)$this->idEncoder->decode($args['input']['return_uid']);

        $rma = $this->validator->validateRma($returnId, (int)$customer->getId());

        if (isset($args['input']['comment_text'])) {
            $this->comment->addComment($rma, $args['input']['comment_text']);
        }

        $rma = $this->rmaRepository->get($returnId);

        return [
            'return' => $this->rmaFormatter->format($rma),
            'model' => $rma
        ];
    }
}
