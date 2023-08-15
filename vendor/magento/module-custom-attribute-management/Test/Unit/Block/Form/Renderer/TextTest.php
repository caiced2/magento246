<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomAttributeManagement\Test\Unit\Block\Form\Renderer;

use Magento\CustomAttributeManagement\Block\Form\Renderer\Text;
use Magento\Eav\Model\Attribute;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    /**
     * Tested block.
     *
     * @var Text
     */
    private $block;

    /**
     * Context.
     *
     * @var Context | MockObject
     */
    private $context;

    /**
     * Block attributes.
     *
     * @var Attribute | MockObject
     */
    private $attributes;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->attributes = $this->createMock(Attribute::class);
        $this->attributes
            ->method('getIsRequired')
            ->willReturn(true);

        $this->block = new Text($this->context);
        $this->block->setAttributeObject($this->attributes);
    }

    /**
     * Tests element html class generation.
     *
     * @param String $validationRule
     * @param String $expectedClass
     * @dataProvider validationClassDataProvider
     */
    public function testGetHtmlClass(String $validationRule, String $expectedClass): void
    {
        $validateRules = [
            'input_validation' => $validationRule
        ];

        $this->attributes
            ->method('getValidateRules')
            ->willReturn($validateRules);

        self::assertEquals(' required-entry ' . $expectedClass, $this->block->getHtmlClass());
    }

    /**
     * Provider validation types and corresponding html classes.
     *
     * @return array
     */
    public function validationClassDataProvider(): array
    {
        return [
            ['alphanumeric', 'validate-alphanum'],
            ['alphanum-with-spaces', 'validate-alphanum-with-spaces'],
            ['numeric', 'validate-digits'],
            ['alpha', 'validate-alpha'],
            ['email', 'validate-email'],
            ['url', 'validate-url'],
            ['date', 'product-custom-option datetime-picker input-text validate-date']
        ];
    }
}
