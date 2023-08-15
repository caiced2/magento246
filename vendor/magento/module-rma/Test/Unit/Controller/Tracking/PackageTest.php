<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Tracking;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Controller\Tracking\Package;
use Magento\Rma\Model\Shipping;
use Magento\Rma\Model\Shipping\Info;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class to test controller to load Rma Packages
 */
class PackageTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ViewInterface|MockObject
     */
    private $viewMock;

    /**
     * @var Registry|MockObject
     */
    private $registryMock;

    /**
     * @var Info|MockObject
     */
    private $shippingInfoMock;

    /**
     * @var Package
     */
    private $controller;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->viewMock = $this->getMockForAbstractClass(ViewInterface::class);
        $this->contextMock->method('getView')->willReturn($this->viewMock);
        $this->registryMock = $this->createMock(Registry::class);
        $this->shippingInfoMock = $this->createMock(Info::class);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Package::class,
            [
                'context' => $this->contextMock,
                'coreRegistry' => $this->registryMock,
                'shippingInfo' => $this->shippingInfoMock,
            ]
        );
    }

    /**
     * Test to execute tracking package
     */
    public function testExecute()
    {
        $hash = 'hash123';
        $packagesVal = 'packages vale';

        $this->requestMock->method('getParam')
            ->with('hash')
            ->willReturn($hash);
        $this->viewMock->expects($this->once())->method('loadLayout');
        $this->viewMock->expects($this->once())->method('renderLayout');

        $shippingLabelMock = $this->getMockBuilder(Shipping::class)
            ->addMethods(['getPackages'])
            ->disableOriginalConstructor()
            ->getMock();
        $shippingLabelMock->method('getPackages')
            ->willReturn($packagesVal);
        $this->shippingInfoMock->expects($this->once())
            ->method('loadPackage')
            ->with($hash)
            ->willReturn($shippingLabelMock);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('rma_package_shipping', $shippingLabelMock);

        $this->controller->execute();
    }

    /**
     * Test to execute tracking package with NotFoundException
     */
    public function testExecuteNotFoundException()
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->expectExceptionMessage('Page not found.');
        $hash = 'hash123';
        $this->requestMock->method('getParam')
            ->with('hash')
            ->willReturn($hash);
        $shippingLabelMock = $this->getMockBuilder(Shipping::class)
            ->addMethods(['getPackages'])
            ->disableOriginalConstructor()
            ->getMock();
        $shippingLabelMock->method('getPackages')->willReturn('');
        $this->shippingInfoMock->expects($this->once())
            ->method('loadPackage')
            ->with($hash)
            ->willReturn($shippingLabelMock);

        $this->viewMock->expects($this->never())->method('loadLayout');
        $this->viewMock->expects($this->never())->method('renderLayout');
        $this->registryMock->expects($this->never())
            ->method('register')
            ->with('rma_package_shipping', $shippingLabelMock);

        $this->controller->execute();
    }
}
