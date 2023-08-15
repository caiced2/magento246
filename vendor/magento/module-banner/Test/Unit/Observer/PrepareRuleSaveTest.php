<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Observer;

use Magento\Backend\Helper\Js;
use Magento\Banner\Observer\PrepareRuleSave;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for testing prepare rule save
 */
class PrepareRuleSaveTest extends TestCase
{
    /**
     * @var PrepareRuleSave
     */
    protected $prepareRuleSaveObserver;

    /**
     * @var Observer
     */
    protected $eventObserver;

    /**
     * @var Js|MockObject
     */
    protected $adminhtmlJs;

    /**
     * @var Event|MockObject
     */
    protected $event;

    /**
     * @var Http|MockObject
     */
    protected $http;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->adminhtmlJs = $this->getMockBuilder(Js::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->prepareRuleSaveObserver = $objectManager->getObject(
            PrepareRuleSave::class,
            ['adminhtmlJs' => $this->adminhtmlJs]
        );
    }

    /**
     * Test prepare rule save with banners
     *
     * @return void
     */
    public function testPrepareRuleSave(): void
    {
        $this->http = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->http->method('getPost')
            ->with('related_banners')
            ->willReturn('test');
        $this->adminhtmlJs->expects($this->once())
            ->method('decodeGridSerializedInput')
            ->with('test')
            ->willReturn('test');
        $this->http->expects($this->any())
            ->method('setPost')
            ->with('related_banners', 'test');
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'setPost', 'getPost'])
            ->getMock();
        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->http);
        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->assertInstanceOf(
            PrepareRuleSave::class,
            $this->prepareRuleSaveObserver->execute($this->eventObserver)
        );
    }

    /**
     * Test prepare rule save without banners
     *
     * @return void
     */
    public function testPrepareRuleSaveWithoutSelectedBanners(): void
    {
        $this->http = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->http->method('getPost')
            ->with('related_banners')
            ->willReturn(null);
        $this->adminhtmlJs->expects($this->never())
            ->method('decodeGridSerializedInput');
        $this->http->expects($this->never())
            ->method('setPost');
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'setPost', 'getPost'])
            ->getMock();
        $this->event->method('getRequest')
            ->willReturn($this->http);
        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver->method('getEvent')
            ->willReturn($this->event);
        $this->assertInstanceOf(PrepareRuleSave::class, $this->prepareRuleSaveObserver->execute($this->eventObserver));
    }
}
