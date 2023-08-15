<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Service;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma\PermissionChecker;
use Magento\Rma\Model\Service\RmaManagement;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Model\Rma\Status\History;
use PHPUnit\Framework\TestCase;

class RmaManagementTest extends TestCase
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
     * Rma repository
     *
     * @var RmaRepositoryInterface|MockObject
     */
    protected $rmaRepositoryMock;

    /**
     * @var RmaManagement
     */
    protected $rmaManagement;

    /**
     * @var History|MockObject
     */
    protected $statusHistory;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->permissionCheckerMock = $this->createMock(PermissionChecker::class);
        $this->rmaRepositoryMock = $this->getMockForAbstractClass(
            RmaRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->statusHistory = $this->createMock(History::class);
        $this->rmaManagement = $this->objectManager->getObject(
            RmaManagement::class,
            [
                'permissionChecker' => $this->permissionCheckerMock,
                'rmaRepository' => $this->rmaRepositoryMock,
                'statusHistory' => $this->statusHistory
            ]
        );
    }

    /**
     * Run test saveRma method
     *
     * @return void
     */
    public function testSaveRma()
    {
        $rmaMock = $this->getMockBuilder(RmaInterface::class)
            ->addMethods(['isStatusNeedsAuthEmail'])
            ->getMockForAbstractClass();
        $rmaItem = $this->getMockBuilder(ItemInterface::class)
            ->addMethods(['hasDataChanges'])
            ->getMockForAbstractClass();
        $rmaItem->method('getStatus')
            ->willReturn('authorized');
        $rmaItem->method('hasDataChanges')
            ->willReturn(true);
        $rmaMock->method('getItems')
            ->willReturn([$rmaItem]);
        $rmaMock->method('isStatusNeedsAuthEmail')
            ->willReturn(true);
        $this->permissionCheckerMock->expects($this->once())
            ->method('checkRmaForCustomerContext');
        $this->rmaRepositoryMock->expects($this->once())
            ->method('save')
            ->with($rmaMock)
            ->willReturn($rmaMock);
        $this->statusHistory->expects($this->once())
            ->method('setRmaEntityId');
        $this->statusHistory->expects($this->once())
            ->method('sendAuthorizeEmail');
        $this->rmaRepositoryMock->method('save')
            ->willReturn($rmaMock);
        $this->rmaManagement->saveRma($rmaMock);
    }

    /**
     * Run test search method
     *
     * @return void
     */
    public function testSearch()
    {
        $expectedResult = 'test-result';

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaResultMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionCheckerMock->expects($this->once())
            ->method('checkRmaForCustomerContext');
        $this->rmaRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaResultMock)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->rmaManagement->search($searchCriteriaMock));
    }
}
