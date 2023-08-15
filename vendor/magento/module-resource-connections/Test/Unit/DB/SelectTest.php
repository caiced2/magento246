<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ResourceConnections\Test\Unit\DB;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\ResourceConnections\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{
    /**
     * @var Select
     */
    protected $select;

    /**
     * @var Mysql|MockObject
     */
    protected $mysqlMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->mysqlMock = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(['setUseMasterConnection'])
            ->getMock();
        $selectRenderer = $this->createMock(SelectRenderer::class);
        $this->select = new Select(
            $this->mysqlMock,
            $selectRenderer
        );
    }

    /**
     * @return void
     */
    public function testForUpdate()
    {
        $this->mysqlMock->expects(
            $this->once()
        )->method(
            'setUseMasterConnection'
        );
        $this->select->forUpdate();
    }
}
