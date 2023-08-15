<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma;

use Magento\Backend\Block\Widget\Context as WidgetContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Rma\Block\Adminhtml\Rma\Edit;
use Magento\Rma\Model\Rma;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests Magento\Rma\Block\Adminhtml\Rma\Edit.
 */
class EditTest extends TestCase
{
    /**
     * @var Edit
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var WidgetContext
     */
    private $context;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Rma
     */
    private $rma;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getServer'])
            ->getMockForAbstractClass();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();
        $this->rma = $this->createMock(Rma::class);
        $this->rma->method('getOrderId')->willReturn(1);
        $this->rma->method('getCustomerId')->willReturn(1);
    }

    /**
     * Method to test get back url with referrer.
     *
     * @param string $referrer
     * @param string $expectation
     * @return void
     *
     * @dataProvider getBackUrlDataProvider
     */
    public function testGetBackUrlWithReferrer(string $referrer, string $expectation): void
    {
        $this->request->method('getServer')
            ->with('HTTP_REFERER', '')
            ->willReturnOnConsecutiveCalls('', $referrer);
        $this->createEditMockModel();
        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->willReturnCallback(
                function (string $route = '', array $params = []) {
                    $routeParams = '';
                    array_walk($params, function ($value, $key) use (&$routeParams) {
                        $routeParams .= (strlen($routeParams) ? '/' : '') . $key . '/' . $value;
                    });

                    return "http://localhost/admin/{$route}{$routeParams}";
                }
            );

        $this->assertStringContainsString($expectation, $this->model->getBackUrl());
    }

    /**
     * @return array
     */
    public function getBackUrlDataProvider(): array
    {
        return [
            ['http://localhost/admin/sales/order/view/order_id/1', 'sales/order/view/order_id/1'],
            ['http://localhost/admin/customer/index/edit/id/1', 'customer/index/edit/id/1']
        ];
    }

    /**
     * Method to create edit mock model.
     *
     * @return void
     */
    private function createEditMockModel(): void
    {
        $this->context = $this->objectManager->getObject(
            WidgetContext::class,
            [
                'request' => $this->request,
                'urlBuilder' => $this->urlBuilder
            ]
        );

        $this->model = $this->objectManager->getObject(
            Edit::class,
            [
                'context' => $this->context
            ]
        );
        $this->objectManager->setBackwardCompatibleProperty($this->model, '_rma', $this->rma);
    }
}
