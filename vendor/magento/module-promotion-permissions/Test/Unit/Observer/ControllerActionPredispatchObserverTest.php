<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PromotionPermissions\Test\Unit\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\PromotionPermissions\Helper\Data;
use Magento\PromotionPermissions\Observer\ControllerActionPredispatchObserver;
use Magento\SalesRule\Controller\Adminhtml\Promo\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ControllerActionPredispatchObserverTest extends TestCase
{
    /**
     * @var ControllerActionPredispatchObserver
     */
    private $model;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Quote|MockObject
     */
    private $controllerAction;

    /**
     * @var Data|MockObject
     */
    private $promoPermData;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->request =  $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['initForward', 'setDispatched']
        );
        $this->promoPermData =  $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->promoPermData->method('getCanAdminEditSalesRules')->willReturn(false);
        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction'])
            ->getMock();
        $this->controllerAction = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $objectManagerHelper->getObject(
            ControllerActionPredispatchObserver::class,
            [
                'promoPermData' => $this->promoPermData,
                'request' => $this->request,
            ]
        );
    }

    /**
     * @param $actionName
     * @dataProvider dataProvider
     */
    public function testExecute($actionName)
    {
        $this->observer->expects($this->once())
            ->method('getControllerAction')
            ->willReturn($this->controllerAction);
        $this->request->expects($this->any())
            ->method('getActionName')
            ->willReturn($actionName);
        $this->request->expects($this->once())
            ->method('setActionName')
            ->willReturnSelf();

        $this->model->execute($this->observer);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            ['DELETE'],
            ['delete']
        ];
    }
}
