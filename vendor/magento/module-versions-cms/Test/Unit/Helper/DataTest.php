<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Helper;

use Magento\Framework\Data\Form\AbstractForm;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VersionsCms\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $fieldSet;

    /**
     * @var MockObject
     */
    protected $elementInFieldSet;

    /**
     * @var MockObject
     */
    protected $element;

    /**
     * @var MockObject
     */
    protected $container;

    protected function setUp(): void
    {
        $this->fieldSet = $this->createMock(Fieldset::class);
        $this->elementInFieldSet = $this->createMock(AbstractElement::class);
        $this->element = $this->createMock(AbstractElement::class);
    }

    public function testAddAttributeToFormElements()
    {
        $attributeName = 'test-attribute';
        $attributeValue = 'test-value';

        $this->elementInFieldSet->expects($this->once())->method('setData')->with($attributeName, $attributeValue);

        $this->fieldSet->expects($this->once())->method('getType')->willReturn('fieldset');
        $this->fieldSet->expects($this->once())->method('getElements')->willReturn([$this->elementInFieldSet]);

        $this->element->expects($this->once())->method('setData')->with($attributeName, $attributeValue);

        $this->container = $this->createMock(AbstractForm::class);
        $this->container->expects($this->once())->method('getElements')->willReturn([$this->fieldSet, $this->element]);

        /** @var Data $helper */
        $helper = (new ObjectManager($this))
            ->getObject(Data::class);
        $helper->addAttributeToFormElements($attributeName, $attributeValue, $this->container);
    }
}
