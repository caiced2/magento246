<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Model\Plugin;

use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRuleStaging\Model\Plugin\ConditionFieldsetIdResolver;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConditionFieldsetIdResolverTest extends TestCase
{
    /**
     * @var ConditionFieldsetIdResolver
     */
    protected $plugin;

    /**
     * @var MockObject
     */
    protected $metadataPoolMock;

    protected function setUp(): void
    {
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->plugin = new ConditionFieldsetIdResolver(
            $this->metadataPoolMock
        );
    }

    public function testAroundGetConditionsFieldSetId()
    {
        $result = 'result';
        $formName = 'form_name';
        $ruleMock = $this->createMock(Rule::class);
        $entityMetadataMock = $this->createMock(EntityMetadata::class);
        $proceed = function () use ($result) {
            return $result;
        };
        $this->metadataPoolMock
            ->expects($this->once())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($entityMetadataMock);
        $entityMetadataMock->expects($this->once())->method('getLinkField')->willReturn('rule_id');
        $ruleMock->expects($this->once())->method('getData')->with('rule_id')->willReturn(1);
        $this->assertEquals(
            $formName . 'rule_conditions_fieldset_1',
            $this->plugin->aroundGetConditionsFieldSetId($ruleMock, $proceed, $formName)
        );
    }
}
