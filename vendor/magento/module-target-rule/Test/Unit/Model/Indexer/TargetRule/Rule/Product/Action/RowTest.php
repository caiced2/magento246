<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Rule\Product\Action;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row;
use Magento\TargetRule\Model\ResourceModel\Index;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
{
    /**
     * Tested model
     *
     * @var Row
     */
    protected $model;

    /**
     * Product factory mock
     *
     * @var \Magento\Catalog\Model\ProductFactory|MockObject
     */
    protected $productFactoryMock;

    /**
     * Rule Factory mock
     *
     * @var \Magento\TargetRule\Model\RuleFactory|MockObject
     */
    protected $ruleFactoryMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->ruleFactoryMock = $this->createPartialMock(\Magento\TargetRule\Model\RuleFactory::class, ['create']);
        $this->model = $objectManager->getObject(
            Row::class,
            [
                'productFactory' => $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']),
                'ruleFactory' => $this->ruleFactoryMock,
                'ruleCollectionFactory' => $this->createPartialMock(
                    \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
                    ['create']
                ),
                'resource' => $this->createMock(Index::class),
                'storeManager' => $this->getMockForAbstractClass(
                    StoreManagerInterface::class,
                    [],
                    '',
                    false
                ),
                'localeDate' => $this->getMockForAbstractClass(
                    TimezoneInterface::class,
                    [],
                    '',
                    false
                ),
            ]
        );
    }

    /**
     * Test for exec with empty IDs
     */
    public function testEmptyId()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('We can\'t rebuild the index for an undefined product.');
        $this->model->execute(null);
    }

    public function testCleanProductPagesCache()
    {
        $ruleId = 1;
        $oldProductIds = [1, 2];
        $newProductIds = [2, 3];
        $productsToClean = array_unique(array_merge($oldProductIds, $newProductIds));
        $rule = $this->createPartialMock(
            Rule::class,
            ['load', 'getResource', 'getMatchingProductIds', 'getId', '__sleep', '__wakeup']
        );
        $rule->expects($this->once())->method('load')->with($ruleId);
        $ruleResource = $this->createPartialMock(\Magento\TargetRule\Model\ResourceModel\Rule::class, [
            '__sleep',
            '__wakeup',
            'getAssociatedEntityIds',
            'unbindRuleFromEntity',
            'bindRuleToEntity',
            'cleanCachedDataByProductIds'
        ]);
        $ruleResource->expects($this->once())
            ->method('getAssociatedEntityIds')
            ->with($ruleId, 'product')
            ->willReturn($oldProductIds);

        $ruleResource->expects($this->once())
            ->method('unbindRuleFromEntity')
            ->with($ruleId, [], 'product');

        $ruleResource->expects($this->once())
            ->method('bindRuleToEntity')
            ->with($ruleId, $newProductIds, 'product');

        $ruleResource->expects($this->once())
            ->method('cleanCachedDataByProductIds')
            ->with($productsToClean);

        $rule->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $rule->expects($this->once())
            ->method('getMatchingProductIds')
            ->willReturn($newProductIds);

        $rule->expects($this->once())
            ->method('getResource')
            ->willReturn($ruleResource);

        $this->ruleFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rule);

        $this->model->execute($ruleId);
    }
}
