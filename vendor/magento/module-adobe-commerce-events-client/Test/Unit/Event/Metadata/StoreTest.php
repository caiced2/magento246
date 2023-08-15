<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Metadata;

use Magento\AdobeCommerceEventsClient\Event\Metadata\Store;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see Store class
 */
class StoreTest extends TestCase
{
    /**
     * @var Store
     */
    private Store $storeMetadata;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private StoreManagerInterface|MockObject $storeManagerMock;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->storeMetadata = new Store($this->storeManagerMock);
    }

    public function testGet()
    {
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $storeMock->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $storeMock->expects(self::once())
            ->method('getWebsiteId')
            ->willReturn(1);
        $storeMock->expects(self::once())
            ->method('getStoreGroupId')
            ->willReturn(1);
        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willReturn($storeMock);

        $metadata = $this->storeMetadata->get();

        self::assertEquals(3, count($metadata));
        self::assertEquals(1, $metadata['storeId']);
        self::assertEquals(1, $metadata['websiteId']);
        self::assertEquals(1, $metadata['storeGroupId']);
    }

    public function testGetWithException()
    {
        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willThrowException(new NoSuchEntityException(new Phrase('some error')));

        $metadata = $this->storeMetadata->get();

        self::assertEquals(3, count($metadata));
        self::assertEquals('', $metadata['storeId']);
        self::assertEquals('', $metadata['websiteId']);
        self::assertEquals('', $metadata['storeGroupId']);
    }
}
