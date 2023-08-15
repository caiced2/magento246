<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\SalesRule;

use Magento\Reward\Model\SalesRule\RewardPointCounter;
use Magento\SalesRule\Api\Data\RuleExtensionInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RewardPointCounterTest extends TestCase
{
    /** @var RewardPointCounter */
    private $model;

    /** @var RuleRepositoryInterface|MockObject */
    private $ruleRepositoryMock;

    protected function setUp(): void
    {
        $this->ruleRepositoryMock = $this->getMockBuilder(RuleRepositoryInterface::class)
            ->getMock();

        $this->model = new RewardPointCounter(
            $this->ruleRepositoryMock
        );
    }

    public function testGetPointsForRules()
    {
        $ruleIds = [1, 2, 3, 4];

        /** @var RuleExtensionInterface|MockObject $attributesOneMock */
        $attributesOneMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesOneMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(12);

        /** @var RuleExtensionInterface|MockObject $attributesTwoMock */
        $attributesTwoMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesTwoMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(21);

        /** @var RuleExtensionInterface|MockObject $attributesThreeMock */
        $attributesThreeMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesThreeMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(null);

        /** @var RuleInterface|MockObject $ruleOneMock */
        $ruleOneMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleOneMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn($attributesOneMock);

        /** @var RuleInterface|MockObject $ruleTwoMock */
        $ruleTwoMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleTwoMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn($attributesTwoMock);

        /** @var RuleInterface|MockObject $ruleThreeMock */
        $ruleThreeMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleThreeMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn($attributesThreeMock);

        /** @var RuleInterface|MockObject $ruleFourMock */
        $ruleFourMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleFourMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn(null);

        $this->ruleRepositoryMock->expects(self::any())
            ->method('getById')
            ->willReturnMap([
                [1, $ruleOneMock],
                [2, $ruleTwoMock],
                [3, $ruleThreeMock],
                [4, $ruleFourMock],
            ]);

        $this->assertEquals(33, $this->model->getPointsForRules($ruleIds));
    }

    public function testGetPointsForRulesWithoutIds()
    {
        $this->assertEquals(0, $this->model->getPointsForRules([]));
    }
}
