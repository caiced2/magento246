<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document as DocumentDataProvider;
use Magento\Rma\Helper\Data;
use Magento\Rma\Observer\AddRmaOptionObserver;
use Magento\Sales\Block\Adminhtml\Reorder\Renderer\Action as Renderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddRmaOptionObserverTest extends TestCase
{
    /**
     * @var AddRmaOptionObserver
     */
    private $model;

    /**
     * @var Data|MockObject
     */
    private $rmaData;

    /**
     * @var EventObserver|MockObject
     */
    private $eventObserver;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @var DocumentDataProvider|MockObject
     */
    private $row;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->rmaData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver = $this->getMockBuilder(EventObserver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRenderer', 'getRow'])
            ->getMock();
        $this->renderer = $this->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->row = $this->getMockBuilder(DocumentDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManagerHelper->getObject(
            AddRmaOptionObserver::class,
            [
                'rmaData' => $this->rmaData
            ]
        );
    }

    public function testExecute()
    {
        $id = "1";
        $url = "http://magento.dev/admin/admin/rma/new/order_id/1/";
        $reorderAction = [
            '@' => ['href' => $url],
            '#' => new Phrase('Return'),
        ];

        $this->eventObserver->expects($this->atLeastOnce())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->event->expects($this->once())
            ->method('getRenderer')
            ->willReturn($this->renderer);
        $this->event->expects($this->once())
            ->method('getRow')
            ->willReturn($this->row);
        $this->rmaData->expects($this->once())
            ->method('canCreateRma')
            ->willReturn(true);
        $this->row->expects($this->once())
            ->method('getId')
            ->willReturn($id);

        $this->renderer->expects($this->once())
            ->method('getUrl')
            ->with('adminhtml/rma/new', ['order_id' => $id])
            ->willReturn($url);

        $this->renderer->expects($this->once())
            ->method('addToActions')
            ->with($reorderAction)
            ->willReturnSelf();

        $this->model->execute($this->eventObserver);
    }
}
