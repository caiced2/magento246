<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Test\Unit\Generator;

use Magento\AdobeCommerceEventsGenerator\Generator\PluginConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PluginConverter
 */
class PluginConverterTest extends TestCase
{
    /**
     * @var array
     */
    private array $interfaces = [
        'Magento\Customer\Api\CustomerRepositoryInterface' => [
            ['name' => 'save'],
            ['name' => 'get'],
            ['name' => 'getById'],
            ['name' => 'getList'],
            ['name' => 'delete'],
            ['name' => 'deleteById'],
        ],
        'Magento\Sales\Api\CreditmemoRepositoryInterface' => [
            ['name' => 'save'],
            ['name' => 'get'],
            ['name' => 'getList'],
            ['name' => 'delete'],
            ['name' => 'deleteById'],
        ],
    ];

    /**
     * @var array
     */
    private array $resourceModels = [
        'Magento\Sales\Model\ResourceModel\Order\Tax' => [
            ['name' => 'afterSave', 'params' => ['some params']],
            ['name' => 'generate', 'params' => ['some params']],
        ],
        'Magento\Sales\Model\ResourceModel\Order' => [
            ['name' => 'afterDelete'],
        ],
        'Magento\Sales\Model\ResourceModel\Report\Order' => [
            ['name' => 'afterUpdate'],
        ]
    ];

