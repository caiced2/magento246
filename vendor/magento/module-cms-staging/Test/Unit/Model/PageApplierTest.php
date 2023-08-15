<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model;

use Magento\Cms\Model\Page;
use Magento\CmsStaging\Model\PageApplier;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PageApplierTest extends TestCase
{
    /** @var CacheContext|MockObject */
    protected $cacheContext;

    /** @var PageApplier|MockObject */
    protected $stagingApplier;

    protected function setUp(): void
    {
        $this->cacheContext = $this->getMockBuilder(CacheContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->stagingApplier = $objectManager->getObject(PageApplier::class, [
            "cacheContext" => $this->cacheContext
        ]);
    }

    public function getEntityIds()
    {
        return [
            [[1,2,3]],
            [[]]
        ];
    }

    /**
     * @dataProvider getEntityIds
     */
    public function testRegisterCmsCacheTag($entityIds)
    {
        if (!empty($entityIds)) {
            $this->cacheContext->expects($this->once())
                ->method("registerEntities")
                ->with(Page::CACHE_TAG, $entityIds);
        }

        $result = $this->stagingApplier->execute($entityIds);
        $this->assertNull($result);
    }
}
