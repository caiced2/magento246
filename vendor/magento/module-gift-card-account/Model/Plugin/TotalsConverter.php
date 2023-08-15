<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\Quote\Api\Data\TotalSegmentExtensionFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Cart\TotalsConverter as CartTotalsConverter;
use Magento\Quote\Api\Data\TotalSegmentInterface;
use Magento\Quote\Model\Quote\Address\Total as AddressTotal;
use Magento\Quote\Api\Data\TotalSegmentExtensionInterface;

/**
 * Plugin for Magento\Quote\Model\Cart\TotalsConverter
 */
class TotalsConverter
{
    /**
     * @var TotalSegmentExtensionFactory
     */
    private $totalSegmentExtensionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $code;

    /**
     * @param TotalSegmentExtensionFactory $totalSegmentExtensionFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        TotalSegmentExtensionFactory $totalSegmentExtensionFactory,
        SerializerInterface $serializer
    ) {
        $this->totalSegmentExtensionFactory = $totalSegmentExtensionFactory;
        $this->serializer = $serializer;
        $this->code = 'giftcardaccount';
    }

    /**
     * Add gift card segment to the summary
     *
     * @param CartTotalsConverter $subject
     * @param TotalSegmentInterface[] $result
     * @param AddressTotal[] $addressTotals
     * @return TotalSegmentInterface[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(CartTotalsConverter $subject, $result, $addressTotals)
    {
        if (!isset($addressTotals[$this->code])) {
            return $result;
        }

        /** @var TotalSegmentExtensionInterface $totalSegmentExtension */
        $totalSegmentExtension = $this->totalSegmentExtensionFactory->create();
        $totalSegmentExtension->setGiftCards($this->serializer->serialize($addressTotals[$this->code]->getGiftCards()));
        $result[$this->code]->setExtensionAttributes($totalSegmentExtension);

        return $result;
    }
}
