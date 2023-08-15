<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\DataProvider\DataProviderPlugin;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var DataProviderPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->plugin = new DataProviderPlugin(
            $this->requestMock,
            $this->updateRepositoryMock,
            $this->versionManagerMock
        );
    }

    public function testAroundGetData()
    {
        $updateId = 1;
        $entityId = 1;

        $closure = function () use ($entityId) {
            return [
                $entityId => [
                    'key' => 'value',
                ],
            ];
        };

        $dataProviderMock = $this->createMock(
            DataProviderInterface::class
        );

        $updateMock = $this->createMock(Update::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($updateId);
        $updateMock->expects($this->any())->method('getName')->willReturn('Update Name');
        $updateMock->expects($this->any())->method('getDescription')->willReturn('Update Description');
        $updateMock->expects($this->any())->method('getStartTime')->willReturn(1000);
        $updateMock->expects($this->any())->method('getEndTime')->willReturn(2000);

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);
        $this->versionManagerMock->expects($this->once())->method('setCurrentVersionId')->with($updateId);

        $expectedResult = [
            $entityId => [
                'key' => 'value',
                'staging' => [
                    'mode' => 'save',
                    'update_id' => $updateId,
                    'name' => 'Update Name',
                    'description' => 'Update Description',
                    'start_time' => 1000,
                    'end_time' => 2000,
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $this->plugin->aroundGetData($dataProviderMock, $closure));
    }

    public function testAroundGetDataReturnsOnlyEntityDataIfUpdateIsNotFound()
    {
        $updateId = 1;
        $entityId = 1;

        $closure = function () use ($entityId) {
            return [
                $entityId => [
                    'key' => 'value',
                ],
            ];
        };

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->updateRepositoryMock->expects($this->any())
            ->method('get')
            ->with($updateId)
            ->willThrowException(NoSuchEntityException::singleField('id', $updateId));
        $this->versionManagerMock->expects($this->never())->method('setCurrentVersionId');

        $dataProviderMock = $this->createMock(
            DataProviderInterface::class
        );

        $expectedResult = [
            $entityId => [
                'key' => 'value',
            ],
        ];

        $this->assertEquals($expectedResult, $this->plugin->aroundGetData($dataProviderMock, $closure));
    }
}
