<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PersistentHistory\Helper\Data;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $modulesReaderMock;

    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject
     */
    protected $storeMock;

    /**
     * @var Data
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $className = Data::class;
        $arguments = $objectManager->getConstructArguments($className);
        /** @var Context $context */
        $context = $arguments['context'];
        $this->storeMock = $this->createMock(Store::class);
        $this->modulesReaderMock = $arguments['modulesReader'];
        $this->scopeConfigMock = $context->getScopeConfig();
        $this->subject = $objectManager->getObject($className, $arguments);
    }

    public function testGetPersistentConfigFilePath()
    {
        $this->modulesReaderMock->expects($this->once())
            ->method('getModuleDir')
            ->with('etc', 'Magento_PersistentHistory');
        $this->assertEquals('/persistent.xml', $this->subject->getPersistentConfigFilePath());
    }

    public function testIsWishlistPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/wishlist',
                ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->willReturn(true);
        $this->assertTrue($this->subject->isWishlistPersist($this->storeMock));
    }

    public function testIsOrderedItemsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/recently_ordered',
                ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->willReturn(true);
        $this->assertTrue($this->subject->isOrderedItemsPersist($this->storeMock));
    }

    public function testIsCompareProductsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/compare_current',
                ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->willReturn(true);
        $this->assertTrue($this->subject->isCompareProductsPersist($this->storeMock));
    }

    public function testIsComparedProductsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/compare_history',
                ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->willReturn(true);
        $this->assertTrue($this->subject->isComparedProductsPersist($this->storeMock));
    }

    public function testIsViewedProductsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/recently_viewed',
                ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->willReturn(true);
        $this->assertTrue($this->subject->isViewedProductsPersist($this->storeMock));
    }

    public function testIsCustomerAndSegmentsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/customer',
                ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->willReturn(true);
        $this->assertTrue($this->subject->isCustomerAndSegmentsPersist($this->storeMock));
    }
}
