<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\TargetRule;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\ObjectManagerInterface;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModelRule;
use Magento\TestFramework\App\ApiMutableScopeConfig;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes as RuleConditionAttributes;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes as ActionsConditionAttributes;
use Magento\TargetRule\Model\Actions\Condition\Combine;
use Magento\TargetRule\Test\Fixture\Action as RuleActionFixture;
use Magento\TargetRule\Test\Fixture\Actions as RuleActionsFixture;
use Magento\TargetRule\Test\Fixture\Condition as RuleConditionFixture;
use Magento\TargetRule\Test\Fixture\Conditions as RuleConditionsFixture;
use Magento\TargetRule\Test\Fixture\Rule as RuleFixture;

class RelatedProductRuleTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ResourceModelRule
     */
    private $resourceModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->resourceModel = $this->objectManager->get(ResourceModelRule::class);
    }

    /**
     * Checks if related products from target rule loaded
     *
     * @magentoDbIsolation disabled
     *
     * @magentoApiDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoApiDataFixture Magento/TargetRule/_files/related.php
     */
    public function testTargetRuleRelatedProduct()
    {
        $productSku = 'simple_product_1';

        $query = <<<QUERY
{
    products(filter: {sku: {eq: "{$productSku}"}})
    {
        items
        {
            related_products
            {
                sku
                name
                url_key
                description
                {
                    html
                }
                created_at
            }
        }
    }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $items = $response['products']['items'];
        $this->assertEquals(1, count($items));
        $this->assertEquals('simple_product_2', $items[0]['related_products'][0]['sku']);
        $this->assertEquals('simple-product-2-name', $items[0]['related_products'][0]['url_key']);
        $this->assertContains('Simple Product 2 Full Description', $items[0]['related_products'][0]['description']);
    }

    /**
     * Checks if up-sell products from target rule loaded
     *
     * @magentoDbIsolation disabled
     *
     * @magentoApiDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoApiDataFixture Magento/TargetRule/_files/upsell.php
     */
    public function testTargetRuleProductUpsell()
    {
        $productSku = 'simple_product_1';

        $query = <<<QUERY
{
    products(filter: {sku: {eq: "{$productSku}"}})
    {
        items {
            upsell_products
            {
                sku
                name
                url_key
                description
                {
                    html
                }
                created_at
            }
        }
    }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $items = $response['products']['items'];
        $this->assertEquals(1, count($items));
        $this->assertEquals('simple_product_2', $items[0]['upsell_products'][0]['sku']);
        $this->assertEquals('simple-product-2-name', $items[0]['upsell_products'][0]['url_key']);
        $this->assertContains('Simple Product 2 Full Description', $items[0]['upsell_products'][0]['description']);
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoApiDataFixture Magento/TargetRule/_files/products_with_attributes.php
     * @dataProvider rulesDataProvider
     *
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $attributeValue
     * @param array $productsSku
     *
     * @return void
     */
    public function testTargetRuleGetProductIds(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $attributeValue,
        array $productsSku
    ): void {
        $sku = 'simple1';

        $model = $this->createRuleModel($ruleType, $actionAttribute, $valueType, $attributeValue);
        $query = $this->getQuery($sku);
        $response = $this->graphQlQuery($query);

        $actualSkus = array_map(function ($product) {
            if (isset($product['sku'])) {
                return $product['sku'];
            }
        }, $response['products']['items'][0][$this->getLinkTypeKey($ruleType)]);
        $this->resourceModel->delete($model);
        $this->assertEquals(sort($productsSku), sort($actualSkus));
    }

    /**
     * Get GraphQl query
     *
     * @param int $sku
     * @return string
     */
    private function getQuery($sku): string
    {
        return <<<QUERY
{
    products(filter: {sku: {eq: "{$sku}"}})
    {
        items {
            crosssell_products
            {
                ...LinkedProduct
            }
            upsell_products
            {
                ...LinkedProduct
            }
            related_products
            {
                ...LinkedProduct
            }
        }
    }
}

fragment LinkedProduct on ProductInterface
{
    sku
    name
    url_key
    created_at

}
QUERY;
    }

    /**
     * Get type of product list
     *
     * @param $type
     * @return string
     */
    private function getLinkTypeKey($type): string
    {
        switch ($type) {
            case Rule::CROSS_SELLS:
                $key = 'crosssell_products';
                break;
            case Rule::UP_SELLS:
                $key = 'upsell_products';
                break;
            default:
                $key = 'related_products';
                break;
        }
        return $key;
    }

    /**
     * Generate target rule config data
     *
     * @return array
     */
    public function rulesDataProvider(): array
    {
        return [
            'related rule by the same category id' => [
                Rule::CROSS_SELLS,
                'category_ids',
                ActionsConditionAttributes::VALUE_TYPE_SAME_AS,
                '',
                ['simple3'],
            ],
            'cross sells rule by constant category ids' => [
                Rule::CROSS_SELLS,
                'category_ids',
                ActionsConditionAttributes::VALUE_TYPE_CONSTANT,
                '44',
                ['simple2', 'simple4'],
            ],
            'up sells rule by the same static attribute' => [
                Rule::UP_SELLS,
                'type_id',
                ActionsConditionAttributes::VALUE_TYPE_SAME_AS,
                '',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by constant promo attribute' => [
                Rule::RELATED_PRODUCTS,
                'promo_attribute',
                ActionsConditionAttributes::VALUE_TYPE_CONSTANT,
                'RELATED_PRODUCT',
                ['simple2', 'simple3', 'simple4'],
            ]
        ];
    }

    /**
     * Instantiate target rule model
     *
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $attributeValue
     *
     * @return Rule
     */
    private function createRuleModel(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $attributeValue
    ): Rule {
        /** @var Rule $model */
        $model = $this->objectManager->create(Rule::class);
        $model->setName('Test rule');
        $model->setSortOrder(0);
        $model->setIsActive(1);
        $model->setApplyTo($ruleType);

        $conditions = [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'conditions' => [],
        ];
        $conditions['conditions'][1] = [
            'type' => RuleConditionAttributes::class,
            'attribute' => 'category_ids',
            'operator' => '==',
            'value' => 33,
        ];
        $model->getConditions()->setConditions([])->loadArray($conditions);

        $actions = [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'actions' => [],
        ];
        $actions['actions'][1] = [
            'type' => ActionsConditionAttributes::class,
            'attribute' => $actionAttribute,
            'operator' => '==',
            'value_type' => $valueType,
            'value' => $attributeValue,
        ];
        $model->getActions()->setActions([])->loadArray($actions, 'actions');

        $this->resourceModel->save($model);

        return $model;
    }

    /**
     * @dataProvider configGetShowProductsDataProvider
     */
    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, ['product_links' => [['sku' => '$p1.sku$', 'type' => 'related']]], 'p2'),
        DataFixture(ProductFixture::class, as: 'p3'),
        DataFixture(ProductFixture::class, as: 'p4'),
        DataFixture(ProductFixture::class, as: 'p5'),
        DataFixture(ProductFixture::class, ['product_links' => [['sku' => '$p4.sku$', 'type' => 'upsell']]], 'p6'),
        DataFixture(ProductFixture::class, as: 'p7'),
        DataFixture(ProductFixture::class, as: 'p8'),
        DataFixture(ProductFixture::class, ['product_links' => [['sku' => '$p8.sku$', 'type' => 'crosssell']]], 'p9'),
        DataFixture(RuleConditionFixture::class, ['attribute' => 'sku', 'value' => '$p2.sku$',], 'rule1Condition'),
        DataFixture(RuleConditionsFixture::class, ['conditions' => ['$rule1Condition$']], 'rule1Conditions'),
        DataFixture(RuleActionFixture::class, ['attribute' => 'sku', 'value' => '$p3.sku$',], 'rule1Action'),
        DataFixture(RuleActionsFixture::class, ['conditions' => ['$rule1Action$']], 'rule1Actions'),
        DataFixture(
            RuleFixture::class,
            ['actions' => '$rule1Actions$', 'conditions' => '$rule1Conditions$', 'apply_to' => Rule::RELATED_PRODUCTS],
            'rule1'
        ),
        DataFixture(RuleConditionFixture::class, ['attribute' => 'sku', 'value' => '$p6.sku$',], 'rule2Condition'),
        DataFixture(RuleConditionsFixture::class, ['conditions' => ['$rule2Condition$']], 'rule2Conditions'),
        DataFixture(RuleActionFixture::class, ['attribute' => 'sku', 'value' => '$p5.sku$',], 'rule2Action'),
        DataFixture(RuleActionsFixture::class, ['conditions' => ['$rule2Action$']], 'rule2Actions'),
        DataFixture(
            RuleFixture::class,
            ['actions' => '$rule2Actions$', 'conditions' => '$rule2Conditions$', 'apply_to' => Rule::UP_SELLS],
            'rule2'
        ),
        DataFixture(RuleConditionFixture::class, ['attribute' => 'sku', 'value' => '$p9.sku$',], 'rule3Condition'),
        DataFixture(RuleConditionsFixture::class, ['conditions' => ['$rule3Condition$']], 'rule3Conditions'),
        DataFixture(RuleActionFixture::class, ['attribute' => 'sku', 'value' => '$p7.sku$',], 'rule3Action'),
        DataFixture(RuleActionsFixture::class, ['conditions' => ['$rule3Action$']], 'rule3Actions'),
        DataFixture(
            RuleFixture::class,
            ['actions' => '$rule3Actions$', 'conditions' => '$rule3Conditions$', 'apply_to' => Rule::CROSS_SELLS],
            'rule3'
        ),
    ]
    public function testConfigGetShowProducts(
        string $productName,
        array $relatedProducts,
        array $upsellProducts,
        array $crosssellProducts,
        array $config
    ): void {
        $fixtures = DataFixtureStorageManager::getStorage();
        $expected = [
            'related_products' => $relatedProducts,
            'upsell_products' => $upsellProducts,
            'crosssell_products' => $crosssellProducts,
        ];
        $scopeConfig = $this->objectManager->get(ApiMutableScopeConfig::class);
        foreach ($config as $key => $value) {
            $scopeConfig->setValue($key, (string) $value);
        }
        $sku = $fixtures->get($productName)->getSku();
        $query = $this->getQuery($sku);
        $response = $this->graphQlQuery($query);
        $actual = [];
        foreach (array_keys($expected) as $linkType) {
            $expected[$linkType] = array_map(
                function (string $productName) use ($fixtures) {
                    return $fixtures->get($productName)->getSku();
                },
                $expected[$linkType]
            );
            $actual[$linkType] = array_column($response['products']['items'][0][$linkType], 'sku');
            sort($expected[$linkType]);
            sort($actual[$linkType]);
        }

        $this->assertEquals($expected, $actual);
        $scopeConfig = $this->objectManager->get(ApiMutableScopeConfig::class);

        // reset config
        foreach ($config as $key => $value) {
            $scopeConfig->setValue($key, (string) Rule::BOTH_SELECTED_AND_RULE_BASED);
        }
    }

    /**
     * @return array
     */
    public function configGetShowProductsDataProvider(): array
    {
        return [
            [
                'productName' => 'p2',
                'related_products' => ['p1', 'p3'],
                'upsell_products' => [],
                'crosssell_products' => [],
                'config' => [
                    'catalog/magento_targetrule/related_position_behavior' => Rule::BOTH_SELECTED_AND_RULE_BASED
                ]
            ],
            [
                'productName' => 'p2',
                'related_products' => ['p1'],
                'upsell_products' => [],
                'crosssell_products' => [],
                'config' => [
                    'catalog/magento_targetrule/related_position_behavior' => Rule::SELECTED_ONLY
                ]
            ],
            [
                'productName' => 'p2',
                'related_products' => ['p3'],
                'upsell_products' => [],
                'crosssell_products' => [],
                'config' => [
                    'catalog/magento_targetrule/related_position_behavior' => Rule::RULE_BASED_ONLY
                ]
            ],
            [
                'productName' => 'p6',
                'related_products' => [],
                'upsell_products' => ['p4', 'p5'],
                'crosssell_products' => [],
                'config' => [
                    'catalog/magento_targetrule/upsell_position_behavior' => Rule::BOTH_SELECTED_AND_RULE_BASED
                ]
            ],
            [
                'productName' => 'p6',
                'related_products' => [],
                'upsell_products' => ['p4'],
                'crosssell_products' => [],
                'config' => [
                    'catalog/magento_targetrule/upsell_position_behavior' => Rule::SELECTED_ONLY
                ]
            ],
            [
                'productName' => 'p6',
                'related_products' => [],
                'upsell_products' => ['p5'],
                'crosssell_products' => [],
                'config' => [
                    'catalog/magento_targetrule/upsell_position_behavior' => Rule::RULE_BASED_ONLY
                ]
            ],
            [
                'productName' => 'p9',
                'related_products' => [],
                'upsell_products' => [],
                'crosssell_products' => ['p8', 'p7'],
                'config' => [
                    'catalog/magento_targetrule/crosssell_position_behavior' => Rule::BOTH_SELECTED_AND_RULE_BASED
                ]
            ],
            [
                'productName' => 'p9',
                'related_products' => [],
                'upsell_products' => [],
                'crosssell_products' => ['p8'],
                'config' => [
                    'catalog/magento_targetrule/crosssell_position_behavior' => Rule::SELECTED_ONLY
                ]
            ],
            [
                'productName' => 'p9',
                'related_products' => [],
                'upsell_products' => [],
                'crosssell_products' => ['p7'],
                'config' => [
                    'catalog/magento_targetrule/crosssell_position_behavior' => Rule::RULE_BASED_ONLY
                ]
            ]
        ];
    }
}
