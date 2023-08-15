<?php declare(strict_types=1);
/**
 * Test \Magento\Logging\Model\Config
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Test\Unit\Model\Handler;

use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\Event\Changes;
use Magento\Logging\Model\Event\ChangesFactory;
use Magento\Logging\Model\Handler\Controllers;
use Magento\Logging\Model\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ControllersTest extends TestCase
{
    /**
     * @var Controllers
     */
    protected $object;

    /**
     * @var Http|MockObject
     */
    protected $request;

    /**
     * @var ChangesFactory|MockObject
     */
    protected $eventChangesFactory;

    /**
     * @var Changes|MockObject
     */
    protected $eventChanges;

    /**
     * @var Event
     */
    private $eventModel;

    /**
     * @var Structure|MockObject
     */
    protected $configStructure;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Processor|MockObject
     */
    protected $processor;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->request = $this->createMock(Http::class);
        $this->request->expects($this->any())->method('getParams')->willReturn([]);

        $this->eventChanges = new DataObject();
        $this->eventChangesFactory = $this->createPartialMock(
            ChangesFactory::class,
            ['create']
        );
        $this->eventChangesFactory->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->eventChanges
        );
        $messageCollection = $this->createMock(\Magento\Framework\Message\Collection::class);
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager->expects($this->any())->method('getMessages')->willReturn($messageCollection);
        $helper = new ObjectManager($this);
        $this->eventModel = $helper->getObject(Event::class);
        $this->processor = $this->getMockBuilder(
            Processor::class
        )->disableOriginalConstructor()
            ->getMock();

        $this->configStructure = $this->createPartialMock(
            Structure::class,
            ['getFieldPathsByAttribute']
        );
        $this->configStructure->expects(
            $this->any()
        )->method(
            'getFieldPathsByAttribute'
        )->willReturn(
            []
        );

        $this->object = $objectManager->getObject(
            Controllers::class,
            [
                'messageManager' => $this->messageManager,
                'request' => $this->request,
                'eventChangesFactory' => $this->eventChangesFactory,
                'structureConfig' => $this->configStructure
            ]
        );

        $this->processor = $this->createMock(Processor::class);
    }

    /**
     * @dataProvider postDispatchReportDataProvider
     */
    public function testPostDispatchReport($config, $expectedInfo)
    {
        $result = $this->object->postDispatchReport($config, $this->eventModel, $this->processor);
        if (is_object($result)) {
            $result = $result->getInfo();
        }
        $this->assertEquals($expectedInfo, $result);
    }

    /**
     * @dataProvider postDispatchSystemCurrencySaveDataProvider
     */
    public function testPostDispatchSystemCurrencySave($config, $rate, $expectedInfo)
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('rate')
            ->willReturn($rate);

        $result = $this->object->postDispatchSystemCurrencySave($config, $this->eventModel, $this->processor);
        if (is_object($result)) {
            $result = $result->getInfo();
        }
        $this->assertEquals($expectedInfo, $result);
    }

    /**
     * @return array
     */
    public function postDispatchReportDataProvider()
    {
        return [
            [['controller_action' => 'reports_report_shopcart_product'], 'shopcart_product'],
            [['controller_action' => 'some_another_value'], false]
        ];
    }

    /**
     * @return array
     */
    public function postDispatchSystemCurrencySaveDataProvider()
    {
        return [
          [
              ['controller_action' => 'system_currency_save'],
              ['USD' => ['UAH' => 30, "EUR" => 0.7067]],
              __('Currency Rates Saved')
          ],
          [
              ['controller_action' => 'system_currency_save'],
              ['USD' => ['UAH' => '', "EUR" => 0.7067]],
              __('Currency Rates Saved')
          ],
          [
              ['controller_action' => 'system_currency_save'],
              ['USD' => ['UAH' => null, "EUR" => 0.7067]],
              __('Currency Rates Saved')
          ],
        ];
    }
}
