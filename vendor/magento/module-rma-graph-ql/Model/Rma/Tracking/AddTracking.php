<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Rma\Tracking;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Rma\Api\Data\TrackInterfaceFactory;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\Rma\Helper\Data as RmaHelper;
use Magento\RmaGraphQl\Helper\Data as RmaGraphQlHelper;
use Magento\RmaGraphQl\Model\Validator;

/**
 * Add return tracking
 */
class AddTracking
{
    /**
     * @var RmaHelper
     */
    private $rmaHelper;

    /**
     * @var RmaGraphQlHelper
     */
    private $rmaGraphQlHelper;

    /**
     * @var TrackRepositoryInterface
     */
    private $trackRepository;

    /**
     * @var TrackInterfaceFactory
     */
    private $trackFactory;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param RmaHelper $rmaHelper
     * @param TrackRepositoryInterface $trackRepository
     * @param TrackInterfaceFactory $trackFactory
     * @param Uid $idEncoder
     * @param Validator $validator
     * @param RmaGraphQlHelper $rmaGraphQlHelper
     */
    public function __construct(
        RmaHelper $rmaHelper,
        TrackRepositoryInterface $trackRepository,
        TrackInterfaceFactory $trackFactory,
        Uid $idEncoder,
        Validator $validator,
        RmaGraphQlHelper $rmaGraphQlHelper
    ) {
        $this->rmaHelper = $rmaHelper;
        $this->rmaGraphQlHelper = $rmaGraphQlHelper;
        $this->trackRepository = $trackRepository;
        $this->trackFactory = $trackFactory;
        $this->idEncoder = $idEncoder;
        $this->validator = $validator;
    }

    /**
     * Add return tracking
     *
     * @param CustomerInterface $customer
     * @param array $input
     * @return array
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function execute(CustomerInterface $customer, array $input): array
    {
        $rmaId = (int)$this->idEncoder->decode($input['return_uid']);

        $trackingNumber = $this->validator->validateString(
            $input['tracking_number'],
            'Please enter a valid tracking number.'
        );

        $rma = $this->validator->validateRma($rmaId, (int)$customer->getId(), true);

        try {
            $carrier = $this->rmaGraphQlHelper->decodeCarrierId($input['carrier_uid']);
            $carriers = $this->rmaHelper->getShippingCarriers($carrier[1]);
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('Please select a valid carrier.'));
        }

        if (!isset($carriers[$carrier[0]])) {
            throw new GraphQlInputException(__('Please select a valid carrier.'));
        }

        $rmaShipping = $this->trackFactory->create();
        $rmaShipping->setRmaEntityId($rma->getEntityId())
            ->setTrackNumber($trackingNumber)
            ->setCarrierCode($carrier[0])
            ->setCarrierTitle($carriers[$carrier[0]]);

        try {
            $this->trackRepository->save($rmaShipping);
        } catch (CouldNotSaveException $e) {
            throw new LocalizedException(__('Something went wrong.'));
        }

        return [
            'rma' => $rma,
            'shipping' => $rmaShipping
        ];
    }
}
