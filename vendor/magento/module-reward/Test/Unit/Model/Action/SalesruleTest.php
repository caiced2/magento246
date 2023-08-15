<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Reward\Model\Action\Salesrule;
use Magento\Reward\Model\ResourceModel\RewardFactory;
use Magento\Reward\Model\SalesRule\RewardPointCounter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesruleTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var Salesrule
     */
    protected $model;

    /**
     * @var RewardPointCounter|MockObject
     */
    private $rewardPointCounterMock;

    protected function setUp(): void
    {
        $this->rewardFactoryMock =
            $this->createMock(RewardFactory::class);
        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            Salesrule::class,
            [
                'rewardData' => $this->rewardFactoryMock,
                'rewardPointCounter' => $this->rewardPointCounterMock,
            ]
        );
    }

    public function testCanAddRewardPoints()
    {
        $this->assertTrue($this->model->canAddRewardPoints());
    }

    /**
     * @param array $args
     * @param string $expectedResult
     *
     * @dataProvider getHistoryMessageDataProvider
     */
    public function testGetHistoryMessage(array $args, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->model->getHistoryMessage($args));
    }

    /**
     * @return array
     */
    public function getHistoryMessageDataProvider()
    {
        return [
            [
                'args' => [],
                'expectedResult' => 'Earned promotion extra points from order #',
            ],
            [
                'args' => ['increment_id' => 1],
                'expectedResult' => 'Earned promotion extra points from order #1'
            ]
        ];
    }

    public function testGetPoints()
    {
        $appliedIds = '1,2,1,1,3,4,3';

        /** @var Quote|MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAppliedRuleIds'])
            ->getMock();
        $quoteMock->expects(self::any())
            ->method('getAppliedRuleIds')
            ->willReturn($appliedIds);

        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1',
                    1 => '2',
                    4 => '3',
                    5 => '4',
                ]
            )
            ->willReturn(33);

        $this->model->setQuote($quoteMock);

        $this->assertEquals(33, $this->model->getPoints(1));
    }

    public function testGetPointsWithoutIds()
    {
        /** @var Quote|MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAppliedRuleIds'])
            ->getMock();
        $quoteMock->expects(self::any())
            ->method('getAppliedRuleIds')
            ->willReturn('');

        $this->model->setQuote($quoteMock);

        $this->assertEquals(0, $this->model->getPoints(1));
    }

    public function testGetPointsWithoutQuote()
    {
        $this->assertEquals(0, $this->model->getPoints(1));
    }
}
