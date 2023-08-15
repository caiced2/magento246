<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Save;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\Entity\Update\Action\Save\AssignAction;
use Magento\Staging\Model\Entity\Update\CampaignUpdater;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssignActionTest extends TestCase
{
    /** @var Service|MockObject */
    protected $updateService;

    /** @var VersionManager|MockObject */
    protected $versionManager;

    /** @var HydratorInterface|MockObject */
    protected $hydrator;

    /** @var Update|MockObject */
    protected $update;

    /** @var CampaignUpdater|MockObject */
    protected $campaignUpdater;

    /** @var AssignAction */
    protected $assignAction;

    /** @var EntityStaging|MockObject */
    private $entityStaging;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->updateService = $this->getMockBuilder(Service::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydrator = $this->getMockBuilder(HydratorInterface::class)
            ->getMockForAbstractClass();
        $this->update = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignUpdater = $this->getMockBuilder(CampaignUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assignAction = $objectManager->getObject(
            AssignAction::class,
            [
                'updateService' => $this->updateService,
                'versionManager' => $this->versionManager,
                'entityStaging' => $this->entityStaging,
                'entityHydrator' => $this->hydrator,
                'campaignUpdater' => $this->campaignUpdater
            ]
        );
    }

    public function testExecuteWithInvalidParams()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The required parameter is "stagingData". Set parameter and try again.');
        $this->assignAction->execute([]);
    }

    public function testExecuteReassign()
    {
        $params = [
            'stagingData' => [
                'update_id' => 100500
            ],
            'entityData' => [],
        ];

        $versionId = 32;
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with(['update_id' => 100500])
            ->willReturn($this->update);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->update->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($versionId);
        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($versionId);
        $entity = new \stdClass();
        $this->hydrator->expects($this->once())
            ->method('hydrate')
            ->with([])
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $versionId, ['copy_origin_in' => 100500])
            ->willReturn(true);
        $this->campaignUpdater->expects($this->once())
            ->method('updateCampaignStatus')
            ->with($this->update);

        $this->assertTrue($this->assignAction->execute($params));
    }

    public function testExecuteAssign()
    {
        $params = [
            'stagingData' => [
                'update_id' => ''
            ],
            'entityData' => [],
        ];

        $versionId = 32;
        $this->updateService->expects($this->once())
            ->method('assignUpdate')
            ->with(['update_id' => ''])
            ->willReturn($this->update);
        $this->versionManager->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->update);
        $this->update->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($versionId);
        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($versionId);
        $entity = new \stdClass();
        $this->hydrator->expects($this->once())
            ->method('hydrate')
            ->with([])
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $versionId, [])
            ->willReturn(true);
        $this->campaignUpdater->expects($this->once())
            ->method('updateCampaignStatus')
            ->with($this->update);

        $this->assertTrue($this->assignAction->execute($params));
    }
}
