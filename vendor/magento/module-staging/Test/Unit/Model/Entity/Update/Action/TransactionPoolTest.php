<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\Entity\Update\Action\TransactionExecutorFactory;
use Magento\Staging\Model\Entity\Update\Action\TransactionExecutorInterface;
use Magento\Staging\Model\Entity\Update\Action\TransactionPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionPoolTest extends TestCase
{
    /** @var MockObject */
    private $transactionFactory;

    /**
     * @var TransactionPool
     */
    private $transactionPool;

    protected function setUp(): void
    {
        $transactionFactory = TransactionExecutorFactory::class;
        $this->transactionFactory = $this->getMockBuilder($transactionFactory)
            ->disableOriginalConstructor()
            ->getMock();
        $poolData = ['item1' => 'ActionObject'];
        $objectManager = new ObjectManager($this);
        $this->transactionPool = $objectManager->getObject(
            TransactionPool::class,
            [
                'transactionExecutorFactory' => $this->transactionFactory,
                'transactionPool' => $poolData
            ]
        );
    }

    public function testGetExecutor()
    {
        $namespace = 'ActionObject';
        $executor = TransactionExecutorInterface::class;
        $transactionExecutor = $this->getMockBuilder($executor)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionFactory->expects($this->once())
            ->method('create')
            ->willReturn($transactionExecutor);
        $this->assertInstanceOf($executor, $this->transactionPool->getExecutor($namespace));
    }
}
