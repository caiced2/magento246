<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Delete;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service;
use Magento\Staging\Model\Entity\Builder;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Staging\Model\Entity\Update\Action\Delete\AssignAction;
use Magento\Staging\Model\Entity\Update\CampaignUpdater;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AssignActionTest extends TestCase
{
    /** @var Service|MockObject */
    protected $updateService;

    /** @var VersionManager|MockObject */
    protected $versionManager;

    /** @var ManagerInterface|MockObject */
    protected $messageManager;

    /** @var RetrieverInterface|MockObject */
    protected $retriever;

    /** @var Update|MockObject */
    protected $update;

    /** @var CampaignUpdater|MockObject */
    protected $campaignUpdater;

    /** @var AssignAction */
    protected $assignAction;

    /** @var MockObject */
    protected $entityBuilder;

    /**
     * @var MockObject
     */
    private $entityHydratorMock;

    /** @var EntityStaging|MockObject */
    private $entityStaging;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->assignAction = $this->getMockBuilder(AssignAction::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->campaignUpdater = $this->getMockBuilder(CampaignUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityBuilder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityHydratorMock = $this->getMockForAbstractClass(HydratorInterface::class);
        $this->assignAction = $objectManager->getObject(
            AssignAction::class,
            [
                'updateService' => $this->updateService,
                'versionManager' => $this->versionManager,
                'entityStaging' => $this->entityStaging,
                'campaignUpdater' => $this->campaignUpdater,
                'messageManager' => $this->messageManager,
                'entityRetriever' => $this->retriever,
                'builder' => $this->entityBuilder,
                'entityName' => 'entity name'
            ]
        );
    }

    public function testExecuteWithInvalidParams()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The required parameter is "entityId". Set parameter and try again.');
        $this->assignAction->execute([]);
    }

    public function testExecute()
    {
        $params = [
            'updateId' => 1,
            'entityId' => 3,
            'stagingData' => [],
        ];
        $oldUpdateId = 32;
        $newUpdateId = 34;
        $this->versionManager->expects($this->exactly(2))
            ->method('setCurrentVersionId');
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $entity = new \stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(3)
            ->willReturn($entity);
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with([])
            ->willReturn($this->update);
        $this->update->expects($this->exactly(2))
            ->method('getId')
            ->willReturnMap(
                [
                    [$oldUpdateId],
                    [$newUpdateId],
                ]
            );
        $this->entityBuilder->expects($this->once())
            ->method('build')
            ->with($entity)
            ->willReturn($entity);
        $this->campaignUpdater->expects($this->once())
            ->method('updateCampaignStatus')
            ->with($this->update);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You removed this %1 from the update and saved it in the other one.', 'entity name'));
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->willReturn(true);

        $this->assertTrue($this->assignAction->execute($params));
    }

    public function testExecuteWithEntityData()
    {
        $params = [
            'updateId' => 1,
            'entityId' => 3,
            'stagingData' => [],
            'entityData' => [],
        ];
        $newUpdateId = 34;
        $this->versionManager->expects($this->exactly(2))
            ->method('setCurrentVersionId');
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $entity = new \stdClass();
        $this->retriever->expects($this->once())
            ->method('getEntity')
            ->with(3)
            ->willReturn($entity);
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with([])
            ->willReturn($this->update);
        $this->update->expects($this->exactly(1))
            ->method('getId')
            ->willReturnMap(
                [
                    [$newUpdateId]
                ]
            );
        $this->entityBuilder->expects($this->once())
            ->method('build')
            ->with($entity)
            ->willReturn($entity);
        $this->campaignUpdater->expects($this->never())
            ->method('updateCampaignStatus');
        $this->messageManager->expects($this->never())
            ->method('addSuccess');

        $this->assertTrue($this->assignAction->execute($params));
    }
}
