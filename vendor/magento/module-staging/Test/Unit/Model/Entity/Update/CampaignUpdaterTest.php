<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Entity\Update\CampaignUpdater;
use Magento\Staging\Model\Update\Includes\Retriever as IncludesRetriever;
use Magento\Staging\Model\UpdateRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignUpdaterTest extends TestCase
{
    /**
     * @var IncludesRetriever|MockObject
     */
    protected $includesRetriever;

    /**
     * @var UpdateRepository|MockObject
     */
    protected $updateRepository;

    /**
     * @var UpdateInterface|MockObject
     */
    protected $update;

    /**
     * @var CampaignUpdater
     */
    protected $campaignUpdater;

    protected function setUp(): void
    {
        $this->includesRetriever = $this->getMockBuilder(\Magento\Staging\Model\Update\Includes\Retriever::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepository = $this->getMockBuilder(UpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->update = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $this->campaignUpdater = new CampaignUpdater($this->includesRetriever, $this->updateRepository);
    }

    public function testUpdateCampaignStatus()
    {
        $this->update->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->includesRetriever->expects($this->once())
            ->method('getIncludes')
            ->with([1])
            ->willReturn([
                [
                    'includes' => 1,
                ],
                [
                    'includes' => 1,
                ],
            ]);
        $this->update->expects($this->once())
            ->method('setIsCampaign')
            ->with(true);
        $this->updateRepository->expects($this->once())
            ->method('save')
            ->with($this->update);
        $this->campaignUpdater->updateCampaignStatus($this->update);
    }
}
