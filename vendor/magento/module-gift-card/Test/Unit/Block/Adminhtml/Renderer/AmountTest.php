<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Block\Adminhtml\Renderer\Amount;
use PHPUnit\Framework\TestCase;

class AmountTest extends TestCase
{
    /**
     * @var Amount
     */
    protected $block;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(Amount::class);
    }

    public function testGetValues()
    {
        $data = [
            [
                'website_id' => '1',
                'value' => '15.000',
            ],
            [
                'website_id' => '2',
                'value' => '0.500',
            ],
            [
                'website_id' => '0',
                'value' => '3.000',
            ],
            [
                'website_id' => '1',
                'value' => '6.000',
            ],
            [
                'website_id' => '2',
                'value' => '0.900',
            ],
        ];

        $expected = [
            [
                'website_id' => '0',
                'value' => '3.000',
            ],
            [
                'website_id' => '1',
                'value' => '6.000',
            ],
            [
                'website_id' => '1',
                'value' => '15.000',
            ],
            [
                'website_id' => '2',
                'value' => '0.500',
            ],
            [
                'website_id' => '2',
                'value' => '0.900',
            ],
        ];

        $element = $this->getMockBuilder(AbstractElement::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->block->setElement($element);

        $element->expects($this->once())
            ->method('getValue')
            ->willReturn($data);

        $this->assertEquals($expected, $this->block->getValues());
    }
}
