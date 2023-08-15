<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\CatalogInventoryStaging\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdvancedInventoryTest extends TestCase
{
    /**
     * @var AdvancedInventory
     */
    private $model;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var MockObject
     */
    private $inventoryModifierMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->arrayManager = $objectManagerHelper->getObject(ArrayManager::class);
        $this->inventoryModifierMock = $this->createPartialMock(
            \Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory::class,
            ['modifyData', 'modifyMeta']
        );
        $this->model = new AdvancedInventory(
            $this->inventoryModifierMock
        );
        $objectManagerHelper->setBackwardCompatibleProperty($this->model, 'arrayManager', $this->arrayManager);
    }

    public function testModifyData()
    {
        $data = ['key' => 'value'];
        $this->inventoryModifierMock->expects($this->once())->method('modifyData')->with($data)->willReturn($data);
        $this->assertEquals($data, $this->model->modifyData($data));
    }

    public function testModifyMeta()
    {
        $meta = [
            'product-details' => [
                'children' => [
                    'quantity_and_stock_status_qty' => [
                        'in_stock' => true,
                        'qty' => 100
                    ],
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $modifiedMeta = [
            'product-details' => [
                'children' => [
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => true
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->inventoryModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);

        $this->assertEquals($modifiedMeta, $this->model->modifyMeta($meta));
    }

    public function testModifyMetaWithNotDefaultAttributeSet()
    {
        $meta = [
            'inventory-details' => [
                'children' => [
                    'quantity_and_stock_status_qty' => [
                        'in_stock' => true,
                        'qty' => 100
                    ],
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $modifiedMeta = [
            'inventory-details' => [
                'children' => [
                    'quantity_and_stock_status' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => true
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->inventoryModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);

        $this->assertEquals($modifiedMeta, $this->model->modifyMeta($meta));
    }
}
