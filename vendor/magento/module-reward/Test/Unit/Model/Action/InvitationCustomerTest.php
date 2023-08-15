<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Action\InvitationCustomer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvitationCustomerTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardDataMock;

    /**
     * @var InvitationCustomer
     */
    protected $model;

    protected function setUp(): void
    {
        $this->rewardDataMock = $this->createMock(Data::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            InvitationCustomer::class,
            ['rewardData' => $this->rewardDataMock]
        );
    }

    public function testGetPoints()
    {
        $websiteId = 100;
        $this->rewardDataMock->expects($this->once())
            ->method('getPointsConfig')
            ->with('invitation_customer', $websiteId)
            ->willReturn(500);
        $this->assertEquals(500, $this->model->getPoints($websiteId));
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
                'expectedResult' => 'The invitation to  converted into a customer.',
            ],
            [
                'args' => ['email' => 'test@mail.com'],
                'expectedResult' => 'The invitation to test@mail.com converted into a customer.'
            ]
        ];
    }
}
