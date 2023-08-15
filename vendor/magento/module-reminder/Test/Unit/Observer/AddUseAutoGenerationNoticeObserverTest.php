<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Observer;

use Magento\Framework\Data\Form;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Observer\AddUseAutoGenerationNoticeObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddUseAutoGenerationNoticeObserverTest extends TestCase
{
    /**
     * @var AddUseAutoGenerationNoticeObserver
     */
    private $model;

    /**
     * @var Observer|MockObject
     */
    private $eventObserver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->setMethods(['getCollection', 'getRule', 'getForm', 'getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $helper->getObject(
            AddUseAutoGenerationNoticeObserver::class
        );
    }

    /**
     * @return void
     */
    public function testAddUseAutoGenerationNotice()
    {
        $formMock = $this->getMockBuilder(Form::class)
            ->setMethods(['getElement', 'setNote', 'getNote'])
            ->disableOriginalConstructor()
            ->getMock();
        $formMock->expects($this->once())->method('getElement')->with('use_auto_generation')->willReturnSelf();
        $formMock->expects($this->once())->method('setNote');
        $formMock->expects($this->once())->method('getNote');

        $this->eventObserver->expects($this->once())->method('getForm')->willReturn($formMock);
        $this->model->execute($this->eventObserver);
    }
}
