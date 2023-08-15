<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\BannerCustomerSegment\Observer\AddCustomerSegmentFilterToCollection;
use Magento\CustomerSegment\Helper\Data;
use Magento\CustomerSegment\Model\Customer;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddCustomerSegmentFilterToCollectionTest extends TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\AddCustomerSegmentFilterToCollection
     */
    private $addCustomerSegmentFilterToCollectionObserver;

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

    /**
     * @var SelectRenderer
     */
    protected $selectRenderer;

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

        $this->addCustomerSegmentFilterToCollectionObserver = new AddCustomerSegmentFilterToCollection(
            $this->_segmentHelper,
            $this->_bannerSegmentLink,
            $this->_segmentCustomer
        );

        $this->selectRenderer = $this->getMockBuilder(SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_select = new Select(
            $this->getMockForAbstractClass(Mysql::class, [], '', false),
            $this->selectRenderer
        );
    }

    protected function tearDown(): void
    {
        $this->_bannerSegmentLink = null;
        $this->_segmentCustomer = null;
        $this->_segmentHelper = null;
        $this->_select = null;
        $this->addCustomerSegmentFilterToCollectionObserver = null;
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
    public function testAddCustomerSegmentFilterToCollection(array $segmentIds)
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

        $this->addCustomerSegmentFilterToCollectionObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(
                        ['collection' => new DataObject(['select' => $this->_select])]
                    ),
                ]
            )
        );
    }

    /**
     * @return array
     */
    public function addCustomerSegmentFilterDataProvider()
    {
        return ['segments' => [[123, 456]], 'no segments' => [[]]];
    }

    public function testAddCustomerSegmentFilterToCollectionDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(false);

        $this->_segmentCustomer->expects($this->never())->method('getCurrentCustomerSegmentIds');
        $this->_bannerSegmentLink->expects($this->never())->method('addBannerSegmentFilter');

        $this->addCustomerSegmentFilterToCollectionObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(
                        ['collection' => new DataObject(['select' => $this->_select])]
                    ),
                ]
            )
        );
    }
}
