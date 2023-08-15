<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model\Order\Archive\Grid\Row;

use Magento\Backend\Model\Url;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\DataObject;
use Magento\SalesArchive\Model\Order\Archive\Grid\Row\UrlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlGeneratorTest extends TestCase
{
    /**
     * @var UrlGenerator $_model
     */
    protected $_model;

    /**
     * @var MockObject $_authorization
     */
    protected $_authorizationMock;

    /**
     * @var MockObject $_urlModel
     */
    protected $_urlModelMock;

    protected function setUp(): void
    {
        $this->_authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->getMock();

        $this->_urlModelMock = $this->createMock(Url::class);

        $urlMap = [
            [
                'sales/order/view',
                ['order_id' => null],
                'http://localhost/backend/sales/order/view/order_id/',
            ],
            ['sales/order/view', ['order_id' => 1], 'http://localhost/backend/sales/order/view/order_id/1'],
        ];
        $this->_urlModelMock->expects($this->any())->method('getUrl')->willReturnMap($urlMap);

        $this->_model = new UrlGenerator(
            $this->_urlModelMock,
            $this->_authorizationMock,
            ['path' => 'sales/order/view', 'extraParamsTemplate' => ['order_id' => 'getId']]
        );
    }

    public function testAuthNotAllowed()
    {
        $this->_authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with('Magento_SalesArchive::orders')
            ->willReturn(false);

        $this->assertFalse($this->_model->getUrl(new DataObject()));
    }

    /**
     * @param $item
     * @param $expectedUrl
     * @dataProvider itemsDataProvider
     */
    public function testAuthAllowed($item, $expectedUrl)
    {
        $this->_authorizationMock->expects($this->any())
            ->method('isAllowed')
            ->with('Magento_SalesArchive::orders')
            ->willReturn(true);
        $result = $this->_model->getUrl($item);

        $this->assertEquals($expectedUrl, $result);
    }

    public function itemsDataProvider()
    {
        return [
            [new DataObject(), 'http://localhost/backend/sales/order/view/order_id/'],
            [
                new DataObject(['id' => 1]),
                'http://localhost/backend/sales/order/view/order_id/1'
            ]
        ];
    }
}
