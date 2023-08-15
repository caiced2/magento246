<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\BannerCustomerSegment\Observer\SaveCustomerSegmentRelations;
use Magento\CustomerSegment\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveCustomerSegmentRelationsTest extends TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\SaveCustomerSegmentRelations
     */
    private $saveCustomerSegmentRelationsObserver;

    /**
     * @var MockObject
     */
    private $_bannerSegmentLink;

    /**
     * @var MockObject
     */
    private $_segmentHelper;

    protected function setUp(): void
    {
        $this->_bannerSegmentLink = $this->createPartialMock(
            BannerSegmentLink::class,
            ['loadBannerSegments', 'saveBannerSegments', 'addBannerSegmentFilter']
        );

        $this->_segmentHelper = $this->createPartialMock(
            Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->saveCustomerSegmentRelationsObserver = new SaveCustomerSegmentRelations(
            $this->_segmentHelper,
            $this->_bannerSegmentLink
        );
    }

    protected function tearDown(): void
    {
        $this->_bannerSegmentLink = null;
        $this->_segmentHelper = null;
        $this->saveCustomerSegmentRelationsObserver = null;
    }

    public function testSaveCustomerSegmentRelations()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(true);

        $segmentIds = [123, 456];
        $banner = new DataObject(['id' => 42, 'customer_segment_ids' => $segmentIds]);

        $this->_bannerSegmentLink->expects(
            $this->once()
        )->method(
            'saveBannerSegments'
        )->with(
            $banner->getId(),
            $segmentIds
        );

        $this->saveCustomerSegmentRelationsObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(['banner' => $banner]),
                ]
            )
        );
    }

    public function testSaveCustomerSegmentRelationsException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Customer segments associated with a dynamic block are expected to be defined as an array'
        );
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(true);

        $banner = new DataObject(['id' => 42, 'customer_segment_ids' => 'invalid']);

        $this->_bannerSegmentLink->expects($this->never())->method('saveBannerSegments');

        $this->saveCustomerSegmentRelationsObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(['banner' => $banner]),
                ]
            )
        );
    }

    public function testSaveCustomerSegmentRelationsDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(false);

        $banner = new DataObject(['id' => 42, 'customer_segment_ids' => [123, 456]]);

        $this->_bannerSegmentLink->expects($this->never())->method('saveBannerSegments');

        $this->saveCustomerSegmentRelationsObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(['banner' => $banner]),
                ]
            )
        );
    }
}
