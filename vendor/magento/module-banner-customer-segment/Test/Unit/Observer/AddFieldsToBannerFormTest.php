<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\BannerCustomerSegment\Observer\AddFieldsToBannerForm;
use Magento\CustomerSegment\Helper\Data;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddFieldsToBannerFormTest extends TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\AddFieldsToBannerForm
     */
    private $addFieldsToBannerFormObserver;

    /**
     * @var MockObject
     */
    private $_segmentHelper;

    /**
     * @var MockObject
     */
    private $_formKeyMock;

    protected function setUp(): void
    {
        $this->_segmentHelper = $this->createPartialMock(
            Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->addFieldsToBannerFormObserver = new AddFieldsToBannerForm(
            $this->_segmentHelper
        );

        $this->_formKeyMock = $this->createMock(FormKey::class);
    }

    protected function tearDown(): void
    {
        $this->_segmentHelper = null;
        $this->addFieldsToBannerFormObserver = null;
        $this->_formKeyMock = null;
    }

    public function testAddFieldsToBannerForm()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(true);

        $factory = $this->createMock(Factory::class);
        $collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $form = new Form($factory, $collectionFactory, $this->_formKeyMock);
        $model = new DataObject();
        $block = $this->createMock(Dependence::class);

        $this->_segmentHelper->expects($this->once())->method('addSegmentFieldsToForm')->with($form, $model, $block);

        $this->addFieldsToBannerFormObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(
                        ['form' => $form, 'model' => $model, 'after_form_block' => $block]
                    ),
                ]
            )
        );
    }

    public function testAddFieldsToBannerFormDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(false);

        $factory = $this->createMock(Factory::class);
        $collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );

        $form = new Form($factory, $collectionFactory, $this->_formKeyMock);
        $model = new DataObject();
        $block = $this->createMock(Dependence::class);

        $this->_segmentHelper->expects($this->never())->method('addSegmentFieldsToForm');

        $this->addFieldsToBannerFormObserver->execute(
            new Observer(
                [
                    'event' => new DataObject(
                        ['form' => $form, 'model' => $model, 'after_form_block' => $block]
                    ),
                ]
            )
        );
    }
}
