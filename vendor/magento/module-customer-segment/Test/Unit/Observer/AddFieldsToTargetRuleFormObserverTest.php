<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Observer;

use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\CustomerSegment\Helper\Data;
use Magento\CustomerSegment\Observer\AddFieldsToTargetRuleFormObserver;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Layout;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddFieldsToTargetRuleFormObserverTest extends TestCase
{
    /**
     * @var AddFieldsToTargetRuleFormObserver
     */
    private $_model;

    /**
     * @var MockObject
     */
    private $_segmentHelper;

    protected function setUp(): void
    {
        $this->_segmentHelper = $this->createPartialMock(
            Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->_model = new AddFieldsToTargetRuleFormObserver(
            $this->_segmentHelper
        );
    }

    protected function tearDown(): void
    {
        $this->_model = null;
        $this->_segmentHelper = null;
    }

    public function testAddFieldsToTargetRuleForm()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(true);

        $formDependency = $this->createMock(Dependence::class);

        $layout = $this->createPartialMock(Layout::class, ['createBlock']);
        $layout->expects(
            $this->once()
        )->method(
            'createBlock'
        )->with(
            Dependence::class
        )->willReturn(
            $formDependency
        );

        $factoryElement = $this->createMock(Factory::class);
        $collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $formKey = $this->createMock(FormKey::class);
        $form = new Form($factoryElement, $collectionFactory, $formKey);
        $model = new DataObject();
        $block = new DataObject(['layout' => $layout]);

        $this->_segmentHelper->expects(
            $this->once()
        )->method(
            'addSegmentFieldsToForm'
        )->with(
            $form,
            $model,
            $formDependency
        );

        $this->_model->execute(
            new Observer(
                [
                    'event' => new DataObject(
                        ['form' => $form, 'model' => $model, 'block' => $block]
                    )
                ]
            )
        );
    }

    public function testAddFieldsToTargetRuleFormDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->willReturn(false);

        $layout = $this->createPartialMock(Layout::class, ['createBlock']);
        $layout->expects($this->never())->method('createBlock');

        $factoryElement = $this->createMock(Factory::class);
        $collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $formKey = $this->createMock(FormKey::class);
        $form = new Form($factoryElement, $collectionFactory, $formKey);
        $model = new DataObject();
        $block = new DataObject(['layout' => $layout]);

        $this->_segmentHelper->expects($this->never())->method('addSegmentFieldsToForm');

        $this->_model->execute(
            new Observer(
                [
                    'event' => new DataObject(
                        ['form' => $form, 'model' => $model, 'block' => $block]
                    )
                ]
            )
        );
    }
}
