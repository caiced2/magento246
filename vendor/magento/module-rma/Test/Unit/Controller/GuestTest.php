<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url;
use Magento\Rma\Helper\Data;
use Magento\Sales\Helper\Guest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class GuestTest extends TestCase
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var Guest
     */
    protected $controller;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistry;

    /**
     * @var Http|MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Response\Http|MockObject
     */
    protected $response;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManager;

    /**
     * @var Url|MockObject
     */
    protected $url;

    /**
     * @var RedirectInterface|MockObject
     */
    protected $redirect;

    /**
     * @var Manager|MockObject
     */
    protected $messageManager;

    /**
     * @var Data|MockObject
     */
    protected $rmaHelper;

    /**
     * @var \Magento\Sales\Helper\Guest|MockObject
     */
    protected $salesGuestHelper;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirect;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $resultRedirectFactory;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->request = $this->createMock(Http::class);
        $this->response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->objectManager = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->messageManager = $this->createMock(Manager::class);
        $this->redirect = $this->createMock(\Magento\Store\App\Response\Redirect::class);
        $this->url = $this->createMock(Url::class);
        $this->rmaHelper = $this->createMock(Data::class);
        $this->salesGuestHelper = $this->createMock(Guest::class);

        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory = $this->getMockBuilder(
            RedirectFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRedirectFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $context = $this->createMock(Context::class);
        $context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $context->expects($this->once())->method('getResponse')->willReturn($this->response);
        $context->expects($this->once())->method('getObjectManager')->willReturn($this->objectManager);
        $context->expects($this->once())->method('getMessageManager')->willReturn($this->messageManager);
        $context->expects($this->once())->method('getRedirect')->willReturn($this->redirect);
        $context->expects($this->once())->method('getUrl')->willReturn($this->url);
        $context->expects($this->once())->method('getResultRedirectFactory')->willReturn($this->resultRedirectFactory);

        $this->coreRegistry = $this->createPartialMock(Registry::class, ['registry']);

        $this->controller = $objectManagerHelper->getObject(
            '\\Magento\\Rma\\Controller\\Guest\\' . $this->name,
            [
                'coreRegistry' => $this->coreRegistry,
                'context' => $context,
                'rmaHelper' => $this->rmaHelper,
                'salesGuestHelper' => $this->salesGuestHelper,
            ]
        );
    }
}
