<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Indexer\State;
use Magento\TargetRule\Model\Indexer\TargetRule\AbstractProcessor;
use Magento\TargetRule\Model\Indexer\TargetRule\Status\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractProcessorTest extends TestCase
{
    /**
     * @var AbstractProcessor
     */
    protected $_processor;

    /**
     * @var Container|MockObject
     */
    protected $_statusContainer;

    /**
     * @var Indexer|MockObject
     */
    protected $_indexer;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var State|MockObject
     */
    protected $_state;

    protected function setUp(): void
    {
        $this->_indexer = $this->createPartialMock(Indexer::class, ['getState', 'load']);

        $this->_statusContainer = $this->createPartialMock(
            Container::class,
            ['setFullReindexPassed', 'isFullReindexPassed']
        );

        $this->indexerRegistryMock = $this->createPartialMock(
            IndexerRegistry::class,
            ['get']
        );

        $this->_processor = $this->getMockForAbstractClass(
            AbstractProcessor::class,
            [$this->indexerRegistryMock, $this->_statusContainer]
        );
    }

    public function testIsFullReindexPassed()
    {
        $this->_statusContainer->expects($this->once())
            ->method('isFullReindexPassed')
            ->with($this->_processor->getIndexerId());
        $this->_processor->isFullReindexPassed();
    }

    public function testSetFullReindexPassed()
    {
        $this->_state = $this->createPartialMock(
            State::class,
            ['setStatus', 'save', '__sleep', '__wakeup']
        );

        $this->_state->expects($this->once())
            ->method('setStatus')
            ->with(StateInterface::STATUS_VALID)->willReturnSelf();

        $this->_state->expects($this->once())
            ->method('save');

        $this->_statusContainer->expects($this->once())
            ->method('setFullReindexPassed')
            ->with($this->_processor->getIndexerId());

        $this->_indexer->expects($this->once())
            ->method('getState')
            ->willReturn($this->_state);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with('')
            ->willReturn($this->_indexer);

        $this->_processor->setFullReindexPassed();
    }
}
