<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model;

use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRuleStaging\Model\SalesRuleStagingAdapter;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for SalesRuleStagingAdapter
 */
class SalesRuleStagingAdapterTest extends TestCase
{
    /**
     * @var SalesRuleStagingAdapter
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var CampaignValidator|MockObject
     */
    private $campaignValidator;

    /**
     * @var Rule|MockObject
     */
    private $rule;

    /**
     * @var int
     */
    private $version;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();
        $this->campaignValidator = $this->getMockBuilder(CampaignValidator::class)
            ->disableOriginalConstructor()
            ->setMethods(['canBeScheduled'])
            ->getMock();
        $this->rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->version = 2;

        $this->model = $this->objectManager->getObject(
            SalesRuleStagingAdapter::class,
            [
                'entityManager' => $this->entityManager,
                'campaignValidator' => $this->campaignValidator
            ]
        );
    }

    public function testSchedule()
    {
        $arguments = ['created_in' => $this->version];

        $this->campaignValidator->expects($this->once())
            ->method('canBeScheduled')
            ->with($this->rule, $this->version, null)
            ->willReturn(true);
        $this->entityManager->expects($this->once())
            ->method('save')
            ->with($this->rule, $arguments)
            ->willReturn(true);

        $this->assertTrue($this->model->schedule($this->rule, $this->version));
    }

    public function testScheduleWithException()
    {
        $this->expectException('Magento\Framework\Exception\ValidatorException');
        $this->campaignValidator->expects($this->once())
            ->method('canBeScheduled')
            ->with($this->rule, $this->version, null)
            ->willReturn(false);
        $this->model->schedule($this->rule, $this->version);
    }
}
