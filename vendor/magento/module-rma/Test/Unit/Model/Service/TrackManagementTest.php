<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Service;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Api\Data\TrackInterface;
use Magento\Rma\Api\Data\TrackSearchResultInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\PermissionChecker;
use Magento\Rma\Model\Service\TrackManagement;
use Magento\Rma\Model\Shipping\LabelService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TrackManagementTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Permission checker
     *
     * @var PermissionChecker|MockObject
     */
    protected $permissionCheckerMock;

    /**
     * Label service
     *
     * @var LabelService|MockObject
     */
    protected $labelServiceMock;

    /**
     * RMA repository
     *
     * @var RmaRepositoryInterface|MockObject
     */
    protected $rmaRepositoryMock;

    /**
     * Filter builder
     *
     * @var FilterBuilder|MockObject
     */
    protected $filterBuilderMock;

    /**
     * Criteria builder
     *
     * @var SearchCriteriaBuilder|MockObject
     */
    protected $criteriaBuilderMock;

    /**
     * Track repository
     *
     * @var TrackRepositoryInterface|MockObject
     */
    protected $trackRepositoryMock;

    /**
     * @var TrackManagement
     */
    protected $trackManagement;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->permissionCheckerMock = $this->createMock(PermissionChecker::class);
        $this->labelServiceMock = $this->createMock(LabelService::class);
        $this->rmaRepositoryMock = $this->getMockForAbstractClass(
            RmaRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->trackRepositoryMock = $this->getMockForAbstractClass(
            TrackRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->criteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->filterBuilderMock = $this->createMock(FilterBuilder::class);

        $this->trackManagement = $this->objectManager->getObject(
            TrackManagement::class,
            [
                'permissionChecker' => $this->permissionCheckerMock,
                'labelService' => $this->labelServiceMock,
                'rmaRepository' => $this->rmaRepositoryMock,
                'trackRepository' => $this->trackRepositoryMock,
                'criteriaBuilder' => $this->criteriaBuilderMock,
                'filterBuilder' => $this->filterBuilderMock,
            ]
        );
    }

    /**
     * Run test getShippingLabelPdf method
     *
     * @return void
     */
    public function testGetShippingLabelPdf()
    {
        $expectedResult = base64_encode('test-label');
        $rmaMock = $this->createMock(Rma::class);

        $this->permissionCheckerMock->expects($this->once())
            ->method('checkRmaForCustomerContext');
        $this->rmaRepositoryMock->expects($this->once())
            ->method('get')
            ->with(10)
            ->willReturn($rmaMock);
        $this->labelServiceMock->expects($this->once())
            ->method('getShippingLabelByRmaPdf')
            ->with($rmaMock)
            ->willReturn('test-label');
        $actualResult = $this->trackManagement->getShippingLabelPdf(10);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Run test getTracks method
     *
     * @return void
     */
    public function testGetTracks()
    {
        $filter = ['eq' => 'filter'];
        $criteriaMock = $this->createMock(SearchCriteria::class);
        $this->filterBuilderMock->expects($this->once())
            ->method('setField')
            ->with('rma_entity_id')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())
            ->method('setValue')
            ->with(10)
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn('filter');

        $this->criteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->with($filter);
        $this->criteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($criteriaMock);

        $this->trackRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($criteriaMock)
            ->willReturn('track-list');

        $this->assertEquals('track-list', $this->trackManagement->getTracks(10));
    }

    /**
     * Run test addTrack method
     *
     * @return void
     */
    public function testAddTrack()
    {
        $trackMock = $this->getMockForAbstractClass(
            TrackInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $rmaMock = $this->createMock(Rma::class);

        $this->permissionCheckerMock->expects($this->once())
            ->method('isCustomerContext')
            ->willReturn(false);
        $this->rmaRepositoryMock->expects($this->once())
            ->method('get')
            ->with(10)
            ->willReturn($rmaMock);
        $rmaMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(23);
        $trackMock->expects($this->once())
            ->method('setRmaEntityId')
            ->with(23);
        $this->trackRepositoryMock->expects($this->once())
            ->method('save')
            ->with($trackMock)
            ->willReturn(true);

        $this->assertTrue($this->trackManagement->addTrack(10, $trackMock));
    }

    /**
     * Run test addTrack method [Exception]
     *
     * @return void
     */
    public function testAddTrackException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $trackMock = $this->getMockForAbstractClass(
            TrackInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->permissionCheckerMock->expects($this->once())
            ->method('isCustomerContext')
            ->willReturn(true);

        $this->trackManagement->addTrack(10, $trackMock);
    }

    /**
     * Run test removeTrackById method
     *
     * @return void
     */
    public function testRemoveTrackById()
    {
        $filter = ['eq' => 'filter'];
        $criteriaMock = $this->createMock(SearchCriteria::class);
        $trackSearchResult = $this->getMockForAbstractClass(
            TrackSearchResultInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $trackMock = $this->getMockForAbstractClass(
            TrackInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $tracksMock = [$trackMock];

        $this->permissionCheckerMock->expects($this->once())
            ->method('isCustomerContext')
            ->willReturn(false);
        $this->filterBuilderMock->expects($this->atLeastOnce())
            ->method('setField')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->atLeastOnce())
            ->method('setValue')
            ->willReturnSelf();
        $this->filterBuilderMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn('filter');
        $this->criteriaBuilderMock->expects($this->atLeastOnce())
            ->method('addFilters')
            ->with($filter);
        $this->criteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($criteriaMock);
        $this->trackRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($criteriaMock)
            ->willReturn($trackSearchResult);
        $trackSearchResult->expects($this->once())
            ->method('getItems')
            ->willReturn($tracksMock);
        $this->trackRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($trackMock)
            ->willReturn(['deleted']);

        $this->assertTrue($this->trackManagement->removeTrackById(10, 20));
    }

    /**
     * Run test removeTrackById method
     *
     * @return void
     */
    public function testRemoveTrackByIdException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->permissionCheckerMock->expects($this->once())
            ->method('isCustomerContext')
            ->willReturn(true);

        $this->assertTrue($this->trackManagement->removeTrackById(10, 20));
    }
}
