<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Actions\Condition;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\TargetRule\Model\Actions\Condition\Combine;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;
use Magento\TargetRule\Model\Actions\Condition\Product\Special\Price;
use Magento\TargetRule\Model\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombineTest extends TestCase
{
    /**
     * Combine model
     *
     * @var Combine
     */
    private $combine;

    /**
     * @var ProductCollection|MockObject
     */
    private $productCollectionMock;

    /**
     * @var Index
     */
    private $indexModel;

    /**
     * @var Price|MockObject
     */
    private $priceCondition;

    /**
     * @var Attributes
     */
    private $attributesCondition;

    /**
     * @var SqlBuilder
     */
    private $conditionSqlBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->productCollectionMock = $this->createMock(ProductCollection::class);
        $this->indexModel = $this->createMock(Index::class);
        $this->priceCondition = $this->createMock(Price::class);
        $this->attributesCondition = $this->createMock(Attributes::class);
        $this->conditionSqlBuilder = $this->createMock(SqlBuilder::class);

        $this->combine = (new ObjectManager($this))->getObject(
            Combine::class,
            [
                'context' => $this->createMock(Context::class),
                'conditionSqlBuilder' => $this->conditionSqlBuilder
            ]
        );
    }

    /**
     * Tests getConditionForCollection when one "Price Condition" is in "Combine".
     *
     * "Price Special Condition" has it's own implementation of getConditionForCollection() method, that's why resulting
     * will depends on what it will return.
     *
     * @return void
     */
    public function testGetConditionForCollectionWithSpecialPrice(): void
    {
        $this->priceCondition->method('getConditionForCollection')
            ->willReturn('(`price_index`.`min_price`=:targetrule_bind_0)');
        $bind = [];

        $this->combine->setConditions([$this->priceCondition]);
        $result = $this->combine->getConditionForCollection($this->productCollectionMock, $this->indexModel, $bind);

        $this->assertEquals('( (`price_index`.`min_price`=:targetrule_bind_0))', (string)$result);
    }

    /**
     * Tests getConditionForCollection with set of different conditions.
     *
     * Tests combination of "Special Price Condition" and "Attributes Condition" in "Combine"
     *
     * @return void
     */
    public function testGetConditionForCollectionWithSetOfConditions(): void
    {
        $this->priceCondition->method('getConditionForCollection')
            ->willReturn('prettyPriceResult');
        $this->conditionSqlBuilder->method('generateWhereClause')
            ->willReturn('prettyResultOfSQLBuilder');

        $bind = [];

        $this->combine->setConditions([$this->priceCondition, $this->attributesCondition]);
        $result = $this->combine->getConditionForCollection($this->productCollectionMock, $this->indexModel, $bind);

        $this->assertEquals('( prettyPriceResult AND  prettyResultOfSQLBuilder)', (string)$result);
    }
}
