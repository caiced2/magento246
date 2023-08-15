<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Delete;

use Magento\Framework\Message\ManagerInterface;
use Magento\Staging\Model\Entity\RemoverInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Staging\Model\Entity\Update\Action\Delete\RemoveAction;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveActionTest extends TestCase
{
    /**
     * @var RemoveAction
     */
    protected $removeAction;

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
     * @var RemoverInterface|MockObject
     */
    protected $remover;

    /**
     * @var MockObject
     */
    private $update;

    /**
     * @var EntityStaging|MockObject
     */
    private $entityStaging;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
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
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->removeAction = new RemoveAction(
            $this->versionManager,
            $this->messageManager,
            $this->entityStaging,
            $this->retriever,
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
        $this->removeAction->execute([]);
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $params = [
            'updateId' => 1,
            'entityId' => 4
        ];
        $versionId = 1;
        $this->versionManager
            ->method('setCurrentVersionId')
            ->with(1);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->update->expects($this->any())
            ->method('getId')
            ->willReturn($versionId);
        $entity = new \stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(4)
            ->willReturn($entity);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You removed this %1 from the update.', 'entity name'));
        $this->entityStaging->expects($this->once())
            ->method('unschedule')
            ->with($entity, $versionId)
            ->willReturn(true);
        $this->assertTrue($this->removeAction->execute($params));
    }
}
