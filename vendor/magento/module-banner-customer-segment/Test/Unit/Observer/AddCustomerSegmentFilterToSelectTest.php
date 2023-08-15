<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\BannerCustomerSegment\Observer\AddCustomerSegmentFilterToSelect;
use Magento\CustomerSegment\Helper\Data;
use Magento\CustomerSegment\Model\Customer;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddCustomerSegmentFilterToSelectTest extends TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\AddCustomerSegmentFilterToSelect
     */
    private $addCustomerSegmentFilterToSelectObserver;

    /**
     * @var MockObject
     */
    private $_bannerSegmentLink;

    /**
     * @var MockObject
     */
    private $_segmentCustomer;

    /**
     * @var MockObject
     */
    private $_segmentHelper;

    /**
     * @var Select
     */
    private $_select;

    protected function setUp(): void
    {
        $this->_bannerSegmentLink = $this->createPartialMock(
            BannerSegmentLink::class,
            ['loadBannerSegments', 'saveBannerSegments', 'addBannerSegmentFilter']
        );
        $this->_segmentCustomer = $this->createPartialMock(
            Customer::class,
            ['getCurrentCustomerSegmentIds']
        );
        $this->_segmentHelper = $this->createPartialMock(
            Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->addCustomerSegmentFilterToSelectObserver = new AddCustomerSegmentFilterToSelect(
            $this->_segmentHelper,
            $this->_bannerSegmentLink,
            $this->_segmentCustomer
        );

        $this->_select = new Select(
            $this->getMockForAbstractClass(Mysql::class, [], '', false),
            $this->getMockForAbstractClass(SelectRenderer::class, [], '', false)
        );
    }

    protected function tearDown(): void
    {
        $this->_segmentHelper = null;
        $this->_bannerSegmentLink = null;
        $this->_segmentCustomer = null;
        $this->addCustomerSegmentFilterToSelectObserver = null;
    }

    public function addCustomerSegmentFilterDataProvider()
    {
        return ['segments' => [[123, 456]], 'no segments' => [[]]];
    }

    protected function _setFixtureSegmentIds(array $segmentIds)
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(true);

        $this->_segmentCustomer->expects(
            $this->once()
        )->method(
            'getCurrentCustomerSegmentIds'
        )->willReturn(
            $segmentIds
        );
    }

    /**
     * @dataProvider addCustomerSegmentFilterDataProvider
     * @param array $segmentIds
     */
    public function testAddCustomerSegmentFilterToSelect(array $segmentIds)
    {
        $this->_setFixtureSegmentIds($segmentIds);

        $this->_bannerSegmentLink->expects(
            $this->once()
        )->method(
            'addBannerSegmentFilter'
        )->with(
            $this->_select,
            $segmentIds
        );

        $this->addCustomerSegmentFilterToSelectObserver->execute(
            new Observer(
                ['event' => new DataObject(['select' => $this->_select])]
            )
        );
    }

    public function testAddCustomerSegmentFilterToSelectDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(false);

        $this->_segmentCustomer->expects($this->never())->method('getCurrentCustomerSegmentIds');
        $this->_bannerSegmentLink->expects($this->never())->method('addBannerSegmentFilter');

        $this->addCustomerSegmentFilterToSelectObserver->execute(
            new Observer(
                ['event' => new DataObject(['select' => $this->_select])]
            )
        );
    }
}
