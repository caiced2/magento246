<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\RmaGraphQl\Model\Formatter\Rma as RmaFormatter;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\RmaGraphQl\Model\ResolverAccess;
use Magento\RmaGraphQl\Model\Validator;

/**
 * Remove return tracking mutation resolver
 */
class RemoveReturnTracking implements ResolverInterface
{
    /**
     * @var RmaFormatter
     */
    private $rmaFormatter;

    /**
     * @var TrackRepositoryInterface
     */
    private $trackRepository;

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
     * @param RmaFormatter $rmaFormatter
     * @param TrackRepositoryInterface $trackRepository
     * @param GetCustomer $getCustomer
     * @param Uid $idEncoder
     * @param ResolverAccess $resolverAccess
     * @param Validator $validator
     */
    public function __construct(
        RmaFormatter $rmaFormatter,
        TrackRepositoryInterface $trackRepository,
        GetCustomer $getCustomer,
        Uid $idEncoder,
        ResolverAccess $resolverAccess,
        Validator $validator
    ) {
        $this->rmaFormatter = $rmaFormatter;
        $this->trackRepository = $trackRepository;
        $this->getCustomer = $getCustomer;
        $this->resolverAccess = $resolverAccess;
        $this->idEncoder = $idEncoder;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($context);

        if (empty($args['input']['return_shipping_tracking_uid'])) {
            throw new GraphQlInputException(__('Please enter return_shipping_tracking_uid.'));
        }

        $trackId = $this->idEncoder->decode($args['input']['return_shipping_tracking_uid']);
        $tracking = $this->trackRepository->get($trackId);

        $customer = $this->getCustomer->execute($context);

        $rma = $this->validator->validateRma(
            (int)$tracking->getRmaEntityId(),
            (int)$customer->getId(),
            true
        );

        try {
            $this->trackRepository->delete($tracking);
        } catch (CouldNotDeleteException $e) {
            throw new LocalizedException(__('We can\'t delete the label right now.'));
        }

        return ['return' => $this->rmaFormatter->format($rma)];
    }
}