    /**
     * Checks that interfaces list converts correctly to appropriate plugin list
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testConvertApiInterfaces(): void
    {
        $pluginConverter = new PluginConverter();

        self::assertEquals(
            [
                [
                    'class' => 'Magento\\AdobeCommerceEvents\\Plugin\\Customer\\Api\\CustomerRepositoryInterfacePlugin',
                    'namespace' => 'Magento\\AdobeCommerceEvents\\Plugin\\Customer\\Api',
                    'interface' => 'Magento\\Customer\\Api\\CustomerRepositoryInterface',
                    'interfaceShort' => 'CustomerRepositoryInterface',
                    'pluginName' => 'magento_customer_customerrepositoryinterface_plugin',
                    'name' => 'CustomerRepositoryInterfacePlugin',
                    'methods' => [
                        [
                            'name' => 'Save',
                            'nameLower' => 'save',
                            'eventCode' => 'magento.customer.api.customer_repository.save',
                            'params' => [],
                        ],
                        [
                            'name' => 'Get',
                            'nameLower' => 'get',
                            'eventCode' => 'magento.customer.api.customer_repository.get',
                            'params' => [],
                        ],
                        [
                            'name' => 'GetById',
                            'nameLower' => 'getById',
                            'eventCode' => 'magento.customer.api.customer_repository.get_by_id',
                            'params' => [],
                        ],
                        [
                            'name' => 'GetList',
                            'nameLower' => 'getList',
                            'eventCode' => 'magento.customer.api.customer_repository.get_list',
                            'params' => [],
                        ],
                        [
                            'name' => 'Delete',
                            'nameLower' => 'delete',
                            'eventCode' => 'magento.customer.api.customer_repository.delete',
                            'params' => [],
                        ],
                        [
                            'name' => 'DeleteById',
                            'nameLower' => 'deleteById',
                            'eventCode' => 'magento.customer.api.customer_repository.delete_by_id',
                            'params' => [],
                        ],
                    ],
                    'path' => '/Plugin/Customer/Api/CustomerRepositoryInterfacePlugin.php',
                    'type' => PluginConverter::TYPE_API_INTERFACE
                ],
                [
                    'class' => 'Magento\\AdobeCommerceEvents\\Plugin\\Sales\\Api\\CreditmemoRepositoryInterfacePlugin',
                    'namespace' => 'Magento\\AdobeCommerceEvents\\Plugin\\Sales\\Api',
                    'interface' => 'Magento\\Sales\\Api\\CreditmemoRepositoryInterface',
                    'interfaceShort' => 'CreditmemoRepositoryInterface',
                    'pluginName' => 'magento_sales_creditmemorepositoryinterface_plugin',
                    'name' => 'CreditmemoRepositoryInterfacePlugin',
                    'methods' => [
                        [
                            'name' => 'Save',
                            'nameLower' => 'save',
                            'eventCode' => 'magento.sales.api.creditmemo_repository.save',
                            'params' => [],
                        ],
                        [
                            'name' => 'Get',
                            'nameLower' => 'get',
                            'eventCode' => 'magento.sales.api.creditmemo_repository.get',
                            'params' => [],
                        ],
                        [
                            'name' => 'GetList',
                            'nameLower' => 'getList',
                            'eventCode' => 'magento.sales.api.creditmemo_repository.get_list',
                            'params' => [],
                        ],
                        [
                            'name' => 'Delete',
                            'nameLower' => 'delete',
                            'eventCode' => 'magento.sales.api.creditmemo_repository.delete',
                            'params' => [],
                        ],
                        [
                            'name' => 'DeleteById',
                            'nameLower' => 'deleteById',
                            'eventCode' => 'magento.sales.api.creditmemo_repository.delete_by_id',
                            'params' => [],
                        ],
                    ],
                    'path' => '/Plugin/Sales/Api/CreditmemoRepositoryInterfacePlugin.php',
                    'type' => PluginConverter::TYPE_API_INTERFACE
                ],
            ],
            $pluginConverter->convert($this->interfaces, PluginConverter::TYPE_API_INTERFACE)
        );
    }

    /**
     * Checks that resourceModels list converts correctly to appropriate plugin list
     *
     * @return void
     */
    public function testConvertResourceModels(): void
    {
        $pluginConverter = new PluginConverter();

        self::assertEquals(
            [
                [
                    'class' => 'Magento\AdobeCommerceEvents\Plugin\Sales\ResourceModel\Order\TaxPlugin',
                    'namespace' => 'Magento\AdobeCommerceEvents\Plugin\Sales\ResourceModel\Order',
                    'interface' => 'Magento\Sales\Model\ResourceModel\Order\Tax',
                    'interfaceShort' => 'Tax',
                    'pluginName' => 'magento_sales_tax_plugin',
                    'name' => 'TaxPlugin',
                    'methods' => [
                        [
                            'name' => 'AfterSave',
                            'nameLower' => 'afterSave',
                            'eventCode' => 'magento.sales.model.resource_model.order.tax.after_save',
                            'params' => ['some params'],
                        ],
                        [
                            'name' => 'Generate',
                            'nameLower' => 'generate',
                            'eventCode' => 'magento.sales.model.resource_model.order.tax.generate',
                            'params' => ['some params'],
                        ],
                    ],
                    'path' => '/Plugin/Sales/ResourceModel/Order/TaxPlugin.php',
                    'type' => 'ResourceModel',
                ],
                [
                    'class' => 'Magento\AdobeCommerceEvents\Plugin\Sales\ResourceModel\OrderPlugin',
                    'namespace' => 'Magento\AdobeCommerceEvents\Plugin\Sales\ResourceModel',
                    'interface' => 'Magento\Sales\Model\ResourceModel\Order',
                    'interfaceShort' => 'Order',
                    'pluginName' => 'magento_sales_order_plugin',
                    'name' => 'OrderPlugin',
                    'methods' => [
                        [
                            'name' => 'AfterDelete',
                            'nameLower' => 'afterDelete',
                            'eventCode' => 'magento.sales.model.resource_model.order.after_delete',
                            'params' => [],
                        ]
                    ],
                    'path' => '/Plugin/Sales/ResourceModel/OrderPlugin.php',
                    'type' => 'ResourceModel'
                ],
                [
                    'class' => 'Magento\AdobeCommerceEvents\Plugin\Sales\ResourceModel\Report\OrderPlugin',
                    'namespace' => 'Magento\AdobeCommerceEvents\Plugin\Sales\ResourceModel\Report',
                    'interface' => 'Magento\Sales\Model\ResourceModel\Report\Order',
                    'interfaceShort' => 'Order',
                    'pluginName' => 'magento_sales_order_plugin',
                    'name' => 'OrderPlugin',
                    'methods' => [
                        [
                            'name' => 'AfterUpdate',
                            'nameLower' => 'afterUpdate',
                            'eventCode' => 'magento.sales.model.resource_model.report.order.after_update',
                            'params' => [],
                        ]
                    ],
                    'path' => '/Plugin/Sales/ResourceModel/Report/OrderPlugin.php',
                    'type' => 'ResourceModel',
                ],
            ],
            $pluginConverter->convert($this->resourceModels, PluginConverter::TYPE_RESOURCE_MODEL)
        );
    }
}
