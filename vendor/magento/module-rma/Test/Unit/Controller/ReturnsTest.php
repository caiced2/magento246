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
use Magento\Framework\Message\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url;
use Magento\Store\App\Response\Redirect;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class ReturnsTest extends TestCase
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var Returns
     */
    protected $controller;

    /**
     * @var Context|MockObject
     */
    protected $context;

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

    protected function initContext()
    {
        $this->context = $this->createMock(Context::class);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);
        $this->context->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);
        $this->context->expects($this->once())
            ->method('getRedirect')
            ->willReturn($this->redirect);
        $this->context->expects($this->once())
            ->method('getUrl')
            ->willReturn($this->url);
    }

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->request = $this->createMock(Http::class);
        $this->response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->objectManager = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->messageManager = $this->createMock(Manager::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->url = $this->createMock(Url::class);

        $this->initContext();

        $this->coreRegistry = $this->createMock(Registry::class);

        $this->controller = $objectManagerHelper->getObject(
            '\\Magento\\Rma\\Controller\\Returns\\' . $this->name,
            [
                'coreRegistry' => $this->coreRegistry,
                'context' => $this->context
            ]
        );
    }
}
