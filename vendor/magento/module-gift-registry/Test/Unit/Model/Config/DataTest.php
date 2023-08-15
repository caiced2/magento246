<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\Config;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftRegistry\Model\Config\Data;
use Magento\GiftRegistry\Model\Config\Reader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_readerMock;

    /**
     * @var MockObject
     */
    protected $_configScopeMock;

    /**
     * @var MockObject
     */
    protected $_cacheMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /** @var ObjectManager */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->_readerMock = $this->createMock(Reader::class);
        $this->_configScopeMock = $this->getMockForAbstractClass(ScopeInterface::class);
        $this->_cacheMock = $this->getMockBuilder(
            Config::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->_model = $this->objectManager->getObject(
            Data::class,
            [
                'reader' => $this->_readerMock,
                'configScope' => $this->_configScopeMock,
                'cache' => $this->_cacheMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    public function testGet()
    {
        $this->_configScopeMock->expects($this->once())->method('getCurrentScope')->willReturn('global');
        $this->_cacheMock->expects($this->any())->method('load')->willReturn(false);
        $this->_readerMock->expects($this->any())->method('read')->willReturn([]);

        $this->assertEquals([], $this->_model->get());
    }
}
