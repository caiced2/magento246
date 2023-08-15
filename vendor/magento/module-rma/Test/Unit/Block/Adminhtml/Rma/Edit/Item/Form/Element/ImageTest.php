<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Item\Form\Element;

use Magento\Backend\Helper\Data;
use Magento\Customer\Block\Adminhtml\Form\Element\Image;
use Magento\Framework\Data\Form;
use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element\Image
 */
class ImageTest extends TestCase
{
    /**
     * @var Image
     */
    protected $image;

    /**
     * @var MockObject
     */
    protected $backendHelperMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $escaper = $objectManager->getObject(Escaper::class);
        $this->backendHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->image = $objectManager->getObject(
            Image::class,
            [
                'adminhtmlData' => $this->backendHelperMock,
                '_escaper' => $escaper
            ]
        );
    }

    public function testGetHiddenInput()
    {
        $name = 'test_name';
        $formMock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->image->setForm($formMock);
        $this->image->setName($name);

        $this->assertStringContainsString($name, $this->image->getElementHtml());
    }
}
