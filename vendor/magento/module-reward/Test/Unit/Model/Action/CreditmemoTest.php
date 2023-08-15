<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\Action\Creditmemo;
use PHPUnit\Framework\TestCase;

class CreditmemoTest extends TestCase
{
    /**
     * @var Creditmemo
     */
    protected $model;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(Creditmemo::class);
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
                'expectedResult' => 'Refunded from order #',
            ],
            [
                'args' => ['increment_id' => 1],
                'expectedResult' => 'Refunded from order #1'
            ]
        ];
    }
}
