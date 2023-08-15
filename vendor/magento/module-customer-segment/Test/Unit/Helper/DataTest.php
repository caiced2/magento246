<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Helper;

use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\CustomerSegment\Helper\Data;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    private $_helper;

    /**
     * @var MockObject
     */
    private $_scopeConfig;

    /**
     * @var MockObject
     */
    private $_segmentCollection;

    /**
     * @var MockObject
     */
    private $_formKeyMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->_formKeyMock = $this->createMock(FormKey::class);

        $objectManager = new ObjectManager($this);
        $className = Data::class;
        $arguments = $objectManager->getConstructArguments($className);
        /** @var Context $context */
        $context = $arguments['context'];

        $this->_scopeConfig = $context->getScopeConfig();
        $this->_segmentCollection = $arguments['segmentCollection'];

        $this->_helper = $objectManager->getObject($className, $arguments);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->_helper = null;
        $this->_scopeConfig = null;
        $this->_segmentCollection = null;
    }

    /**
     * @param array $fixtureFormData
     *
     * @return void
     * @dataProvider addSegmentFieldsToFormDataProvider
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddSegmentFieldsToForm(array $fixtureFormData): void
    {
        $this->_scopeConfig->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            Data::XML_PATH_CUSTOMER_SEGMENT_ENABLER
        )->willReturn(
            '1'
        );

        $this->_segmentCollection->expects(
            $this->once()
        )->method(
            'toOptionArray'
        )->willReturn(
            [10 => 'Devs', 20 => 'QAs']
        );

        $fieldset = $this->createPartialMock(Fieldset::class, ['addField']);
        $fieldset
            ->method('addField')
            ->withConsecutive(
                [$this->logicalOr($this->equalTo('use_customer_segment'), $this->equalTo('select'))],
                [$this->logicalOr($this->equalTo('customer_segment_ids'), $this->equalTo('multiselect'))]
            );

        $form = $this->getMockBuilder(Form::class)
            ->addMethods(['getHtmlIdPrefix'])
            ->onlyMethods(['getElement'])
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects(
            $this->once()
        )->method(
            'getElement'
        )->with(
            'base_fieldset'
        )->willReturn(
            $fieldset
        );
        $form->expects($this->once())->method('getHtmlIdPrefix')->willReturn('pfx_');

        $data = new DataObject($fixtureFormData);

        $dependencies = $this->createPartialMock(
            Dependence::class,
            ['addFieldMap', 'addFieldDependence']
        );
        $dependencies
            ->method('addFieldMap')
            ->withConsecutive(
                ['pfx_use_customer_segment', 'use_customer_segment'],
                ['pfx_customer_segment_ids', 'customer_segment_ids']
            )
            ->willReturnOnConsecutiveCalls($dependencies, $dependencies);
        $dependencies->expects(
            $this->once()
        )->method(
            'addFieldDependence'
        )->with(
            'customer_segment_ids',
            'use_customer_segment',
            '1'
        )->willReturnSelf();

        $this->_helper->addSegmentFieldsToForm($form, $data, $dependencies);
    }

    /**
     * @return array
     */
    public function addSegmentFieldsToFormDataProvider(): array
    {
        return [
            'all segments' => [[]],
            'specific segments' => [['customer_segment_ids' => [123, 456]]]
        ];
    }

    /**
     * @return void
     */
    public function testAddSegmentFieldsToFormDisabled(): void
    {
        $this->_scopeConfig->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            Data::XML_PATH_CUSTOMER_SEGMENT_ENABLER
        )->willReturn(
            '0'
        );

        $this->_segmentCollection->expects($this->never())->method('toOptionArray');

        $factory = $this->createMock(Factory::class);
        $collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $form = new Form(
            $factory,
            $collectionFactory,
            $this->_formKeyMock,
            ['html_id_prefix' => 'pfx_']
        );
        $data = new DataObject();
        $dependencies = $this->createPartialMock(
            Dependence::class,
            ['addFieldMap', 'addFieldDependence']
        );

        $dependencies->expects($this->never())->method('addFieldMap');
        $dependencies->expects($this->never())->method('addFieldDependence');

        $this->_helper->addSegmentFieldsToForm($form, $data, $dependencies);

        $this->assertNull($data->getData('use_customer_segment'));
        $this->assertNull($form->getElement('use_customer_segment'));
        $this->assertNull($form->getElement('customer_segment_ids'));
    }
}
