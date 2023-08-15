<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Block\Adminhtml\Event\Edit;

use Magento\Backend\Block\Widget\Context as WidgetContext;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Form
 */
class FormTest extends TestCase
{
    /**
     * @var Form
     */
    protected $form;

    /**
     * @var \Magento\Backend\Block\Template\Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var FormFactory|MockObject
     */
    protected $formFactoryMock;

    /**
     * @var Data|MockObject
     */
    protected $backendHelperMock;

    /**
     * @var MockObject
     */
    protected $categoryFactoryMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = (new ObjectManager($this))->getObject(WidgetContext::class);
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formFactoryMock = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->form = new Form(
            $this->contextMock,
            $this->registryMock,
            $this->formFactoryMock,
            $this->backendHelperMock,
            $this->categoryFactoryMock
        );
    }

    /**
     * @return void
     */
    public function testGetActionUrl()
    {
        /** @var MockObject $urlBuilderMock */
        $urlBuilderMock = $this->contextMock->getUrlBuilder();
        $urlBuilderMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('adminhtml/*/save', ['_current' => true])
            ->willReturn('Result');

        $this->assertEquals('Result', $this->form->getActionUrl());
    }

    /**
     * @return void
     */
    public function testGetEvent()
    {
        $this->registryMock
            ->expects($this->once())
            ->method('registry')
            ->with('magento_catalogevent_event')
            ->willReturn('Result');

        $this->assertEquals('Result', $this->form->getEvent());
    }
}
