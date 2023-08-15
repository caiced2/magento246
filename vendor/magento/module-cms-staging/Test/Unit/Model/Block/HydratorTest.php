<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model\Block;

use Magento\Cms\Model\Block;
use Magento\CmsStaging\Model\Block\Hydrator;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Message\ManagerInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase
{
    /** @var Hydrator */
    protected $hydrator;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var RetrieverInterface|MockObject */
    protected $entityRetriever;

    /** @var Block|MockObject */
    protected $block;

    /** @var EntityMetadata|MockObject */
    protected $entityMetadata;

    /** @var MetadataPool|MockObject */
    protected $metadataPool;

    protected function setUp(): void
    {
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->entityRetriever = $this->getMockBuilder(RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->block = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCreatedIn', 'getUpdatedIn', 'getData', 'setData'])
            ->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMetadata = $this->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new Hydrator($this->messageManager, $this->entityRetriever, $this->metadataPool);
    }

    public function testHydrate()
    {
        $blockId = 1;
        $createdIn = 1000000001;
        $updatedIn = 1000000002;
        $linkField = 'row_id';
        $rowId = 1;
        $data = [
            'is_active' => 1,
            'block_id' => $blockId,
            $linkField => $rowId,
            'created_in' => $createdIn,
            'updated_in' => $updatedIn,
        ];

        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($blockId)
            ->willReturn($this->block);
        $this->block->expects($this->once())
            ->method('getId')
            ->willReturn($blockId);
        $this->block->expects($this->once())
            ->method('getCreatedIn')
            ->willReturn($createdIn);
        $this->block->expects($this->once())
            ->method('getUpdatedIn')
            ->willReturn($updatedIn);
        $this->block->expects($this->once())
            ->method('getData')
            ->with($linkField)
            ->willReturn($rowId);
        $this->block->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->entityMetadata);
        $this->entityMetadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);

        $this->assertSame($this->block, $this->hydrator->hydrate($data));
    }

    public function testHydrateWithoutBlock()
    {
        $blockId = 1;
        $data = [
            'is_active' => 'true',
            'block_id' => $blockId
        ];

        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($blockId)
            ->willReturn(false);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__('This block no longer exists.'));
        $this->assertFalse($this->hydrator->hydrate($data));
    }
}
