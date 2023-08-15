<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Attributes;

use Magento\Support\Model\Report\Group\Attributes\CustomerAddressEavAttributesSection;

class CustomerAddressEavAttributesSectionTest extends AbstractTest
{
    protected function setUp(): void
    {
        parent::prepareObjects(
            CustomerAddressEavAttributesSection::class
        );
    }

    public function testGenerate()
    {
        $entityTypeId = '11';
        $data = [
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '1',
                    'attribute_code' => 'code1',
                    'is_user_defined' => '1',
                    'source_model' => 'Source\Model\First',
                    'backend_model' => 'Backend\Model\First',
                    'frontend_model' => null,
                    'frontend_input' => 'select',
                    'backend_type' => 'int'
                ]
            ),
            $this->createEntityAttributeMock(
                [
                    'attribute_id' => '2',
                    'attribute_code' => 'code2',
                    'is_user_defined' => '0',
                    'source_model' => 'Source\Model\Second',
                    'backend_model' => null,
                    'frontend_model' => 'Frontend\Model\Second',
                    'frontend_input' => 'input',
                    'backend_type' => 'string'
                ]
            )
        ];
        $expectedResult = [
            (string)__('Customer Address Eav Attributes') => [
                'headers' => [
                    __('ID'), __('Code'), __('User Defined'), __('Source Model'),
                    __('Backend Model'), __('Frontend Model')
                ],
                'data' => [
                    [
                        '1',
                        'code1' . "\n" . '{frontend: select, backend: int}',
                        __('Yes'),
                        'Source\Model\First' . "\n" . 'Source/Model/First.php',
                        'Backend\Model\First' . "\n" . 'Backend/Model/First.php',
                        ''
                    ],
                    [
                        '2',
                        'code2' . "\n" . '{frontend: input, backend: string}',
                        __('No'),
                        'Source\Model\Second' . "\n" . 'Source/Model/Second.php',
                        '',
                        'Frontend\Model\Second' . "\n" . 'Frontend/Model/Second.php'
                    ]
                ]
            ]
        ];
        $entityAttributeCollectionMock = $this->createEntityAttributeCollectionMock($data);

        $this->entityTypeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createEntityTypeMock(['id' => $entityTypeId]));
        $this->attributeCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($entityAttributeCollectionMock);
        $entityAttributeCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_type_id', $entityTypeId)
            ->willReturnSelf();
        $this->dataFormatterMock->expects($this->any())
            ->method('prepareModelValue')
            ->willReturnMap(
                [
                    ['Source\Model\First', 'Source\Model\First' . "\n" . 'Source/Model/First.php'],
                    ['Backend\Model\First', 'Backend\Model\First' . "\n" . 'Backend/Model/First.php'],
                    ['Source\Model\Second', 'Source\Model\Second' . "\n" . 'Source/Model/Second.php'],
                    ['Frontend\Model\Second', 'Frontend\Model\Second' . "\n" . 'Frontend/Model/Second.php']
                ]
            );

        $this->assertEquals($expectedResult, $this->section->generate());
    }
}
