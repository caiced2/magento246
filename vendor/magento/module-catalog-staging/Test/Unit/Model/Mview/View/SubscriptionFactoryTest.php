<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Mview\View;

use Magento\CatalogStaging\Model\Mview\View\SubscriptionFactory;
use Magento\Framework\Mview\View\SubscriptionFactory as FrameworkSubstrictionFactory;
use Magento\Framework\Mview\View\SubscriptionInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriptionFactoryTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\CatalogStaging\Model\Mview\View\SubscriptionFactory
     */
    protected $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->model = $objectManager->getObject(
            SubscriptionFactory::class,
            [
                'objectManager' => $this->objectManagerMock,
                'subscriptionModels' => [
                    'catalog_product_entity_int' => 'ProductEntityIntSubscription'
                ]
            ]
        );
    }

    public function testCreate()
    {
        $data = ['tableName' => 'catalog_product_entity_int', 'columnName' => 'entity_id'];

        $expectedData = $data;
        $expectedData['columnName'] = 'entity_id';

        $subscriptionMock = $this->getMockBuilder(SubscriptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with('ProductEntityIntSubscription', $expectedData)
            ->willReturn($subscriptionMock);

        $result = $this->model->create($data);
        $this->assertEquals($subscriptionMock, $result);
    }

    public function testCreateNoTableName()
    {
        $data = ['columnName' => 'entity_id'];

        $expectedData = $data;

        $subscriptionMock = $this->getMockBuilder(SubscriptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(FrameworkSubstrictionFactory::INSTANCE_NAME, $expectedData)
            ->willReturn($subscriptionMock);

        $result = $this->model->create($data);
        $this->assertEquals($subscriptionMock, $result);
    }
}
