<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Action\Creditmemo;

use Magento\Reward\Model\Action\Creditmemo\VoidAction;
use PHPUnit\Framework\TestCase;

class VoidActionTest extends TestCase
{
    /**
     * @var VoidAction
     */
    protected $model;

    protected function setUp(): void
    {
        $this->model = new VoidAction();
    }

    /**
     * @param array $args
     * @param string $expectedResult
     *
     * @dataProvider getHistoryMessageDataProvider
     * @covers \Magento\Reward\Model\Action\Creditmemo\VoidAction::getHistoryMessage
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
                'expectedResult' => 'Points voided at order # refund.',
            ],
            [
                'args' => ['increment_id' => 1],
                'expectedResult' => 'Points voided at order #1 refund.'
            ]
        ];
    }
}
