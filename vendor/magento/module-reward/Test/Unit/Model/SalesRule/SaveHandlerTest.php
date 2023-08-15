<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\SalesRule;

use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\ResourceModel\Reward;
use Magento\Reward\Model\SalesRule\SaveHandler;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveHandlerTest extends TestCase
{
    /** @var SaveHandler */
    private $model;

    /** @var Data|MockObject */
    private $rewardHelperMock;

    /** @var MetadataPool|MockObject */
    private $metadataPoolMock;

    /** @var Reward|MockObject */
    private $rewardMock;

    protected function setUp(): void
    {
        $this->rewardHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardMock = $this->getMockBuilder(Reward::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new SaveHandler(
            $this->rewardHelperMock,
            $this->metadataPoolMock,
            $this->rewardMock
        );
    }

    /**
     * @param array $attributes
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $attributes)
    {
        $linkField = 'link_field';
        $linkFieldValue = 'link_field_value';

        /** @var Rule|MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getData', 'getRewardPointsDelta'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $ruleMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($attributes);

        /** @var EntityMetadataInterface|MockObject $metadataMock */
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();

        $this->metadataPoolMock->expects(self::any())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects(self::any())
            ->method('getLinkField')
            ->willReturn($linkField);

        $ruleMock->expects(self::any())
            ->method('getData')
            ->with($linkField)
            ->willReturn($linkFieldValue);

        $this->rewardMock->expects(self::once())
            ->method('saveRewardSalesrule')
            ->with($linkFieldValue, (int)$attributes['reward_points_delta']);

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }

    public function executeDataProvider(): array
    {
        return [
            'attribute with points' => [
                [
                    'reward_points_delta' => '234',
                ]
            ],
            'attribute with empty points' => [
                [
                    'reward_points_delta' => '',
                ]
            ],
            'attribute with 0 points' => [
                [
                    'reward_points_delta' => '0',
                ]
            ],
        ];
    }

    public function testExecuteWithoutPointsInAttributes()
    {
        $points = '345';
        $linkField = 'link_field';
        $linkFieldValue = 'link_field_value';

        /** @var Rule|MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getData', 'getRewardPointsDelta'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $ruleMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn([]);

        /** @var EntityMetadataInterface|MockObject $metadataMock */
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();

        $this->metadataPoolMock->expects(self::any())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects(self::any())
            ->method('getLinkField')
            ->willReturn($linkField);

        $ruleMock->expects(self::any())
            ->method('getData')
            ->with($linkField)
            ->willReturn($linkFieldValue);

        $ruleMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn($points);

        $this->rewardMock->expects(self::once())
            ->method('saveRewardSalesrule')
            ->with($linkFieldValue, $points);

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }

    /**
     * @param mixed $points
     * @dataProvider executeWithoutPointsDataProvider
     */
    public function testExecuteWithoutPoints($points)
    {
        $linkField = 'link_field';
        $linkFieldValue = 'link_field_value';

        /** @var Rule|MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getData', 'getRewardPointsDelta'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $ruleMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn([]);

        /** @var EntityMetadataInterface|MockObject $metadataMock */
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();

        $this->metadataPoolMock->expects(self::any())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects(self::any())
            ->method('getLinkField')
            ->willReturn($linkField);

        $ruleMock->expects(self::any())
            ->method('getData')
            ->with($linkField)
            ->willReturn($linkFieldValue);

        $ruleMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn($points);

        $this->rewardMock->expects(self::never())
            ->method('saveRewardSalesrule');

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }

    /**
     * @return array
     */
    public function executeWithoutPointsDataProvider(): array
    {
        return [
            'null value' => [null],
        ];
    }

    public function testExecuteWithoutLinkFieldValue()
    {
        $linkField = 'link_field';

        /** @var Rule|MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getData'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $ruleMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn([]);

        /** @var EntityMetadataInterface|MockObject $metadataMock */
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();

        $this->metadataPoolMock->expects(self::any())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects(self::any())
            ->method('getLinkField')
            ->willReturn($linkField);

        $ruleMock->expects(self::any())
            ->method('getData')
            ->with($linkField)
            ->willReturn('');

        $this->rewardMock->expects(self::never())
            ->method('saveRewardSalesrule');

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }

    public function testExecuteWithDisabledRewards()
    {
        /** @var Rule|MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(false);

        $this->rewardMock->expects(self::never())
            ->method('saveRewardSalesrule');

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }
}
