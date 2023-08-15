<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Renderer;

use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Checkbox;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Block\Adminhtml\Renderer\OpenAmount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OpenAmountTest extends TestCase
{
    /**
     * @var OpenAmount
     */
    protected $block;

    /**
     * @var MockObject
     */
    protected $factory;

    /**
     * @var Checkbox
     */
    protected $element;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();
        $objectManager = new ObjectManager($this);
        $this->element = $objectManager->getObject(Checkbox::class);
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHtmlIdPrefix', 'getHtmlIdSuffix'])
            ->getMock();
        $form->expects($this->any())->method('getHtmlIdPrefix')->willReturn('');
        $form->expects($this->any())->method('getHtmlIdSuffix')->willReturn('');

        $this->factory->expects($this->once())->method('create')->willReturn($this->element);
        $this->block = $objectManager->getObject(
            OpenAmount::class,
            [
                'factoryElement' => $this->factory
            ]
        );
        $this->block->setForm($form);
    }

    public function testGetElementHtml()
    {
        $this->block->setReadonlyDisabled(true);
        $this->assertStringContainsString('disabled', $this->block->getElementHtml());
    }
}
