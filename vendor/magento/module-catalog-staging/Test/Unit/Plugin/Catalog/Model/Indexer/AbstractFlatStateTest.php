<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Plugin\Catalog\Model\Indexer;

use Magento\CatalogStaging\Plugin\Catalog\Model\Indexer\AbstractFlatState;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Abstract Flat State plugin.
 */
class AbstractFlatStateTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var AbstractFlatState
     */
    private $subject;

    /**
     * @var \Magento\Catalog\Model\Indexer\AbstractFlatState|MockObject
     */
    private $abstractFlatStateMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    protected function setUp(): void
    {
        $this->abstractFlatStateMock = $this->createMock(\Magento\Catalog\Model\Indexer\AbstractFlatState::class);

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->subject = new AbstractFlatState(
            $this->versionManagerMock
        );
    }

    /**
     * @param bool $isPreview
     * @param bool $isAvailable
     * @param bool $expectedResult
     *
     * @dataProvider aroundIsAvailableDataProvider
     */
    public function testAroundIsAvailable($isPreview, $isAvailable, $expectedResult)
    {
        $closureMock = function () use ($isAvailable) {
            return $isAvailable;
        };

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        $result = $this->subject->aroundIsAvailable($this->abstractFlatStateMock, $closureMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function aroundIsAvailableDataProvider()
    {
        return [
            [true, true, false],
            [false, true, true]
        ];
    }
}
