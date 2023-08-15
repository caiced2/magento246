<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog;

use Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Stub\EventStub as Event;
use Magento\Framework\Phrase;

class EventTest extends AbstractEventTest
{
    /**
     * @var \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event
     */
    protected $event;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->event = new Event(
            $this->contextMock,
            $this->registryMock,
            $this->eventFactoryMock,
            $this->dateTimeMock,
            $this->storeManagerMock
        );
    }

    /**
     * @dataProvider dispatchDataProvider
     * @param bool $isHelperEnabled
     * @param \PHPUnit\Framework\MockObject_Matcher_Invocation $expectDispatched
     * @return void
     */
    public function testDispatch($isHelperEnabled, $expectDispatched)
    {
        $this->requestMock
            ->expects($this->any())
            ->method('isDispatched')
            ->willReturn(true);

        $this->helperMock
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isHelperEnabled);

        $this->requestMock
            ->expects($expectDispatched)
            ->method('setDispatched')
            ->with(false);

        $this->responseMock
            ->expects($this->once())
            ->method('setRedirect');

        $this->responseMock
            ->expects($this->once())
            ->method('setStatusHeader')
            ->with(403);

        $this->event->dispatch($this->requestMock);
    }

    /**
     * @return void
     */
    public function testInitAction()
    {
        $this->breadcrumbsBlockMock
            ->expects($this->atLeastOnce())
            ->method('addLink')
            ->withConsecutive(
                [new Phrase('Catalog'), new Phrase('Catalog'), null],
                [new Phrase('Events'), new Phrase('Events'), null]
            );

        $this->event->_initAction();
    }

    /**
     * @return array
     */
    public function dispatchDataProvider()
    {
        return [
            [
                true, $this->never()
            ],
            [
                false, $this->once()
            ]
        ];
    }
}
