<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Delete;

use Magento\Framework\Message\ManagerInterface;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service;
use Magento\Staging\Model\Entity\Builder;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Staging\Model\Entity\Update\Action\Delete\SaveAction;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveActionTest extends TestCase
{
    /**
     * @var Service|MockObject
     */
    protected $updateService;

    /**
     * @var VersionManager|MockObject
     */
    protected $versionManager;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManager;

    /**
     * @var RetrieverInterface|MockObject
     */
    protected $retriever;

    /**
     * @var Update|MockObject
     */
    protected $update;

    /**
     * @var SaveAction
     */
    protected $saveAction;

    /**
     * @var MockObject
     */
    protected $entityBuilder;

    /**
     * @var MockObject
     */
    protected $intersectionMock;

    /**
     * @var EntityStaging|MockObject
     */
    private $entityStaging;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->updateService = $this->getMockBuilder(Service::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->retriever = $this->getMockBuilder(RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->update = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityBuilder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->saveAction = new SaveAction(
            $this->updateService,
            $this->versionManager,
            $this->retriever,
            $this->entityStaging,
            $this->messageManager,
            $this->entityBuilder,
            'entity name'
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithInvalidParams(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The required parameter is "entityId". Set parameter and try again.');
        $this->saveAction->execute([]);
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $params = [
            'updateId' => 1,
            'entityId' => 3,
            'stagingData' => []
        ];
        $currentVersionId = 1;
        $oldVersionId = 31;
        $newVersionId = 32;
        $this->versionManager
            ->method('setCurrentVersionId')
            ->withConsecutive([$currentVersionId]);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->updateService->expects($this->once())
            ->method('createUpdate')
            ->with([])
            ->willReturn($this->update);
        $this->update
            ->method('getId')
            ->willReturnOnConsecutiveCalls($oldVersionId, $newVersionId);
        $entity = new stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(3)
            ->willReturn($entity);
        $this->entityBuilder->expects($this->once())
            ->method('build')
            ->with($entity)
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $newVersionId)
            ->willReturn(true);
        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('You removed this %1 from the update and saved it in a new one.', 'entity name'));
        $this->assertTrue($this->saveAction->execute($params));
    }
}
