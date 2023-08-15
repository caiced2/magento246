<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Banner\Block\Adminhtml\Widget\Chooser;
use Magento\Banner\Model\Config;
use Magento\Framework\Data\Form\AbstractForm;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Hidden;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChooserTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Factory|MockObject
     */
    protected $elementFactoryMock;

    /**
     * @var Chooser|MockObject
     */
    protected $chooser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->elementFactoryMock = $this->createMock(Factory::class);

        $contextMock = $this->createMock(Context::class);
        $dataMock = $this->createMock(Data::class);
        $bannerColFactory = $this->getMockBuilder(\Magento\Banner\Model\ResourceModel\Banner\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $bannerConfig = $this->createMock(Config::class);

        $this->chooser = $this->getMockBuilder(Chooser::class)
            ->setMethods(['toHtml', '_construct'])
            ->setConstructorArgs(
                [
                    'context' => $contextMock,
                    'backendHelper' => $dataMock,
                    'bannerColFactory' => $bannerColFactory,
                    'bannerConfig' => $bannerConfig,
                    'elementFactory' => $this->elementFactoryMock
                ]
            )
            ->getMock();
    }

    /**
     * @return void
     */
    public function testPrepareElementHtml()
    {
        $elementId = 1;
        $elementData = 'Some data of element';
        $hiddenHtml = 'Some HTML';
        $toHtmlValue = 'to html';

        $this->chooser->expects($this->once())
            ->method('toHtml')
            ->willReturn($toHtmlValue);

        /** @var AbstractForm|MockObject $formMock */
        $formMock = $this->getMockForAbstractClass(AbstractForm::class, [], '', false);

        /** @var AbstractElement|MockObject $elementMock */
        $elementMock = $this->getMockBuilder(AbstractElement::class)
            ->setMethods(['getId', 'getValue', 'getData', 'getForm', 'setValue', 'setValueClass', 'setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $elementMock->expects($this->once())
            ->method('getId')
            ->willReturn($elementId);
        $elementMock->expects($this->once())
            ->method('getValue')
            ->willReturn('some value');
        $elementMock->expects($this->once())
            ->method('getData')
            ->willReturn($elementData);
        $elementMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);
        $elementMock->expects($this->once())
            ->method('setValue')
            ->with('')
            ->willReturnSelf();
        $elementMock->expects($this->once())
            ->method('setValueClass')
            ->with('value2')
            ->willReturnSelf();
        $elementMock->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['css_class', 'grid-chooser'],
                ['after_element_html', $hiddenHtml . $toHtmlValue],
                ['no_wrap_as_addon', true]
            )
            ->willReturnSelf();

        /** @var Hidden|MockObject $hiddenMock */
        $hiddenMock = $this->createMock(Hidden::class);
        $hiddenMock->expects($this->once())
            ->method('setId')
            ->with($elementId)
            ->willReturnSelf();
        $hiddenMock->expects($this->once())
            ->method('setForm')
            ->with($formMock)
            ->willReturnSelf();
        $hiddenMock->expects($this->once())
            ->method('getElementHtml')
            ->willReturn($hiddenHtml);

        $this->elementFactoryMock->expects($this->once())
            ->method('create')
            ->with('hidden', ['data' => $elementData])
            ->willReturn($hiddenMock);

        $this->assertSame($elementMock, $this->chooser->prepareElementHtml($elementMock));
    }
}
