<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Plugin\Store\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Plugin\Store\Model\StoreResolver;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for store resolver plugin.
 */
class StoreResolverTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var StoreResolver
     */
    private $subject;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var StoreResolverInterface|MockObject
     */
    private $storeResolverMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var StoreRepositoryInterface|MockObject
     */
    private $storeRepositoryMock;

    protected function setUp(): void
    {
        $this->storeMock = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['isActive']
        );

        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->objectManager = new ObjectManager($this);

        $this->storeResolverMock = $this->getMockForAbstractClass(StoreResolverInterface::class);

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->storeRepositoryMock = $this->getMockForAbstractClass(
            StoreRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->subject = $this->objectManager->getObject(
            StoreResolver::class,
            [
                'request' => $this->requestMock,
                'storeRepository' => $this->storeRepositoryMock,
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    /**
     * @param bool $isPreview
     * @param string|null $storeCode
     * @param bool $isException
     * @param bool $isStoreActive
     *
     * @dataProvider dataProviderAroundGetCurrentStoreId
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testAroundGetCurrentStoreId($isPreview, $storeCode, $isException, $isStoreActive)
    {
        $defaultStoreId = '1';
        $requestedStoreId = '2';

        $closureMock = function () use ($defaultStoreId) {
            return $defaultStoreId;
        };

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with(StoreManagerInterface::PARAM_NAME)
            ->willReturn($storeCode);

        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($requestedStoreId);
        $this->storeMock->expects($this->any())
            ->method('isActive')
            ->willReturn($isStoreActive);

        // Assertions.
        if (!$isPreview || ($isPreview && !$storeCode)) {
            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($defaultStoreId, $result);
        }

        if ($isPreview && $storeCode && $isException) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('get')
                ->with($storeCode)
                ->willThrowException(
                    new NoSuchEntityException(
                        new Phrase('Test Exception')
                    )
                );

            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($defaultStoreId, $result);
        }

        if ($isPreview && $storeCode && !$isException && !$isStoreActive) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('get')
                ->with($storeCode)
                ->willReturn($this->storeMock);

            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($defaultStoreId, $result);
        }

        if ($isPreview && $storeCode && !$isException && $isStoreActive) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('get')
                ->with($storeCode)
                ->willReturn($this->storeMock);

            $result = $this->subject->aroundGetCurrentStoreId(
                $this->storeResolverMock,
                $closureMock
            );

            $this->assertEquals($requestedStoreId, $result);
        }
    }

    /**
     * @return array
     */
    public function dataProviderAroundGetCurrentStoreId()
    {
        return [
            [false, null, false, false],
            [true, null, false, false],
            [true, 'test_store_2', true, false],
            [true, 'test_store_2', false, false],
            [true, 'test_store_2', false, true]
        ];
    }
}
