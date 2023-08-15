<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TargetRule\Model;

use Magento\Catalog\Test\Fixture\MultiselectAttribute as MultiselectAttributeFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Store\Model\Store;
use Magento\TargetRule\Test\Fixture\Action as RuleActionFixture;
use Magento\TargetRule\Test\Fixture\Actions as RuleActionsFixture;
use Magento\TargetRule\Test\Fixture\Rule as RuleFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;

/**
 * Test for Magento\TargetRule\Model\Index
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Rule
     */
    private $resourceModel;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->resourceModel = $this->objectManager->get(\Magento\TargetRule\Model\ResourceModel\Rule::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/TargetRule/_files/products_with_attributes.php
     * @dataProvider rulesDataProvider
     *
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $operator
     * @param string $attributeValue
     * @param array $productsSku
     *
     * @return void
     */
    public function testGetProductIds(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $operator,
        string $attributeValue,
        array $productsSku
    ): void {
        /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
        $productRepository = $this->objectManager->create(\Magento\Catalog\Model\ProductRepository::class);
        $product = $productRepository->get('simple1');

        $model = $this->createRuleModel($ruleType, $actionAttribute, $valueType, $operator, $attributeValue);
        /** @var \Magento\TargetRule\Model\Index $index */
        $index = $this->objectManager->create(\Magento\TargetRule\Model\Index::class)
            ->setType($ruleType)
            ->setProduct($product);
        $productIds = array_map(
            'intval',
            array_keys($index->getProductIds())
        );
        sort($productIds);
        $this->resourceModel->delete($model);

        $expectedProductIds = [];
        foreach ($productsSku as $sku) {
            $expectedProductIds[] = (int) $productRepository->get($sku)->getId();
        }
        sort($expectedProductIds);
        $this->assertEquals($expectedProductIds, $productIds);
    }

    /**
     * @return array
     */
    public function rulesDataProvider(): array
    {
        return [
            'cross sells rule by the same global attribute' => [
                \Magento\TargetRule\Model\Rule::CROSS_SELLS,
                'global_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple2', 'simple3', 'simple4'],
            ],
            'related rule by the same category id' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'category_ids',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple3'],
            ],
            'up sells rule by child of category ids' => [
                \Magento\TargetRule\Model\Rule::UP_SELLS,
                'category_ids',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CHILD_OF,
                '==',
                '',
                ['child_simple'],
            ],
            'cross sells rule by constant category ids' => [
                \Magento\TargetRule\Model\Rule::CROSS_SELLS,
                'category_ids',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '==',
                '44',
                ['simple2', 'simple4'],
            ],
            'up sells rule by the same static attribute' => [
                \Magento\TargetRule\Model\Rule::UP_SELLS,
                'type_id',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_SAME_AS,
                '==',
                '',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by constant promo attribute' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'promo_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '==',
                'RELATED_PRODUCT',
                ['simple2', 'simple3', 'simple4'],
            ],
            'related rule by attribute where value is equal to multiple values' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'promo_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '==',
                'RELATED_PRODUCT,ANOTHER_PRODUCT',
                [],
            ],
            'related rule by scoped attribute where value is one of' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'promo_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '()',
                'RELATED_PRODUCT,ANOTHER_PRODUCT',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by global attribute where value is one of' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'global_attribute',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '()',
                '666,777',
                ['simple2', 'simple3', 'simple4', 'child_simple'],
            ],
            'related rule by static attribute where value is one of' => [
                \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
                'sku',
                \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_CONSTANT,
                '()',
                'simple2,child_simple',
                ['simple2', 'child_simple'],
            ],
        ];
    }

    #[
        DbIsolation(false),
        DataFixture(
            MultiselectAttributeFixture::class,
            [
                'attribute_code' => 'product_multiselect_attribute',
                'options' => ['option_1', 'option_2', 'option_3', 'option_4', 'option_5']
            ],
            'attr'
        ),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(ProductFixture::class, as: 'product3'),
        DataFixture(ProductFixture::class, as: 'product4'),
        DataFixture(ProductFixture::class, as: 'product5'),
        DataFixture(ProductFixture::class, as: 'product6'),
        DataFixture(
            RuleActionFixture::class,
            [
                'attribute' => '$attr.attribute_code$',
                'value' => ['$attr.option_1$','$attr.option_4$'],
                'operator' => '()'
            ],
            'action'
        ),
        DataFixture(
            RuleActionsFixture::class,
            ['conditions' => ['$action$']],
            'actions'
        ),
        DataFixture(
            RuleFixture::class,
            [
                'actions' => '$actions$'
            ],
            'rule'
        ),
    ]
    public function testConditionWithMultiselectAndConstant(): void
    {
        $this->assertMatchingProducts(
            'product6',
            ['product1', 'product3', 'product5'],
            [
                'product1' => ['option_1'],
                'product2' => ['option_2'],
                'product3' => ['option_1', 'option_2'],
                'product5' => ['option_4'],
            ],
        );
    }

    #[
        DbIsolation(false),
        DataFixture(
            MultiselectAttributeFixture::class,
            [
                'attribute_code' => 'product_multiselect_attribute',
                'options' => ['option_1', 'option_2', 'option_3', 'option_4', 'option_5']
            ],
            'attr'
        ),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(ProductFixture::class, as: 'product3'),
        DataFixture(ProductFixture::class, as: 'product4'),
        DataFixture(ProductFixture::class, as: 'product5'),
        DataFixture(ProductFixture::class, as: 'product6'),
        DataFixture(
            RuleActionFixture::class,
            [
                'attribute' => '$attr.attribute_code$',
                'operator' => '()',
                'value_type' => \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::VALUE_TYPE_SAME_AS
            ],
            'action'
        ),
        DataFixture(
            RuleActionsFixture::class,
            ['conditions' => ['$action$']],
            'actions'
        ),
        DataFixture(
            RuleFixture::class,
            [
                'actions' => '$actions$'
            ],
            'rule'
        ),
    ]
    public function testConditionWithMultiselectAndSameAs(): void
    {
        $this->assertMatchingProducts(
            'product3',
            ['product1', 'product2'],
            [
                'product1' => ['option_1'],
                'product2' => ['option_2'],
                'product3' => ['option_1', 'option_2'],
                'product5' => ['option_4'],
            ],
        );
    }

    /**
     * @param string $targetProduct
     * @param array $expectedProducts
     * @param array $productsConfiguration
     * @return void
     */
    private function assertMatchingProducts(
        string $targetProduct,
        array $expectedProducts,
        array $productsConfiguration
    ): void {
        /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
        $productRepository = $this->objectManager->get(\Magento\Catalog\Model\ProductRepository::class);
        /** @var \Magento\TargetRule\Model\Index $index */
        $index = $this->objectManager->create(\Magento\TargetRule\Model\Index::class)
            ->setType(\Magento\TargetRule\Model\Rule::RELATED_PRODUCTS);

        $multiselect = $this->fixtures->get('attr');
        $attributeCode = $multiselect->getAttributeCode();
        // set multiselect attribute
        foreach ($productsConfiguration as $fixture => $value) {
            $id = (int) $this->fixtures->get($fixture)->getId();
            $product = $productRepository->getById($id, true, Store::DEFAULT_STORE_ID, true);
            $product->setData($attributeCode, implode(',', array_map([$multiselect, 'getData'], $value)));
            $productRepository->save($product);
        }

        $targetProductId = (int) $this->fixtures->get($targetProduct)->getId();
        $product = $productRepository->getById($targetProductId, true, Store::DEFAULT_STORE_ID, true);
        $index->setProduct($product);

        $expectedProductIds = [];
        foreach ($expectedProducts as $fixture) {
            $expectedProductIds[] = (int) $this->fixtures->get($fixture)->getId();
        }

        $this->assertEqualsCanonicalizing($expectedProductIds, array_keys($index->getProductIds()));
    }
    /**
     * @param int $ruleType
     * @param string $actionAttribute
     * @param string $valueType
     * @param string $operator
     * @param string $attributeValue
     * @return \Magento\TargetRule\Model\Rule
     */
    private function createRuleModel(
        int $ruleType,
        string $actionAttribute,
        string $valueType,
        string $operator,
        string $attributeValue
    ): \Magento\TargetRule\Model\Rule {
        /** @var \Magento\TargetRule\Model\Rule $model */
        $model = $this->objectManager->create(\Magento\TargetRule\Model\Rule::class);
        $model->setName('Test rule');
        $model->setSortOrder(0);
        $model->setIsActive(1);
        $model->setApplyTo($ruleType);

        $conditions = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'conditions' => [],
        ];
        $conditions['conditions'][1] = [
            'type' => \Magento\TargetRule\Model\Rule\Condition\Product\Attributes::class,
            'attribute' => 'category_ids',
            'operator' => '==',
            'value' => 33,
        ];
        $model->getConditions()->setConditions([])->loadArray($conditions);

        $actions = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Combine::class,
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
            'actions' => [],
        ];
        $actions['actions'][1] = [
            'type' => \Magento\TargetRule\Model\Actions\Condition\Product\Attributes::class,
            'attribute' => $actionAttribute,
            'operator' => $operator,
            'value_type' => $valueType,
            'value' => $attributeValue,
        ];
        $model->getActions()->setActions([])->loadArray($actions, 'actions');

        $this->resourceModel->save($model);

        return $model;
    }
}
