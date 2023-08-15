<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Action;

use Magento\Reward\Model\Action\OrderRevert;
use PHPUnit\Framework\TestCase;

class OrderRevertTest extends TestCase
{
    /**
     * @var OrderRevert
     */
    protected $model;

    protected function setUp(): void
    {
        $this->model = new OrderRevert();
    }

    /**
     * @param array $args
     * @param string $expectedResult
     *
     * @dataProvider getHistoryMessageDataProvider
     * @covers \Magento\Reward\Model\Action\OrderRevert::getHistoryMessage
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
                'expectedResult' => 'Reverted from incomplete order #',
            ],
            [
                'args' => ['increment_id' => 1],
                'expectedResult' => 'Reverted from incomplete order #1'
            ]
        ];
    }
}
