<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Test\Unit\Observer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory;
use Magento\Framework\Event;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\WebsiteRestriction\Model\ConfigInterface;
use Magento\WebsiteRestriction\Model\Restrictor;
use Magento\WebsiteRestriction\Observer\RestrictWebsite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RestrictWebsiteTest extends TestCase
{
    /**
     * @var \Magento\WebsiteRestriction\Model\Observer\RestrictWebsite
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $configMock;

    /**
     * @var MockObject
     */
    protected $restrictorMock;

    /**
     * @var MockObject
     */
    protected $controllerMock;

    /**
     * @var MockObject
     */
    protected $dispatchResultMock;

    /**
     * @var MockObject
     */
    protected $requestMock;
    /**
     * @var MockObject
     */
    protected $observer;

    protected function setUp(): void
    {
        $this->markTestSkipped();
        $this->configMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->observer = $this->createMock(Observer::class);
        $this->controllerMock = $this->createMock(Action::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getControllerAction', 'getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getControllerAction')
            ->willReturn(
                $this->controllerMock
            );

        $eventMock->expects($this->any())
            ->method('getRequest')
            ->willReturn(
                $this->requestMock
            );

        $this->observer->expects($this->any())->method('getEvent')->willReturn($eventMock);

        $this->restrictorMock = $this->createMock(Restrictor::class);
        $this->dispatchResultMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getCustomerLoggedIn', 'getShouldProceed'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $eventManagerMock->expects($this->once())->method('dispatch')->with(
            'websiterestriction_frontend',
            ['controller' => $this->controllerMock, 'result' => $this->dispatchResultMock]
        );

        $responseMock = $this->createMock(Http::class);

        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('create')
            ->with(['should_proceed' => true, 'customer_logged_in' => false])
            ->willReturn($this->dispatchResultMock);

        $this->model = new RestrictWebsite(
            $this->configMock,
            $eventManagerMock,
            $this->restrictorMock,
            $factoryMock,
            $responseMock
        );
    }

    public function testExecuteSuccess()
    {
        $this->dispatchResultMock->expects($this->any())->method('getShouldProceed')->willReturn(true);
        $this->configMock->expects($this->any())->method('isRestrictionEnabled')->willReturn(true);
        $this->dispatchResultMock->expects($this->once())->method('getCustomerLoggedIn')->willReturn(1);

        $responseMock = $this->getMockForAbstractClass(ResponseInterface::class);
        $this->controllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $this->restrictorMock->expects($this->once())->method('restrict')->with($this->requestMock, $responseMock, 1);
        $this->model->execute($this->observer);
    }

    public function testExecuteWithDisabledRestriction()
    {
        $this->configMock->expects($this->any())->method('isRestrictionEnabled')->willReturn(false);
        $this->restrictorMock->expects($this->never())->method('restrict');
        $this->model->execute($this->observer);
    }

    public function testExecuteWithNotShouldProceed()
    {
        $this->dispatchResultMock->expects($this->any())->method('getShouldProceed')->willReturn(false);
        $this->restrictorMock->expects($this->never())->method('restrict');
        $this->model->execute($this->observer);
    }
}
