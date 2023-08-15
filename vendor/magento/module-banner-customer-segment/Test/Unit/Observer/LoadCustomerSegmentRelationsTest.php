<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\BannerCustomerSegment\Observer\LoadCustomerSegmentRelations;
use Magento\CustomerSegment\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoadCustomerSegmentRelationsTest extends TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\LoadCustomerSegmentRelations
     */
    private $loadCustomerSegmentRelationsObserver;

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

        $this->loadCustomerSegmentRelationsObserver = new LoadCustomerSegmentRelations(
            $this->_segmentHelper,
            $this->_bannerSegmentLink
        );
    }

    protected function tearDown(): void
    {
        $this->_bannerSegmentLink = null;
        $this->loadCustomerSegmentRelationsObserver = null;
    }

    public function testLoadCustomerSegmentRelations()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(true);

        $banner = new DataObject(['id' => 42]);
        $segmentIds = [123, 456];

        $this->_bannerSegmentLink->expects(
            $this->once()
        )->method(
            'loadBannerSegments'
        )->with(
            $banner->getId()
        )->willReturn(
            $segmentIds
        );

        $this->loadCustomerSegmentRelationsObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(['banner' => $banner]),
                ]
            )
        );
        $this->assertEquals($segmentIds, $banner->getData('customer_segment_ids'));
    }

    public function testLoadCustomerSegmentRelationsDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(false);

        $banner = new DataObject(['id' => 42]);

        $this->_bannerSegmentLink->expects($this->never())->method('loadBannerSegments');

        $this->loadCustomerSegmentRelationsObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(['banner' => $banner]),
                ]
            )
        );
    }
}
