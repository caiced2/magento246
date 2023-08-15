<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\Framework\View\Page\Title;
use Magento\TargetRule\Controller\Adminhtml\Targetrule\Edit;
use Magento\TargetRule\Model\Rule;

class EditTest extends AbstractTest
{
    /**
     * @var Edit
     */
    protected $controller;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new Edit(
            $this->contextMock,
            $this->registryMock,
            $this->dateMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $menuModelMock = $this->getMockBuilder(Menu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $menuModelMock
            ->expects($this->any())
            ->method('getParentItems')
            ->willReturn([]);

        $this->menuBlockMock
            ->expects($this->any())
            ->method('getMenuModel')
            ->willReturn($menuModelMock);

        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $titleMock
            ->expects($this->exactly(2))
            ->method('prepend')
            ->withConsecutive(
                [new Phrase('Related Products Rule')],
                ['New Name']
            );
        $this->viewMock
            ->expects($this->any())
            ->method('getPage')
            ->willReturn(new DataObject(['config' => new DataObject(['title' => $titleMock])]));

        $this->objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [Session::class, $this->sessionMock]
                ]
            );

        $ruleMock = $this->getMockBuilder(Rule::class)
            ->setMethods(['addData', 'getName', 'getId', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock
            ->expects($this->once())
            ->method('addData')
            ->with(['some data']);
        $ruleMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $ruleMock
            ->expects($this->any())
            ->method('getName')
            ->willReturn('New Name');

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['id', null, 123]
            ]);

        $this->objectManagerMock
            ->expects($this->once())
            ->method('create')
            ->with(Rule::class)
            ->willReturn($ruleMock);

        $this->sessionMock
            ->expects($this->any())
            ->method('getFormData')
            ->with(true)
            ->willReturn(['some data']);

        $this->controller->execute();
    }

    /**
     * @return void
     */
    public function testExecuteRuleNotExists()
    {
        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['id', null, 123]
            ]);

        $ruleMock = $this->getMockBuilder(Rule::class)
            ->setMethods(['getId', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(false);

        $this->objectManagerMock
            ->expects($this->once())
            ->method('create')
            ->with(Rule::class)
            ->willReturn($ruleMock);

        $this->responseMock
            ->expects($this->once())
            ->method('setRedirect')
            ->with('adminhtml/*');

        $this->controller->execute();
    }
}
