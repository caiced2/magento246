<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma\Item\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\View;
use Magento\Framework\DataObject;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Layout;
use Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute\Edit;
use Magento\Rma\Model\Item\Attribute;
use Magento\Store\Model\WebsiteFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class EditTest extends TestCase
{
    /**
     * @var Edit
     */
    protected $action;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\Response\Http|MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Rma\Model\Item\Attribute|MockObject
     */
    protected $attributeMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var Type|MockObject
     */
    protected $entityTypeMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var View|MockObject
     */
    protected $viewMock;

    /**
     * @var Layout|MockObject
     */
    protected $layoutMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $blockMock;

    /**
     * @var WebsiteFactory|MockObject
     */
    protected $websiteFactoryMock;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var Manager|MockObject
     */
    protected $messageManagerMock;

    /**
     * Setup before each test
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->websiteFactoryMock = $this->createPartialMock(WebsiteFactory::class, ['create']);
        $this->coreRegistryMock = $this->createMock(Registry::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->responseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->attributeMock = $this->getMockBuilder(Attribute::class)
            ->addMethods(['getFrontendLabel'])
            ->onlyMethods(['load', 'setWebsite', 'setEntityTypeId', 'getId', 'getEntityTypeId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->entityTypeMock = $this->createMock(Type::class);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManager\ObjectManager::class);
        $this->messageManagerMock = $this->createMock(Manager::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->viewMock = $this->createMock(View::class);
        $this->layoutMock = $this->createMock(Layout::class);
        $this->blockMock = $this->getMockForAbstractClass(
            BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setActive', 'getMenuModel', 'getParentItems', 'addLink', 'getConfig', 'getTitle', 'prepend']
        );
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getActionFlag')
            ->willReturn($this->createMock(ActionFlag::class));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getHelper')
            ->willReturn($this->createMock(Data::class));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->action = $this->objectManager->getObject(
            Edit::class,
            [
                'context' => $this->contextMock,
                'coreRegistry' => $this->coreRegistryMock,
                'websiteFactory' => $this->websiteFactoryMock
            ]
        );
    }

    public function mockInitAttribute($website = null)
    {
        //_initAttribute
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(Attribute::class, [])
            ->willReturn($this->attributeMock);
        if ($website) {
            $this->websiteFactoryMock->expects($this->never())
                ->method('create');
        } else {
            $website = new DataObject();
            $this->websiteFactoryMock->expects($this->once())
                ->method('create')
                ->willReturn($website);
        }
        $this->attributeMock->expects($this->once())
            ->method('setWebsite')
            ->with($website)
            ->willReturnSelf();

        //_getEntityType
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(Config::class)
            ->willReturn($this->eavConfigMock);
        $this->eavConfigMock->expects($this->once())
            ->method('getEntityType')
            ->with('rma_item')
            ->willReturn($this->entityTypeMock);

        $this->attributeMock->expects($this->once())
            ->method('setEntityTypeId')
            ->willReturnSelf();
    }

    /**
     * Test for method execute.
     *
     * @dataProvider executeDataProvider
     * @param int|null $attributeId
     * @param int|null $website
     * @param string $expectedLabel
     * @param string $expectedTitle
     */
    public function testExecute($attributeId, $website, $expectedLabel, $expectedTitle)
    {
        $requestParameters = [
            ['attribute_id', null, $attributeId],
            ['website', null, $website]
        ];
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap($requestParameters);

        $this->mockInitAttribute($website);

        if ($attributeId) {
            $this->attributeMock->expects($this->once())
                ->method('load')
                ->with($attributeId)
                ->willReturnSelf();
            $this->attributeMock->expects($this->once())
                ->method('getFrontendLabel')
                ->willReturn(__($expectedTitle));
        } else {
            $this->attributeMock->expects($this->never())
                ->method('load');
        }
        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($attributeId);

        $this->coreRegistryMock->expects($this->once())
            ->method('register')
            ->with('entity_attribute', $this->attributeMock);

        $this->layoutMock->expects($this->atLeastOnce())
            ->method('getBlock')
            ->willReturn($this->blockMock);
        $this->viewMock->expects($this->atLeastOnce())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->viewMock->expects($this->atLeastOnce())
            ->method('getPage')
            ->willReturn($this->blockMock);
        $this->viewMock->expects($this->once())
            ->method('renderLayout');
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getTitle')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getMenuModel')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getParentItems')
            ->willReturn([]);
        $links = [];
        $this->blockMock->expects($this->atLeastOnce())
            ->method('addLink')
            ->with($this->callback(function ($value) use (&$links) {
                $links[] = $value->__toString();
                return true;
            }));
        $titles = [];
        $this->blockMock->expects($this->atLeastOnce())
            ->method('prepend')
            ->with($this->callback(function ($value) use (&$titles) {
                $titles[] = $value->__toString();
                return true;
            }));

        $this->assertEmpty($this->action->execute());
        $this->assertContains($expectedLabel, $links);
        $this->assertContains($expectedTitle, $titles);
    }

    public function executeDataProvider()
    {
        return [
            [null, null, 'New Return Item Attribute', 'New Return Attribute'],
            [null, 1, 'New Return Item Attribute', 'New Return Attribute'],
            [109, null, 'Edit Return Item Attribute', 'Return Attribute #109'],
            [111, 1, 'Edit Return Item Attribute', 'Return Attribute #111'],
        ];
    }

    /**
     * Test for execute method. Attribute is no longer exist.
     */
    public function testExecuteErrorNoId()
    {
        $attributeId = 1;
        $requestParameters = [
            ['attribute_id', null, $attributeId],
            ['website', null, null]
        ];
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap($requestParameters);

        $this->mockInitAttribute();

        $this->attributeMock->expects($this->once())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attributeMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('This attribute no longer exists.'));

        $this->assertEmpty($this->action->execute());
    }

    /**
     * Test for execute method. Entity type is wrong.
     */
    public function testExecuteErrorWrongEntityType()
    {
        $attributeId = 111;
        $entityTypeId = 1;
        $requestParameters = [
            ['attribute_id', null, $attributeId],
            ['website', null, null]
        ];
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap($requestParameters);

        $this->mockInitAttribute();

        $this->attributeMock->expects($this->once())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attributeMock->expects($this->once())
            ->method('getId')
            ->willReturn($attributeId);
        $this->attributeMock->expects($this->once())
            ->method('getEntityTypeId')
            ->willReturn($entityTypeId);
        $this->entityTypeMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($entityTypeId + 1);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('You cannot edit this attribute.'));

        $this->assertEmpty($this->action->execute());
    }
}
