<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\Action\AbstractAction;
use Magento\Reward\Model\ActionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionFactoryTest extends TestCase
{
    /**
     * @var ActionFactory
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $objectManagerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);

        $this->model = $objectManager->getObject(
            ActionFactory::class,
            ['objectManager' => $this->objectManagerMock]
        );
    }

    public function testCreate()
    {
        $type = 'action_type';
        $params = ['param' => 'value'];
        $actionMock = $this->createMock(AbstractAction::class);

        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with($type, $params)
            ->willReturn($actionMock);

        $this->assertEquals($actionMock, $this->model->create($type, $params));
    }
}
