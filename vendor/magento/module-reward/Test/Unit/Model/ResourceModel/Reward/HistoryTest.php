<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\ResourceModel\Reward;

use Magento\Reward\Model\ResourceModel\Reward\History;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;

class HistoryTest extends TestCase
{
    /**
     * @var History
     */
    protected $_model;

    /**
     * DB Connection
     *
     * @var AdapterInterface
     */
    protected $connection;

    protected function setUp(): void
    {
        $this->_model = $this->createPartialMock(History::class, ['getConnection']);
        $this->connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIsExistHistoryUpdate()
    {
        $this->_model->expects($this->never())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->assertEquals(false, $this->_model->isExistHistoryUpdate(1, 1, 1, null));
    }
}
