<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Controller\Adminhtml\Category\Update;

use Magento\Backend\App\Action\Context;
use Magento\CatalogStaging\Controller\Adminhtml\Category\Update\Save as SaveController;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    /** @var SaveController */
    protected $controller;

    /** @var Context|MockObject */
    protected $context;

    /** @var \Magento\Staging\Model\Entity\Update\Save|MockObject */
    protected $stagingUpdateSave;

    /** @var RequestInterface|MockObject */
    protected $request;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods([
                'getPostValue',
                'getParam',
                'getModuleName',
                'setModuleName',
                'getActionName',
                'setActionName',
                'setParams',
                'getParams',
                'getCookie',
                'isSecure',
            ])
            ->getMockForAbstractClass();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->stagingUpdateSave = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getCode', 'setCurrentStore', 'getStore'])
            ->getMockForAbstractClass();
        $this->controller = new SaveController($this->context, $this->stagingUpdateSave, $this->storeManager);
    }

    public function testExecute()
    {
        $categoryId = 1;
        $entityData = [];
        $staging = [];
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(
                ['entity_id'],
                ['staging']
            )
            ->willReturnOnConsecutiveCalls(
                $categoryId,
                $staging
            );
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($entityData);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->stagingUpdateSave
            ->expects($this->once())
            ->method('execute')
            ->with([
                'entityId' => $categoryId,
                'stagingData' => $staging,
                'entityData' => $entityData
            ])
            ->willReturn(true);
        $this->assertTrue($this->controller->execute());
    }
}
