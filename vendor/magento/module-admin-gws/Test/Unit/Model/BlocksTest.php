<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\AdminGws\Model\Blocks;
use Magento\Framework\Data\Form;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class BlocksTest extends TestCase
{
    /**
     * @var Blocks
     */
    protected $_model;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $this->_model = $helper->getObject(Blocks::class);
    }

    public function testDisableTaxRelatedMultiselects()
    {
        $form = $this->getMockBuilder(Form::class)
            ->addMethods(['setDisabled'])
            ->onlyMethods(['getElement'])
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects(
            $this->exactly(3)
        )->method(
            'getElement'
        )->with(
            $this->logicalOr(
                $this->equalTo('tax_customer_class'),
                $this->equalTo('tax_product_class'),
                $this->equalTo('tax_rate')
            )
        )->willReturnSelf();

        $form->expects(
            $this->exactly(3)
        )->method(
            'setDisabled'
        )->with(
            true
        )->willReturnSelf();

        $observerMock = new DataObject(
            [
                'event' => new DataObject(
                    [
                        'block' => new DataObject(['form' => $form]),
                    ]
                ),
            ]
        );

        $this->_model->disableTaxRelatedMultiselects($observerMock);
    }
}
