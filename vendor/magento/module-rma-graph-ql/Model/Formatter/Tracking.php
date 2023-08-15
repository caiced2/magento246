<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Rma\Api\Data\TrackInterface;
use Magento\Rma\Model\Shipping;
use Magento\RmaGraphQl\Helper\Data as RmaGraphQlHelper;
use Magento\Shipping\Model\Tracking\Result\Error;
use Magento\Shipping\Model\Tracking\Result\Status;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Tracking formatter
 */
class Tracking
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @var RmaGraphQlHelper
     */
    private $rmaGraphQlHelper;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var string
     */
    private $statusTypeEnum = 'ReturnShippingTrackingStatusType';

    /**
     * @param StoreManagerInterface $storeManager
     * @param EnumLookup $enumLookup
     * @param RmaGraphQlHelper $rmaGraphQlHelper
     * @param Uid $idEncoder
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        EnumLookup $enumLookup,
        RmaGraphQlHelper $rmaGraphQlHelper,
        Uid $idEncoder
    ) {
        $this->storeManager = $storeManager;
        $this->enumLookup = $enumLookup;
        $this->rmaGraphQlHelper = $rmaGraphQlHelper;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Format tracking according to the GraphQl schema
     *
     * @param TrackInterface $track
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws RuntimeException
     */
    public function format(TrackInterface $track): array
    {
        $result = $track->getNumberDetail();
        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        if (is_object($result)) {
            $carrier = [
                'uid' => $this->rmaGraphQlHelper->encodeCarrierId($result->getCarrier(), $storeId),
                'label' => $result->getCarrierTitle()
            ];
            if ($result->getErrorMessage()) {
                $status = [
                    'text' => $result->getErrorMessage()->getText(),
                    'type' => $this->enumLookup->getEnumValueFromField(
                        $this->statusTypeEnum,
                        (string)Error::STATUS_TYPE
                    )
                ];
            } else {
                $status = [
                    'text' => $result->getTrackSummary(),
                    'type' => $this->enumLookup->getEnumValueFromField(
                        $this->statusTypeEnum,
                        (string)Status::STATUS_TYPE
                    )
                ];
            }
        } else {
            $status = null;
            $carrier = [
                'uid' => $this->rmaGraphQlHelper->encodeCarrierId(Shipping::CUSTOM_CARRIER_CODE, $storeId),
                'label' => $result['title']
            ];
        }

        return [
            'uid' => $this->idEncoder->encode((string)$track->getEntityId()),
            'carrier' => $carrier,
            'tracking_number' => $track->getTrackNumber(),
            'status' => $status
        ];
    }
}
