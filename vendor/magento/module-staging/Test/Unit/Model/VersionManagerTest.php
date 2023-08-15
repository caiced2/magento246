<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VersionManagerTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $updateFactoryMock;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var VersionHistoryInterface|MockObject
     */
    private $versionHistoryMock;

    /**
     * @var UpdateInterface|MockObject
     */
    private $updateMock;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfig;

    /**
     * @var VersionManager
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->updateFactoryMock = $this->getMockBuilder(UpdateFactory::class)
            ->setMethods(
                ['create']
            )->disableOriginalConstructor()
            ->getMock();
        $this->updateRepositoryMock = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->deploymentConfig = $this->getMockBuilder(DeploymentConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->versionHistoryMock = $this->getMockBuilder(VersionHistoryInterface::class)
            ->getMockForAbstractClass();
        $this->updateMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            VersionManager::class,
            [
                'updateFactory'    => $this->updateFactoryMock,
                'updateRepository' => $this->updateRepositoryMock,
                'request'          => $this->requestMock,
                'versionHistory'   => $this->versionHistoryMock,
            ]
        );
        $this->setDeploymentConfigInjection();
        $this->deploymentConfig->expects($this->any())
            ->method('isDbAvailable')
            ->willReturn(true);
    }

    private function setDeploymentConfigInjection()
    {
        $modelReflection = new \ReflectionClass($this->model);
        $deploymentProp = $modelReflection->getProperty('deploymentConfig');
        $deploymentProp->setAccessible(true);
        $deploymentProp->setValue($this->model, $this->deploymentConfig);
    }

    public function testGetCurrentVersion()
    {
        $currentVersionId = 42;
        $this->versionHistoryMock->expects($this->once())->method('getCurrentId')->willReturn($currentVersionId);
        $this->updateRepositoryMock->expects($this->once())->method('get')
            ->with($currentVersionId)
            ->willReturn($this->updateMock);

        $this->assertSame($this->updateMock, $this->model->getCurrentVersion());
    }

    public function testGetFirstVersionVersion()
    {
        $this->updateRepositoryMock->expects($this->once())->method('get')
            ->with(null)
            ->willThrowException(new NoSuchEntityException());
        $this->updateFactoryMock->expects($this->once())->method('create')->willReturn($this->updateMock);
        $this->updateMock->expects($this->once())->method('setId')->with(1)->willReturnSelf();
        $this->assertSame($this->updateMock, $this->model->getCurrentVersion());
    }

    public function testGetVersionByTimeStamp()
    {
        $requestedVersion = 872143214;
        $version = 42;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(VersionManager::PARAM_NAME)
            ->willReturn($requestedVersion);
        $this->updateRepositoryMock->expects($this->once())->method('getVersionMaxIdByTime')
            ->with($requestedVersion)
            ->willReturn($version);
        $this->updateRepositoryMock->expects($this->once())->method('get')
            ->with($version)
            ->willReturn($this->updateMock);
        $this->assertSame($this->updateMock, $this->model->getVersion());
    }

    public function testGetVersionBySetCurrentVersion()
    {
        $version = 42;
        $this->updateRepositoryMock->expects($this->once())->method('get')
            ->with($version)
            ->willReturn($this->updateMock);
        $this->model->setCurrentVersionId($version);
        $this->assertSame($this->updateMock, $this->model->getVersion());
    }

    /**
     * @dataProvider isPreviewDataProvider
     */
    public function testIsPreview($version, $currentVersion)
    {
        $this->updateRepositoryMock->expects($this->once())->method('get')
            ->with($version)
            ->willReturn($this->updateMock);
        $this->model->setCurrentVersionId($version);
        $this->updateMock->expects($this->once())->method('getId')->willReturn($version);
        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn(
                $currentVersion
            );
        $this->assertEquals($version != $currentVersion, $this->model->isPreviewVersion());
    }

    /**
     * @return array
     */
    public function isPreviewDataProvider(): array
    {
        return [
            'true'  => [42, 1],
            'false' => [42, 42],
        ];
    }

    /**
     * @param int|string|null $timestamp
     * @param int|null $expectedTimestamp
     * @return void
     * @dataProvider getRequestedTimestampDataProvider
     */
    public function testGetRequestedTimestamp($timestamp, $expectedTimestamp): void
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(VersionManager::PARAM_NAME)
            ->willReturn($timestamp);

        $this->assertEquals($expectedTimestamp, $this->model->getRequestedTimestamp());
    }

    /**
     * @return array
     */
    public function getRequestedTimestampDataProvider(): array
    {
        return [
            ['872143214', 872143214],
            [872143214, 872143214],
            [null, null],
        ];
    }
}
