<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Test\Unit\Ui\DataProvider\Product\Form\Modifier\AbstractModifierTest;
use Magento\Rma\Model\Product\Source;
use Magento\Rma\Ui\DataProvider\Product\Form\Modifier\Rma;

class RmaTest extends AbstractModifierTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->arrayManagerMock->expects($this->any())
            ->method('merge')
            ->willReturnArgument(1);
    }

    /**
     * {@inheritdoc}
     */
    protected function createModel()
    {
        return $this->objectManager->getObject(Rma::class, [
            'locator' => $this->locatorMock,
            'arrayManager' => $this->arrayManagerMock,
        ]);
    }

    public function testModifyMeta()
    {
        $this->assertEmpty($this->getModel()->modifyMeta([]));

        $groupCode = 'test_group_code';
        $meta = [
            $groupCode => [
                'children' => [
                    'container_' . Rma::FIELD_IS_RMA_ENABLED => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => 'RMA',
                                ],
                            ],
                        ],
                        'children' => [
                            Rma::FIELD_IS_RMA_ENABLED => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'sortOrder' => 10,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertNotEmpty($this->getModel()->modifyMeta($meta));
    }

    public function testModifyData()
    {
        $modelId = 1;
        $data = [
            $modelId => [
                Rma::DATA_SOURCE_DEFAULT => [
                    Rma::FIELD_IS_RMA_ENABLED => Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG,
                ],
            ],
        ];

        $this->productMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $data = $this->getModel()->modifyData($data);
        $this->assertNotEmpty(
            $data[$modelId][Rma::DATA_SOURCE_DEFAULT]['use_config_' . Rma::FIELD_IS_RMA_ENABLED]
        );
    }
}
