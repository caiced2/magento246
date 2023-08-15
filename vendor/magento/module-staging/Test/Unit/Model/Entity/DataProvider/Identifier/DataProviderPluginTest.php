<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\DataProvider\Identifier;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\DataProvider\Identifier\DataProviderPlugin;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderPluginTest extends TestCase
{
    /**
     * @var DataProviderPlugin
     */
    private $plugin;

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

    protected function setUp(): void
    {
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);

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

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);
        $this->versionManagerMock->expects($this->once())->method('setCurrentVersionId')->with($updateId);

        $expectedResult = [
            $entityId => [
                'key' => 'value',
                'update_id' => $updateId,
            ],
        ];

        $this->assertEquals($expectedResult, $this->plugin->aroundGetData($dataProviderMock, $closure));
    }

    public function testAroundGetDataReturnsOnlyEntityDataIfUpdateIsNotFound()
    {
        $entityId = 1;
        $updateId = 1;

        $closure = function () use ($entityId) {
            return [
                $entityId => [
                    'key' => 'value',
                ],
            ];
        };

        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->versionManagerMock->expects($this->never())->method('setCurrentVersionId');
        $this->updateRepositoryMock->expects($this->any())
            ->method('get')
            ->with($updateId)
            ->willThrowException(NoSuchEntityException::singleField('id', $updateId));

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
