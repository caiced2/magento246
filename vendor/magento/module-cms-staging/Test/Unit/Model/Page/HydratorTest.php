<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model\Page;

use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Cms\Model\Page;
use Magento\CmsStaging\Model\Page\Hydrator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase
{
    /** @var Hydrator */
    protected $hydrator;

    /** @var Context|MockObject */
    protected $context;

    /** @var PostDataProcessor|MockObject */
    protected $postDataProcessor;

    /** @var ManagerInterface|MockObject */
    protected $eventManager;

    /** @var RetrieverInterface|MockObject */
    protected $entityRetriever;

    /** @var Page|MockObject */
    protected $page;

    /** @var RequestInterface|MockObject */
    protected $request;

    /** @var EntityMetadata|MockObject */
    protected $entityMetadata;

    /** @var MetadataPool|MockObject */
    protected $metadataPool;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->postDataProcessor = $this->getMockBuilder(
            PostDataProcessor::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->entityRetriever = $this->getMockBuilder(RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->page = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCreatedIn', 'getUpdatedIn', 'getData', 'setData'])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMetadata = $this->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new Hydrator(
            $this->context,
            $this->postDataProcessor,
            $this->entityRetriever,
            $this->metadataPool
        );
    }

    public function testHydrate()
    {
        $pageId = 1;
        $createdIn = 1000000001;
        $updatedIn = 1000000002;
        $linkField = 'row_id';
        $rowId = 1;
        $data = [
            'is_active' => true,
            'page_id' => $pageId,
            $linkField => $rowId,
            'created_in' => $createdIn,
            'updated_in' => $updatedIn,
        ];
        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->postDataProcessor->expects($this->once())
            ->method('filter')
            ->with($data)
            ->willReturn($data);
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($pageId)
            ->willReturn($this->page);
        $this->page->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'cms_page_prepare_save',
                ['page' => $this->page, 'request' => $this->request]
            );
        $this->page->expects($this->once())
            ->method('getCreatedIn')
            ->willReturn($createdIn);
        $this->page->expects($this->once())
            ->method('getUpdatedIn')
            ->willReturn($updatedIn);
        $this->page->expects($this->once())
            ->method('getData')
            ->with($linkField)
            ->willReturn($rowId);
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->entityMetadata);
        $this->entityMetadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);
        $this->postDataProcessor->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(true);
        $this->assertSame($this->page, $this->hydrator->hydrate($data));
    }

    public function testHydrateWithInvalidData()
    {
        $pageId = 1;
        $createdIn = 1000000001;
        $updatedIn = 1000000002;
        $linkField = 'row_id';
        $rowId = 1;
        $data = [
            'is_active' => 1,
            'page_id' => $pageId,
            $linkField => $rowId,
            'created_in' => $createdIn,
            'updated_in' => $updatedIn,
        ];
        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->postDataProcessor->expects($this->once())
            ->method('filter')
            ->with($data)
            ->willReturn($data);
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($pageId)
            ->willReturn($this->page);
        $this->page->expects($this->once())
            ->method('getCreatedIn')
            ->willReturn($createdIn);
        $this->page->expects($this->once())
            ->method('getUpdatedIn')
            ->willReturn($updatedIn);
        $this->page->expects($this->once())
            ->method('getData')
            ->with($linkField)
            ->willReturn($rowId);
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->entityMetadata);
        $this->entityMetadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn($linkField);
        $this->page->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'cms_page_prepare_save',
                ['page' => $this->page, 'request' => $this->request]
            );
        $this->postDataProcessor->expects($this->once())
            ->method('validate')
            ->with($data)
            ->willReturn(false);
        $this->assertFalse($this->hydrator->hydrate($data));
    }
}
