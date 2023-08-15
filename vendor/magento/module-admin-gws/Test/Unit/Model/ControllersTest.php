<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\AdminGws\Model\Controllers as Ctrl;
use Magento\AdminGws\Model\ResourceModel\Collections;
use Magento\AdminGws\Model\Role;
use Magento\Backend\App\Action;
use Magento\Backend\Model\UrlInterface;
use Magento\Backend\Test\Unit\App\Action\Stub\ActionStub;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\CatalogEvent\Model\Event;
use Magento\CatalogRule\Model\Rule;
use Magento\CheckoutAgreements\Model\Agreement;
use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount\Index;
use Magento\GiftRegistry\Model\ResourceModel\Entity;
use Magento\Review\Model\Review;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\Website;
use Magento\UrlRewrite\Model\UrlRewrite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedAtLeastOnce;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ControllersTest extends TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Controllers
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_roleMock;

    /**
     * @var MockObject
     */
    protected $_storeManagerMock;

    /**
     * Controller request object
     *
     * @var MockObject
     */
    protected $_ctrlRequestMock;

    /**
     * Controller response object
     *
     * @var MockObject
     */
    protected $responseMock;

    /**
     * @var MockObject
     */
    protected $_controllerMock;

    /**
     * @var MockObject
     */
    protected $collectionsFactoryMock;

    /**
     * @var MockObject
     */
    protected $collectionsMock;

    /**
     * @var MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $backendUrl;

    /**
     * @var MockObject
     */
    protected $_objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $this->_roleMock = $this->createMock(Role::class);
        $this->_objectManager = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->_storeManagerMock = $this->createMock(StoreManager::class);
        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(
                ['setRedirect']
            )
            ->onlyMethods(['sendResponse'])
            ->getMockForAbstractClass();
        $this->_controllerMock = $this->createMock(Action::class);
        $this->_ctrlRequestMock = $this->createMock(Http::class);
        $this->collectionsFactoryMock = $this->createPartialMock(
            \Magento\AdminGws\Model\ResourceModel\CollectionsFactory::class,
            ['create']
        );
        $this->collectionsMock = $this->createPartialMock(
            Collections::class,
            ['getUsersOutsideLimitedScope', 'getRolesOutsideLimitedScope']
        );

        $coreRegistry = $this->createMock(Registry::class);

        $this->categoryRepositoryMock = $this->getMockForAbstractClass(
            CategoryRepositoryInterface::class,
            [],
            '',
            false
        );

        $this->backendUrl = $this->createMock(UrlInterface::class);

        $this->_model = $helper->getObject(
            \Magento\AdminGws\Model\Controllers::class,
            [
                'role' => $this->_roleMock,
                'backendUrl' => $this->backendUrl,
                'registry' => $coreRegistry,
                'objectManager' => $this->_objectManager,
                'storeManager' => $this->_storeManagerMock,
                'response' => $this->responseMock,
                'request' => $this->_ctrlRequestMock,
                'collectionsFactory' => $this->collectionsFactoryMock,
                'categoryRepository' => $this->categoryRepositoryMock
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        unset($this->_controllerMock);
        unset($this->_ctrlRequestMock);
        unset($this->responseMock);
        unset($this->_model);
        unset($this->_objectManager);
        unset($this->_roleMock);
        unset($this->collectionsFactoryMock);
        unset($this->collectionsMock);
        unset($this->categoryRepositoryMock);
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionRoleHasntWebSiteIdsAndConsideringActionsToDenyForwardAvoidCycling(): void
    {
        $this->_ctrlRequestMock
            ->method('getActionName')
            ->willReturnOnConsecutiveCalls(Ctrl::ACTION_EDIT, Ctrl::ACTION_DENIED);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn(null);

        $this->_model->validateRuleEntityAction();
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionRoleHasntWebSiteIdsAndConsideringActionsToDenyForward(): void
    {
        $this->_ctrlRequestMock
            ->method('getActionName')
            ->willReturnOnConsecutiveCalls(Ctrl::ACTION_EDIT, 'any_action');
        $this->_ctrlRequestMock->expects($this->once())->method('initForward');
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            Ctrl::ACTION_DENIED
        )->willReturnSelf();
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with(false);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn(null);

        $this->_model->validateRuleEntityAction();
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionWhichIsNotInDenyList(): void
    {
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getActionName'
        )->willReturn(
            'any_action'
        );

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn(null);
        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionNoAppropriateEntityIdInRequestParams(): void
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(null);
        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);
        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /**
     * Test get valid entity model class name.
     *
     * @param string $controllerName
     * @param string $modelName
     *
     * @return void
     * @dataProvider validateRuleEntityActionGetValidModuleClassNameDataProvider
     */
    public function testValidateRuleEntityActionGetValidModuleClassName(
        string $controllerName,
        string $modelName
    ): void {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            $controllerName
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            $modelName
        )->willReturn(
            null
        );

        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /**
     * @return array
     */
    public function validateRuleEntityActionGetValidModuleClassNameDataProvider(): array
    {
        return [
            ['promo_catalog', Rule::class],
            ['promo_quote', \Magento\SalesRule\Model\Rule::class],
            ['reminder', \Magento\Reminder\Model\Rule::class],
            ['customersegment', Segment::class]
        ];
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionGetModuleClassNameWithInvalidController(): void
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'some_other'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $this->_objectManager->expects($this->exactly(0))->method('create');

        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionDenyActionIfSpecifiedRuleEntityDoesntExist(): void
    {
        $this->_ctrlRequestMock
            ->method('getActionName')
            ->willReturnOnConsecutiveCalls(Ctrl::ACTION_EDIT, Ctrl::ACTION_DENIED);

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $modelMock = $this->createMock(Rule::class);
        $modelMock->expects($this->once())->method('load')->with(1);
        $modelMock->expects($this->once())->method('getId')->willReturn(false);

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionDenyActionIfRoleHasNoExclusiveAccessToAssignedToRuleEntityWebsites(): void
    {
        $modelMock = $this->createMock(Rule::class);
        $this->_ctrlRequestMock
            ->method('getActionName')
            ->willReturnOnConsecutiveCalls(Ctrl::ACTION_EDIT, Ctrl::ACTION_DENIED);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn([1]);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);
        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            false
        );

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);

        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->willReturn(1);
        $modelMock->expects($this->once())->method('getOrigData')->willReturn([1, 2]);

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionDenyActionIfRoleHasNoAccessToAssignedToRuleEntityWebsites(): void
    {
        $this->_ctrlRequestMock
            ->method('getActionName')
            ->willReturnOnConsecutiveCalls(Ctrl::ACTION_EDIT, Ctrl::ACTION_DENIED);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn([1]);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );

        $modelMock = $this->createMock(Rule::class);
        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->willReturn(1);
        $modelMock->expects($this->once())->method('getOrigData')->willReturn([1, 2]);

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);
        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            true
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            false
        );

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    /**
     * @param array $post
     * @param bool $isAll
     * @param bool $result
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateCmsHierarchyActionDataProvider
     */
    public function testValidateCmsHierarchyAction(array $post, bool $isAll, bool $result, $getActionNameInvoke): void
    {
        $this->_ctrlRequestMock->expects($this->any())
            ->method('getPost')
            ->willReturn($post);

        $this->expectsForward($this->never(), $getActionNameInvoke);

        $websiteId = (isset($post['website'])) ? $post['website'] : 1;
        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $storeId = (isset($post['store'])) ? $post['store'] : 1;
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getWebsite'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->_storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);

        $hasExclusiveAccess = in_array($websiteMock->getId(), [1]);
        $hasExclusiveStoreAccess = in_array($storeMock->getId(), [2]);

        $this->_roleMock->expects($this->any())
            ->method('hasExclusiveAccess')
            ->willReturn($hasExclusiveAccess);

        $this->_roleMock->expects($this->any())
            ->method('hasExclusiveStoreAccess')
            ->willReturn($hasExclusiveStoreAccess);

        $this->_roleMock->expects($this->any())
            ->method('getIsAll')
            ->willReturn($isAll);

        $this->assertEquals($result, $this->_model->validateCmsHierarchyAction());
    }

    /**
     * Data provider for testValidateCmsHierarchyAction()
     *
     * @return array
     */
    public function validateCmsHierarchyActionDataProvider(): array
    {
        return [
            [[], true, true, 'getActionNameInvoke' => $this->never()],
            [[], false, false, 'getActionNameInvoke' => $this->atLeastOnce()],
            [['website' => 1, 'store' => 1], false, false, 'getActionNameInvoke' => $this->atLeastOnce()],
            [['store' => 2], false, true, 'getActionNameInvoke' => $this->never()],
            [['store' => 1], false, false, 'getActionNameInvoke' => $this->atLeastOnce()]
        ];
    }

    /**
     * @return void
     */
    public function testValidateRuleEntityActionWithValidParams(): void
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn([1]);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $modelMock = $this->createMock(Rule::class);
        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->willReturn(1);
        $modelMock->expects($this->once())->method('getOrigData')->willReturn([1, 2]);

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            true
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            true
        );

        $this->assertTrue($this->_model->validateRuleEntityAction());
    }

    /**
     * @return void
     */
    public function testValidateAdminUserActionWithoutId(): void
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'user_id'
        )->willReturn(
            null
        );
        $this->assertTrue($this->_model->validateAdminUserAction());
    }

    /**
     * @return void
     */
    public function testValidateAdminUserActionWithNotLimitedId(): void
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'user_id'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getUsersOutsideLimitedScope'
        )->willReturn(
            []
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->assertTrue($this->_model->validateAdminUserAction());
    }

    /**
     * @return void
     */
    public function testValidateAdminUserActionWithLimitedId(): void
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'user_id'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getUsersOutsideLimitedScope'
        )->willReturn(
            [1]
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertFalse($this->_model->validateAdminUserAction());
    }

    /**
     * @return void
     */
    public function testValidateAdminRoleActionWithoutId(): void
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            null
        );

        $this->assertTrue($this->_model->validateAdminRoleAction());
    }

    /**
     * @return void
     */
    public function testValidateAdminRoleActionWithNotLimitedId(): void
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getRolesOutsideLimitedScope'
        )->willReturn(
            []
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->assertTrue($this->_model->validateAdminRoleAction());
    }

    /**
     * @return void
     */
    public function testValidateAdminRoleActionWithLimitedId(): void
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getRolesOutsideLimitedScope'
        )->willReturn(
            [1]
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertFalse($this->_model->validateAdminRoleAction());
    }

    /**
     * @return void
     */
    public function testValidateRmaAttributeDeleteAction(): void
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->assertFalse($this->_model->validateRmaAttributeDeleteAction());
    }

    /**
     * @return void
     */
    public function testValidateRmaAttributeSaveAction(): void
    {
        $websiteId = 1;

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            ['delete' => '1']
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $this->_storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->_roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);

        $this->assertTrue($this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * @return void
     */
    public function testValidateRmaAttributeSaveActionNoWebsiteAccess(): void
    {
        $websiteId = 1;

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            []
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getWebsite'
        )->willReturn(
            $websiteMock
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->willReturn(
            false
        );

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertFalse($this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * @return void
     */
    public function testValidateRmaAttributeSaveActionNoWebsiteCodeAndNoAllowedWebsites(): void
    {
        $websiteId = null;
        $allowedWebsiteIds = [];

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            []
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'getWebsiteIds'
        )->willReturn(
            $allowedWebsiteIds
        );

        $this->_ctrlRequestMock->expects($this->atLeastOnce())->method('getActionName')
            ->willReturn(Ctrl::ACTION_DENIED);

        $this->assertFalse($this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * @return void
     */
    public function testValidateRmaAttributeSaveActionRedirectToAllowedWebsites(): void
    {
        $websiteId = null;
        $allowedWebsiteIds = [2];

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            []
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'getWebsiteIds'
        )->willReturn(
            $allowedWebsiteIds
        );

        $this->responseMock->expects(
            $this->once()
        )->method(
            'setRedirect'
        );
        $this->assertFalse($this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * @param bool $isWebSiteLevel
     * @param string $action
     * @param int|null $id
     * @param $expectedInvoke
     *
     * @return void
     * @dataProvider validateGiftCardAccountDataProvider
     */
    public function testValidateGiftCardAccount(
        bool $isWebSiteLevel,
        string $action,
        ?int $id,
        $expectedInvoke,
        $getActionNameInvoke
    ): void {
        $controllerMock = $this->createPartialMock(
            Index::class,
            ['setShowCodePoolStatusMessage']
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'getIsWebsiteLevel'
        )->willReturn(
            $isWebSiteLevel
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($action);

        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'id'
        )->willReturn(
            $id
        );

        $this->expectsForward($expectedInvoke, $getActionNameInvoke);
        $this->_model->validateGiftCardAccount($controllerMock);
    }

    /**
     * Data provider for testValidateCmsHierarchyAction()
     *
     * @return array
     */
    public function validateGiftCardAccountDataProvider(): array
    {
        return [
            'WithWebsiteLevelPermissions' => [
                'isWebSiteLevel' => true,
                'action' => '',
                'id' => null,
                'expectedInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'WithoutWebsiteLevelPermissionsActionNew' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_NEW,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionGenerate' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_GENERATE,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionEditWithoutId' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_EDIT,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionEdit' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_EDIT,
                'id' => 1,
                'expectedInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionNewCamelCaseActionName' => [
                'isWebSiteLevel' => false,
                'action' => 'NeW',
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ]
        ];
    }

    /**
     * @param int|null $id
     * @param int|null $websiteId
     * @param array $roleWebsiteIds
     * @param $expectedInvoke
     * @param $expectedForwardInvoke
     * @param bool $expectedValue
     *
     * @return void
     * @dataProvider validateGiftregistryEntityActionDataProvider
     */
    public function testValidateGiftregistryEntityAction(
        ?int $id,
        ?int $websiteId,
        array $roleWebsiteIds,
        $expectedInvoke,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        bool $expectedValue
    ): void {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            $id
        );

        $recourceEntityMock = $this->createPartialMock(
            Entity::class,
            ['getWebsiteIdByEntityId']
        );

        $modelEntityMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\Entity::class,
            ['getResource']
        );

        $modelEntityMock->expects(
            $expectedInvoke
        )->method(
            'getResource'
        )->willReturn(
            $recourceEntityMock
        );

        $recourceEntityMock->expects(
            $expectedInvoke
        )->method(
            'getWebsiteIdByEntityId'
        )->with(
            $id
        )->willReturn(
            $websiteId
        );

        $this->_objectManager->expects(
            $expectedInvoke
        )->method(
            'create'
        )->with(
            \Magento\GiftRegistry\Model\Entity::class
        )->willReturn(
            $modelEntityMock
        );

        $this->_roleMock->expects(
            $expectedInvoke
        )->method(
            'getWebsiteIds'
        )->willReturn(
            $roleWebsiteIds
        );

        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateGiftregistryEntityAction());
    }

    /**
     * Data provider for testValidateGiftregistryEntityAction()
     *
     * @return array
     */
    public function validateGiftregistryEntityActionDataProvider(): array
    {
        $id = 1;
        $websiteId = 1;
        return [
            'withoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedInvoke' => $this->never(),
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false
            ],
            'withIdNotInRoleIds' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedInvoke' => $this->atLeastOnce(),
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false
            ],
            'withIdInRoleIds' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedInvoke' => $this->atLeastOnce(),
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true
            ]
        ];
    }

    /**
     * @param string $actionName
     * @param int|null $attributeId
     * @param int|null $websiteId
     * @param $hasWebsiteAccess
     * @param $expectedForwardInvoke
     * @param bool $expectedValue
     *
     * @return void
     * @dataProvider validateCustomerAttributeActionsDataProvider
     */
    public function testValidateCustomerAttributeActions(
        string $actionName,
        ?int $attributeId,
        ?int $websiteId,
        $hasWebsiteAccess,
        $expectedForwardInvoke,
        bool $expectedValue
    ): void {
        $this->_ctrlRequestMock
            ->method('getActionName')
            ->willReturnOnConsecutiveCalls($actionName, Ctrl::ACTION_DENIED);
        $this->_ctrlRequestMock
            ->method('getParam')
            ->withConsecutive(['attribute_id'], ['website'])
            ->willReturnOnConsecutiveCalls($attributeId, $websiteId);

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->willReturn(
            $hasWebsiteAccess
        );

        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->assertEquals($expectedValue, $this->_model->validateCustomerAttributeActions());
    }

    /**
     * Data provider for testValidateCustomerAttributeActions()
     *
     * @return array
     */
    public function validateCustomerAttributeActionsDataProvider(): array
    {
        return [
            'actionNew' => [
                'actionName' => Ctrl::ACTION_NEW,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false
            ],
            'actionDelete' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false
            ],
            'actionEdit' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'attributeId' => null,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false
            ],
            'actionSave' => [
                'actionName' => Ctrl::ACTION_SAVE,
                'attributeId' => null,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false
            ],
            'actionEditWithAttributeId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true
            ],
            'actionDoesntMatterWithoutWebAccess' => [
                'actionName' => 'DoesntMatter',
                'attributeId' => null,
                'websiteId' => 1,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false
            ]
        ];
    }

    /**
     * @param int|null $id
     * @param int|null $websiteId
     * @param array $roleWebsiteIds
     * @param InvokedCount|InvokedAtLeastOnce $expectedForwardInvoke
     * @param InvokedCount|InvokedAtLeastOnce $getActionNameInvoke
     * @param bool $expectedRedirect
     *
     * @return void
     * @dataProvider validateCustomerEditDataProvider
     */
    public function testValidateCustomerEdit(
        ?int $id,
        ?int $websiteId,
        array $roleWebsiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        bool $expectedRedirect = false
    ): void {
        $this->expectsCustomerAction(
            $id,
            $websiteId,
            $roleWebsiteIds,
            $expectedForwardInvoke,
            $getActionNameInvoke,
            $expectedRedirect
        );
        $this->_model->validateCustomerEdit();
    }

    /**
     * @param int|null $id
     * @param int|null $websiteId
     * @param array $roleWebsiteIds
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateCustomerbalanceDataProvider
     */
    public function testValidateCustomerbalance(
        ?int $id,
        ?int $websiteId,
        array $roleWebsiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $this->expectsCustomerAction($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateCustomerbalance();
    }

    /**
     * Data provider for testValidateCustomer()
     *
     * @return array
     */
    public function validateCustomerEditDataProvider(): array
    {
        $id = 1;
        $websiteId = 1;
        return [
            'customerWithoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedRedirect' => false
            ],
            'customerHasNoRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedRedirect' => true
            ],
            'customerHasRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedRedirect' => false
            ]
        ];
    }

    /**
     * Data provider for testValidateCustomerbalance()
     *
     * @return array
     */
    public function validateCustomerbalanceDataProvider(): array
    {
        $id = 1;
        $websiteId = 1;
        return [
            'customerWithoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'customerHasNoRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'customerHasRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ]
        ];
    }

    /**
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateCatalogProductMassActionsDataProvider
     */
    public function testValidateCatalogProductMassActions(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $storeId = 1;
        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'store'
        )->willReturn(
            $storeId
        );
        $this->_storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $storeMock->expects($this->any())->method('getId')->willReturn($storeId);

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasStoreAccess'
        )->willReturn(
            $hasStoreAccess
        );

        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateCatalogProductMassActions();
    }

    /**
     * Data provider for testValidateCatalogProductMassActions()
     *
     * @return array
     */
    public function validateCatalogProductMassActionsDataProvider(): array
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ]
        ];
    }

    /**
     * @param bool $isGetAll
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @param bool $expectedValue
     *
     * @return void
     * @dataProvider validateCatalogProductAttributeActionsDataProvider
     */
    public function testValidateCatalogProductAttributeActions(
        bool $isGetAll,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        bool $expectedValue
    ): void {
        $this->_roleMock->expects($this->any())->method('getIsAll')->willReturn($isGetAll);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateCatalogProductAttributeActions());
    }

    /**
     * Data provider for testValidateCatalogProductAttributeActions()
     *
     * @return array
     */
    public function validateCatalogProductAttributeActionsDataProvider(): array
    {
        return [
            'permissionsAreAllowed' => [
                'isAll' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true
            ],
            'permissionsAreNotAllowed' => [
                'isAll' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false
            ]
        ];
    }

    /**
     * @param bool $isGetAll
     * @param int|null $attributeId
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @param bool $expectedValue
     *
     * @return void
     * @dataProvider validateCatalogProductAttributeCreateActionDataProvider
     */
    public function testValidateCatalogProductAttributeCreateAction(
        bool $isGetAll,
        ?int $attributeId,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        bool $expectedValue
    ): void {
        $this->_roleMock->expects($this->any())->method('getIsAll')->willReturn($isGetAll);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'attribute_id'
        )->willReturn(
            $attributeId
        );
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateCatalogProductAttributeCreateAction());
    }

    /**
     * Data provider for testValidateCatalogProductAttributeCreateAction()
     *
     * @return array
     */
    public function validateCatalogProductAttributeCreateActionDataProvider(): array
    {
        $attributeId = 1;
        return [
            'permissionsAreAllowedAndAttributeIdIsSet' => [
                'isAll' => true,
                'attributeId' => $attributeId,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true
            ],
            'permissionsAreAllowedAndAttributeIdIsNotSet' => [
                'isAll' => true,
                'attributeId' => null,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true
            ],
            'permissionsAreNotAllowedAndAttributeIdIsSet' => [
                'isAll' => false,
                'attributeId' => $attributeId,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true
            ],
            'permissionsAreNotAllowedAndAttributeIdIsNotSet' => [
                'isAll' => false,
                'attributeId' => null,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false
            ]
        ];
    }

    /**
     * @param int $reviewId
     * @param array $reviewStoreIds
     * @param array $storeIds
     * @param $expectedRedirectInvoke
     *
     * @return void
     * @dataProvider validateCatalogProductReviewDataProvider
     */
    public function testValidateCatalogProductReview(
        int $reviewId,
        array $reviewStoreIds,
        array $storeIds,
        $expectedRedirectInvoke
    ): void {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'id'
        )->willReturn(
            $reviewId
        );

        $reviewMock = $this->getMockBuilder(Review::class)
            ->addMethods(['getStores'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $reviewMock->expects($this->once())->method('load')->willReturnSelf();

        $reviewMock->expects(
            $this->once()
        )->method(
            'getStores'
        )->willReturn(
            $reviewStoreIds
        );

        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            Review::class
        )->willReturn(
            $reviewMock
        );

        $this->_roleMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogProductReview();
    }

    /**
     * Data provider for testValidateCatalogProductReview()
     *
     * @return array
     */
    public function validateCatalogProductReviewDataProvider(): array
    {
        $reviewId = 1;
        return [
            'allowIfReviewHasAccess' => [
                'reviewId' => $reviewId,
                'reviewStoreIds' => [1],
                'storeIds' => [1, 2, 3],
                'expectedRedirectInvoke' => $this->never()
            ],
            'redirectIfReviewHasNoAccess' => [
                'reviewId' => $reviewId,
                'reviewStoreIds' => [1],
                'storeIds' => [2, 3],
                'expectedRedirectInvoke' => $this->once()
            ]
        ];
    }

    /**
     * @param int|null $storeId
     * @param bool $hasStoreAccess
     * @param $expectedRedirectInvoke
     *
     * @return void
     * @dataProvider validateCatalogProductEditDataProvider
     */
    public function testValidateCatalogProductEdit(?int $storeId, bool $hasStoreAccess, $expectedRedirectInvoke): void
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            $storeId
        );

        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $storeMock->expects($this->any())->method('getId')->willReturn($storeId);

        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getStore'
        )->willReturn(
            $storeMock
        );

        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogProductEdit();
    }

    /**
     * Data provider for testValidateCatalogProductEditData()
     *
     * @return array
     */
    public function validateCatalogProductEditDataProvider(): array
    {
        $storeId = 1;
        return [
            'allowStoreInRequestWhenStoreIdIsEmpty' => [
                'storeId' => null,
                'hasStoreAccess' => false,
                'expectedRedirectInvoke' => $this->never()
            ],
            'allowIfHasStoreAccess' => [
                'storeId' => $storeId,
                'hasStoreAccess' => true,
                'expectedRedirectInvoke' => $this->never()
            ],
            'redirectIfNoStoreAcces' => [
                'storeId' => $storeId,
                'hasStoreAccess' => false,
                'expectedRedirectInvoke' => $this->once()
            ]
        ];
    }

    /**
     * @param string $actionName
     * @param int|null $categoryId
     * @param bool|null $isWebsiteLevel
     * @param array|null $allowedRootCategories
     * @param string|null $categoryPath
     * @param $expectedForwardInvoke
     *
     * @return void
     * @dataProvider validateCatalogEventsDataProvider
     */
    public function testValidateCatalogEvents(
        string $actionName,
        ?int $categoryId,
        ?bool $isWebsiteLevel,
        ?array $allowedRootCategories,
        ?string $categoryPath,
        $expectedForwardInvoke
    ): void {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'category_id'
        )->willReturn(
            $categoryId
        );

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);

        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn($isWebsiteLevel);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);

        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->_model->validateCatalogEvents();
    }

    /**
     * @return void
     */
    public function testValidateCatalogEventsException(): void
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_NEW);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('category_id')->willReturn(1);
        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException()
        );
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateCatalogEvents();
    }

    /**
     * Data provider for testValidateCatalogEvents()
     *
     * @return array
     */
    public function validateCatalogEventsDataProvider(): array
    {
        return [
            'allowIfActionNameIsNotNew' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'categoryId' => null,
                'isWebsiteLevel' => null,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfActionNameIsNewWithoutCategory' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => null,
                'isWebsiteLevel' => null,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'forwardIfActionNameIsNewWithCategoryAndWithoutWebsiteLevelAndWithoutAllowedCategory' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => 1,
                'isWebsiteLevel' => false,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory2',
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'allowIfActionNameIsNewWithCategoryAndAccess' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => 1,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'expectedForwardInvoke' => $this->never()
            ]
        ];
    }

    /**
     * @param int|null $id
     * @param bool|null $isWebsiteLevel
     * @param array|null $allowedRootCategories
     * @param string|null $categoryPath
     * @param bool|null $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $expectedRedirectInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateCatalogEventEditDataProvider
     */
    public function testValidateCatalogEventEdit(
        ?int $id,
        ?bool $isWebsiteLevel,
        ?array $allowedRootCategories,
        ?string $categoryPath,
        ?bool $hasStoreAccess,
        $expectedForwardInvoke,
        $expectedRedirectInvoke,
        $getActionNameInvoke
    ): void {
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn($id);
        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn($isWebsiteLevel);

        $catalogEventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCategoryId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $catalogEventMock->expects($this->any())->method('load')->willReturnSelf();
        $catalogEventMock->expects($this->any())->method('getCategoryId')->willReturn(1);
        $catalogEventMock->expects($this->any())->method('getId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Event::class
        )->willReturn(
            $catalogEventMock
        );

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);

        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $storeMock->expects($this->any())->method('getId')->willReturn(1);

        $this->_storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);
        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getDefaultStoreView'
        )->willReturn(
            $storeMock
        );
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogEventEdit();
    }

    /**
     * @return void
     */
    public function testValidateCatalogEventEditException(): void
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn(true);

        $catalogEventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCategoryId'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $catalogEventMock->expects($this->any())->method('load')->willReturnSelf();
        $catalogEventMock->expects($this->any())->method('getCategoryId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Event::class
        )->willReturn(
            $catalogEventMock
        );

        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException()
        );
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateCatalogEventEdit();
    }

    /**
     * Data provider for testValidateCatalogEvents()
     *
     * @return array
     */
    public function validateCatalogEventEditDataProvider(): array
    {
        $storeId = 1;
        return [
            'allow' => [
                'id' => null,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'hasStoreAccess' => null,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'forwardIfCategoryNotAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory2'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => null,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'redirectIfCategoryAllowedButStoreInRequestNotAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->once(),
                'getActionNameInvoke' => $this->never()
            ],
            'allowIfCategoryAllowedAndStoreInRequestAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ]
        ];
    }

    /**
     * @param string $actionName
     * @param int|null $parentId
     * @param string|null $categoryPath
     * @param array|null $allowedRootCategories
     * @param bool|null $exclusiveCategoryAccess
     * @param $expectedForwardInvoke
     *
     * @return void
     * @dataProvider validateCatalogCategoriesAddDataProvider
     */
    public function testValidateCatalogCategoriesAdd(
        string $actionName,
        ?int $parentId,
        ?string $categoryPath,
        ?array $allowedRootCategories,
        ?bool $exclusiveCategoryAccess,
        $expectedForwardInvoke
    ): void {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('parent')->willReturn($parentId);

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);
        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturn(
            $exclusiveCategoryAccess
        );
        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->_model->validateCatalogCategories();
    }

    /**
     * Data provider for validateCatalogCategoriesAdd
     *
     * @return array
     */
    public function validateCatalogCategoriesAddDataProvider(): array
    {
        return [
            'allowIfNotAddAndEdit' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'parentId' => null,
                'categoryPath' => null,
                'allowedRootCategories' => null,
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'allowIfAddAndHasPermission' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfAddAndNoAllowedCategory' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'forwardIfAddAndNoExclusiveCategoryAccess' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => false,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ]
        ];
    }

    /**
     * Test ValidateCatalogCategoriesEdit
     *
     * @param string $actionName
     * @param int|null $parentId
     * @param int|null $id
     * @param string|null $categoryPath
     * @param array|null $allowedRootCategories
     * @param bool|null $exclusiveCategoryAccess
     * @param $expectedForwardInvoke
     *
     * @return void
     * @dataProvider validateCatalogCategoriesEditDataProvider
     */
    public function testValidateCatalogCategoriesEdit(
        string $actionName,
        ?int $parentId,
        ?int $id,
        ?string $categoryPath,
        ?array $allowedRootCategories,
        ?bool $exclusiveCategoryAccess,
        $expectedForwardInvoke
    ): void {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturnMap(
            [
                ['id', null, $id],
                ['parent', null, $parentId]
            ]
        );

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);
        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturn(
            $exclusiveCategoryAccess
        );
        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->_model->validateCatalogCategories();
    }

    /**
     * @return void
     */
    public function testValidateCatalogCategoriesEditException(): void
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);

        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException()
        );
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn([]);
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateCatalogCategories();
    }

    /**
     * @return array
     */
    public function validateCatalogCategoriesEditDataProvider(): array
    {
        return [
            'allowIfNotAddAndEdit' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'parentId' => null,
                'id' => null,
                'categoryPath' => null,
                'allowedRootCategories' => null,
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'allowIfEditAndHasPermissionAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfEditAndNoAllowedCategoryAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'forwardIfEditAndNoExclusiveCategoryAccessAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => false,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'allowIfEditAndHasPermissionAndId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => null,
                'id' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfEditAndNoAllowedCategoryAndId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => null,
                'id' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ]
        ];
    }

    /**
     * @return void
     */
    public function testValidateSalesOrderCreation(): void
    {
        $this->_roleMock->expects($this->any())->method('getWebsiteIds')->willReturn([]);
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateSalesOrderCreation();
    }

    /**
     * test validateSalesOrderViewAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderViewAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $salesOrderMock = $this->createPartialMock(
            Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('order_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderViewAction();
    }

    /**
     * test validateSalesOrderCreditmemoViewAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderCreditmemoViewAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $orderCreditmemoMock = $this->createPartialMock(
            Creditmemo::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderCreditmemoMock->expects($this->any())->method('load')->willReturnSelf();
        $orderCreditmemoMock->expects($this->any())->method('getId')->willReturn(1);
        $orderCreditmemoMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Creditmemo::class
        )->willReturn(
            $orderCreditmemoMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('creditmemo_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderCreditmemoViewAction();
    }

    /**
     * test validateSalesOrderInvoiceViewAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderInvoiceViewAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $orderInvoiceMock = $this->createPartialMock(
            Invoice::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderInvoiceMock->expects($this->any())->method('load')->willReturnSelf();
        $orderInvoiceMock->expects($this->any())->method('getId')->willReturn(1);
        $orderInvoiceMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Invoice::class
        )->willReturn(
            $orderInvoiceMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('invoice_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderInvoiceViewAction();
    }

    /**
     * test validateSalesOrderShipmentViewAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderShipmentViewAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $orderShipmentMock = $this->createPartialMock(
            Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('shipment_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderShipmentViewAction();
    }

    /**
     * Test validateSalesOrderCreditmemoCreateAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderCreditmemoCreateAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $orderCreditmemoMock = $this->createPartialMock(
            Creditmemo::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderCreditmemoMock->expects($this->any())->method('load')->willReturnSelf();
        $orderCreditmemoMock->expects($this->any())->method('getId')->willReturn(1);
        $orderCreditmemoMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Creditmemo::class
        )->willReturn(
            $orderCreditmemoMock
        );

        $this->_ctrlRequestMock
            ->method('getParam')
            ->withConsecutive(['order_id'], ['invoice_id'], ['creditmemo_id'])
            ->willReturnOnConsecutiveCalls(null, null, 1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderCreditmemoCreateAction();
    }

    /**
     * Test validateSalesOrderInvoiceCreateAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderInvoiceCreateAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $orderInvoiceMock = $this->createPartialMock(
            Invoice::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderInvoiceMock->expects($this->any())->method('load')->willReturnSelf();
        $orderInvoiceMock->expects($this->any())->method('getId')->willReturn(1);
        $orderInvoiceMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Invoice::class
        )->willReturn(
            $orderInvoiceMock
        );

        $this->_ctrlRequestMock
            ->method('getParam')
            ->withConsecutive(['order_id'], ['invoice_id'])
            ->willReturnOnConsecutiveCalls(null, 1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderInvoiceCreateAction();
    }

    /**
     * Test validateSalesOrderShipmentCreateAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderShipmentCreateAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $hasDefaultStoreAccess = false;
        $defaultStoreId = 1;
        $orderStoreId = 2;
        $this->mockCurrentStore($defaultStoreId);
        $orderShipmentMock = $this->createPartialMock(
            Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn($orderStoreId);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock
            ->method('getParam')
            ->withConsecutive(['order_id'], ['shipment_id'])
            ->willReturnOnConsecutiveCalls(null, 1);
        $this->_roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->willReturnMap(
                [
                    [2, $hasStoreAccess],
                    [1, $hasDefaultStoreAccess],
                ]
            );
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_storeManagerMock->expects($hasStoreAccess ? $this->once() : $this->never())
            ->method('setCurrentStore')
            ->with($orderStoreId);
        $this->_model->validateSalesOrderShipmentCreateAction();
    }

    /**
     * test validateSalesOrderMassAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderMassAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $salesOrderMock = $this->createPartialMock(
            Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')
            ->with('order_ids', [])
            ->willReturn([1, 2, 3]);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderMassAction();
    }

    /**
     * Test validateSalesOrderEditStartAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderEditStartAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $salesOrderMock = $this->createPartialMock(
            Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('order_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderEditStartAction();
    }

    /**
     * Data provider for ValidateSalesOrder tests.
     *
     * @return array
     */
    public function validateSalesOrderDataProvider(): array
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ]
        ];
    }

    /**
     * @return void
     */
    public function testValidateSalesOrderShipmentTrackActionHasStoreAccess(): void
    {
        $storeId = 1;
        $this->mockCurrentStore($storeId);
        $orderShipmentTrackMock = $this->createPartialMock(
            Track::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentTrackMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentTrackMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentTrackMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $orderShipmentMock = $this->createPartialMock(
            Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager
            ->method('create')
            ->withConsecutive([Track::class], [Shipment::class])
            ->willReturnOnConsecutiveCalls($orderShipmentTrackMock, $orderShipmentMock);

        $this->_ctrlRequestMock
            ->method('getParam')
            ->withConsecutive(['track_id'], ['order_id'], ['shipment_id'])
            ->willReturnOnConsecutiveCalls(1, null, 1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn(true);
        $this->_model->validateSalesOrderShipmentTrackAction();
    }

    /**
     * @return void
     */
    public function testValidateSalesOrderShipmentTrackActionHasNoStoreAccess(): void
    {
        $orderShipmentTrackMock = $this->createPartialMock(
            Track::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentTrackMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentTrackMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentTrackMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Track::class
        )->willReturn(
            $orderShipmentTrackMock
        );

        $this->_ctrlRequestMock
            ->method('getParam')
            ->with('track_id')
            ->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn(false);
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateSalesOrderShipmentTrackAction();
    }

    /**
     * Test validateCheckoutAgreementEditAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateCheckoutAgreementEditActionDataProvider
     */
    public function testValidateCheckoutAgreementEditAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $checkoutAgreementMock = $this->getMockBuilder(Agreement::class)
            ->addMethods(['getStoreId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutAgreementMock->expects($this->any())->method('load')->willReturnSelf();
        $checkoutAgreementMock->expects($this->any())->method('getId')->willReturn(1);
        $checkoutAgreementMock->expects($this->any())->method('getStoreId')->willReturn([1]);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Agreement::class
        )->willReturn(
            $checkoutAgreementMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateCheckoutAgreementEditAction();
    }

    /**
     * Data provider for ValidateCheckoutAgreementEditAction test.
     *
     * @return array
     */
    public function validateCheckoutAgreementEditActionDataProvider(): array
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ]
        ];
    }

    /**
     * Test validateUrlRewriteEditAction
     *
     * @param bool $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateUrlRewriteEditAction(
        bool $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $urlRewriteMock = $this->getMockBuilder(UrlRewrite::class)
            ->addMethods(['getStoreId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $urlRewriteMock->expects($this->any())->method('load')->willReturnSelf();
        $urlRewriteMock->expects($this->any())->method('getId')->willReturn(1);
        $urlRewriteMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            UrlRewrite::class
        )->willReturn(
            $urlRewriteMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateUrlRewriteEditAction();
    }

    /**
     * Validate actions data provider.
     *
     * @return array
     */
    public function validateActionsDataProvider(): array
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ]
        ];
    }

    /**
     * @return void
     */
    public function testValidateAttributeSetActions(): void
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateAttributeSetActions();
    }

    /**
     * @return void
     */
    public function testValidateManageCurrencyRates(): void
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_FETCH_RATES);
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateManageCurrencyRates();
    }

    /**
     * @return void
     */
    public function testValidateTransactionalEmails(): void
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_DELETE);
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateTransactionalEmails();
    }

    /**
     * @return void
     */
    public function testValidatePromoCatalogApplyRules(): void
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validatePromoCatalogApplyRules();
    }

    /**
     * @return void
     */
    public function testPromoCatalogIndexAction(): void
    {
        $controllerMock = $this->getMockBuilder(ActionStub::class)
            ->addMethods(['setDirtyRulesNoticeMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($this->_model, $this->_model->promoCatalogIndexAction($controllerMock));
    }

    /**
     * Test validateNoWebsiteGeneric
     *
     * @param array $denyActions
     * @param string $saveAction
     * @param string $idFieldName
     * @param array|null $websiteIds
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     *
     * @return void
     * @dataProvider validateNoWebsiteGenericDataProvider
     */
    public function testValidateNoWebsiteGeneric(
        array $denyActions,
        string $saveAction,
        string $idFieldName,
        ?array $websiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ): void {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_DELETE);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('getWebsiteIds')->willReturn($websiteIds);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateNoWebsiteGeneric($denyActions, $saveAction, $idFieldName);
    }

    /**
     * Data provider for validateNoWebsiteGeneric method.
     *
     * @return array
     */
    public function validateNoWebsiteGenericDataProvider(): array
    {
        return [
            'hasStoreAccess' => [
                'denyActions' => [Ctrl::ACTION_NEW, Ctrl::ACTION_DELETE],
                'saveAction' => Ctrl::ACTION_SAVE,
                'idFieldName' => 'id',
                'websiteIds' => [1],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'hasNoStoreAccess' => [
                'denyActions' => [Ctrl::ACTION_NEW, Ctrl::ACTION_DELETE],
                'saveAction' => Ctrl::ACTION_SAVE,
                'idFieldName' => 'id',
                'websiteIds' => null,
                'expectedForwardInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ]
        ];
    }

    /**
     * @return void
     */
    public function testBlockCustomerGroupSave(): void
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->blockCustomerGroupSave();
    }

    /**
     * @return void
     */
    public function testBlockIndexAction(): void
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->blockIndexAction();
    }

    /**
     * @return void
     */
    public function testBlockTaxChange(): void
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->blockTaxChange();
    }

    /**
     * Expect for customer Action.
     *
     * @param int|null $id
     * @param int|null $websiteId
     * @param array $roleWebsiteIds
     * @param InvokedCount|InvokedAtLeastOnce $expectedForwardInvoke
     * @param InvokedCount|InvokedAtLeastOnce $getActionNameInvoke
     * @param bool $expectedRedirect
     *
     * @return void
     */
    protected function expectsCustomerAction(
        ?int $id,
        ?int $websiteId,
        array $roleWebsiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        bool $expectedRedirect = false
    ): void {
        $customerMock = $this->getMockBuilder(Customer::class)
            ->addMethods(['getWebsiteId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn($id);
        $customerMock->expects($this->any())->method('load')->with($id)->willReturnSelf();
        $customerMock->expects($this->any())->method('getWebsiteId')->willReturn($websiteId);
        $customerMock->expects($this->any())->method('getId')->willReturn($id);

        $this->_roleMock->expects($this->any())->method('getRelevantWebsiteIds')->willReturn($roleWebsiteIds);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Customer::class
        )->willReturn(
            $customerMock
        );
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);

        if ($expectedRedirect) {
            $url = 'redirectUrl';
            $this->backendUrl->method('getUrl')->willReturn($url);
            $this->expectsRedirect($this->once());
        }
    }

    /**
     * Expect for controller forward action.
     *
     * @param InvokedCount|InvokedAtLeastOnce $expectedForwardInvoke
     * @param InvokedCount|InvokedAtLeastOnce $getActionNameInvokeCount
     *
     * @return void
     */
    protected function expectsForward($expectedForwardInvoke, $getActionNameInvokeCount): void
    {
        $this->_ctrlRequestMock->expects($expectedForwardInvoke)
            ->method('setActionName')->with(Ctrl::ACTION_DENIED)
            ->willReturnSelf();
        $this->_ctrlRequestMock->expects($getActionNameInvokeCount)
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_DENIED);
        $this->_ctrlRequestMock->expects($expectedForwardInvoke)
            ->method('setDispatched')->with(false);
    }

    /**
     * Expect for controller redirect action.
     *
     * @param InvokedCount $expectedRedirectInvoke
     *
     * @return void
     */
    protected function expectsRedirect($expectedRedirectInvoke): void
    {
        $this->responseMock->expects($expectedRedirectInvoke)->method('setRedirect');
    }

    /**
     * @param int $storeId
     */
    private function mockCurrentStore(int $storeId): void
    {
        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);
        $this->_storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);
    }
}
