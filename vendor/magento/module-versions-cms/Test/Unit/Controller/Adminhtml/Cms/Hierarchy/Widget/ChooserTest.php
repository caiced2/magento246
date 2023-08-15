<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Controller\Adminhtml\Cms\Hierarchy\Widget;

use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy\Widget\Chooser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChooserTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\Action\Context|MockObject
     */
    protected $contextMock;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ViewInterface|MockObject
     */
    protected $viewMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Chooser
     */
    protected $chooser;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createPartialMock(
            ActionContext::class,
            ['getView', 'getRequest', 'getResponse']
        );
        $this->viewMock = $this->getMockForAbstractClass(
            ViewInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getLayout']
        );
        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getParam']
        );
        $this->responseMock = $this->getMockForAbstractClass(
            ResponseInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setBody']
        );

        $this->objectManager = new ObjectManager($this);

        $this->contextMock->expects($this->once())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->contextMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->chooser = $this->objectManager->getObject(
            Chooser::class,
            [
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * Run test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $uniqId = 78946;
        $scope = 'scope-value';
        $scopeId = 744112;
        $html = 'test-html';

        $layoutMock = $this->getMockForAbstractClass(
            LayoutInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['createBlock']
        );
        $chooserMock = $this->getMockBuilder(
            \Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget\Chooser::class
        )->addMethods(['setScope', 'setScopeId'])
            ->onlyMethods(['toHtml'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['uniq_id', null, $uniqId],
                    ['scope', null, $scope],
                    ['scope_id', null, $scopeId]
                ]
            );

        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);
        $layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(
                \Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Widget\Chooser::class,
                '',
                ['data' => ['id' => $uniqId]]
            )
            ->willReturn($chooserMock);
        $chooserMock->expects($this->once())
            ->method('setScope')
            ->with($scope)
            ->willReturnSelf();
        $chooserMock->expects($this->once())
            ->method('setScopeId')
            ->with($scopeId)
            ->willReturnSelf();
        $chooserMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($html);
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($html);

        $this->chooser->execute();
    }
}
