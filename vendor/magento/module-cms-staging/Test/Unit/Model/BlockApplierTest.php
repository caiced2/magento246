<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model;

use Magento\Cms\Model\Block;
use Magento\CmsStaging\Model\BlockApplier;
use Magento\Framework\Indexer\CacheContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BlockApplierTest extends TestCase
{
    /**
     * @var CacheContext|MockObject
     */
    private $cacheContext;

    /**
     * @var BlockApplier|MockObject
     */
    private $blockApplier;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cacheContext = $this->createMock(CacheContext::class);
        $this->blockApplier = new BlockApplier($this->cacheContext);
    }

    /**
     * @return array
     */
    public function entityIdsDataProvider(): array
    {
        return [
            [[1, 2]],
            [[]],
        ];
    }

    /**
     * @dataProvider entityIdsDataProvider
     * @param array $entityIds
     */
    public function testRegisterCmsCacheTag(array $entityIds)
    {
        if (!empty($entityIds)) {
            $this->cacheContext->expects($this->once())
                ->method('registerEntities')
                ->with(Block::CACHE_TAG, $entityIds);
        } else {
            $this->cacheContext->expects($this->never())->method('registerEntities');
        }

        $this->blockApplier->execute($entityIds);
    }
}
