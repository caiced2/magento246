<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Block\Adminhtml\Customer\Edit\Tab\Reward\Management;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\Config\Share;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Checkbox;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\File\Resolver;
use Magento\Framework\View\Element\Template\File\Validator;
use Magento\Framework\View\LayoutInterface;
use Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\Management\Update;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store;
use Magento\Store\Model\System\StoreFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class UpdateTest extends TestCase
{
    /** @var Update */
    protected $model;

    /** @var Context|MockObject */
    protected $contextMock;

    /** @var Registry|MockObject */
    protected $registryMock;

    /** @var FormFactory|MockObject */
    protected $formFactoryMock;

    /** @var StoreFactory|MockObject */
    protected $storeFactoryMock;

    /** @var CustomerRegistry|MockObject */
    protected $customerRegistryMock;

    /** @var StoreManagerInterface|MockObject */
    protected $storeManagerMock;

    /** @var ManagerInterface|MockObject */
    protected $eventManagerMock;

    /** @var ScopeConfigInterface|MockObject */
    protected $scopeConfigMock;

    /** @var LayoutInterface|MockObject */
    protected $layoutMock;

    /** @var Session|MockObject */
    protected $backendSessionMock;

    /** @var UrlInterface|MockObject */
    protected $urlBuilderMock;

    /** @var State|MockObject */
    protected $appStateMock;

    /** @var Resolver|MockObject */
    protected $resolverMock;

    /** @var Filesystem|MockObject */
    protected $filesystemMock;

    /** @var ReadInterface|MockObject */
    protected $readMock;

    /** @var Validator|MockObject */
    protected $validatorMock;

    /** @var LoggerInterface|MockObject */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->getMock();
        $this->formFactoryMock = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeFactoryMock = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRegistryMock = $this->getMockBuilder(CustomerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $this->eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();
        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->getMock();
        $this->backendSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getCustomerFormData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->getMock();
        $this->appStateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverMock = $this->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->readMock = $this->getMockBuilder(ReadInterface::class)
            ->getMock();
        $this->validatorMock = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->contextMock->expects($this->once())->method('getStoreManager')->willReturn($this->storeManagerMock);
        $this->contextMock->expects($this->once())->method('getEventManager')->willReturn($this->eventManagerMock);
        $this->contextMock->expects($this->once())->method('getScopeConfig')->willReturn($this->scopeConfigMock);
        $this->contextMock->expects($this->once())->method('getLayout')->willReturn($this->layoutMock);
        $this->contextMock->expects($this->once())->method('getBackendSession')->willReturn($this->backendSessionMock);
        $this->contextMock->expects($this->once())->method('getUrlBuilder')->willReturn($this->urlBuilderMock);
        $this->contextMock->expects($this->once())->method('getAppState')->willReturn($this->appStateMock);
        $this->contextMock->expects($this->once())->method('getResolver')->willReturn($this->resolverMock);
        $this->contextMock->expects($this->once())->method('getFilesystem')->willReturn($this->filesystemMock);
        $this->contextMock->expects($this->once())->method('getValidator')->willReturn($this->validatorMock);
        $this->contextMock->expects($this->once())->method('getLogger')->willReturn($this->loggerMock);

        $this->filesystemMock->expects($this->any())->method('getDirectoryRead')->willReturn($this->readMock);

        $this->model = new Update(
            $this->contextMock,
            $this->registryMock,
            $this->formFactoryMock,
            $this->storeFactoryMock,
            $this->customerRegistryMock
        );
    }

    public function testToHtml()
    {
        $customerId = 11;
        $isSingleStoreMode = false;
        $sessionData = [
            'customer' => [
                'entity_id' => $customerId,
            ],
            'reward' => [
                'reward_update_notification' => true,
                'reward_warning_notification' => true,
                'data' => 'values',
            ],
        ];
        $stores = [
            1 => [
                'label' => 'website',
                'value' => 'website',
                'children' => [
                    1 => [
                        'label' => 'store',
                        'value' => 'store',
                        'children' => [
                            1 => [
                                'label' => 'view',
                                'value' => 'view',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var Form|MockObject $formMock */
        $formMock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Fieldset|MockObject $fieldsetMock */
        $fieldsetMock = $this->getMockBuilder(Fieldset::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Checkbox|MockObject $updateFieldMock */
        $updateFieldMock = $this->getMockBuilder(Checkbox::class)
            ->setMethods(['setIsChecked'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Checkbox|MockObject $warningFieldMock */
        $warningFieldMock = $this->getMockBuilder(Checkbox::class)
            ->setMethods(['setIsChecked'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Customer|MockObject $customerMock */
        $customerMock = $this->getMockBuilder(Customer::class)
            ->setMethods(['getWebsiteId', 'getId', 'getSharingConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Store|MockObject $storeMock */
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Share|MockObject $shareMock */
        $shareMock = $this->getMockBuilder(Share::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->exactly(2))
            ->method('addFieldset')
            ->willReturn($fieldsetMock);
        $this->formFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $this->registryMock->expects($this->any())
            ->method('registry')
            ->with(RegistryConstants::CURRENT_CUSTOMER_ID)
            ->willReturn($customerId);

        $this->customerRegistryMock->expects($this->any())
            ->method('retrieve')
            ->with($customerId)
            ->willReturn($customerMock);

        $customerMock->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);
        $customerMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $customerMock->expects($this->any())
            ->method('getSharingConfig')
            ->willReturn($shareMock);

        $this->storeManagerMock->expects($this->any())
            ->method('isSingleStoreMode')
            ->willReturn($isSingleStoreMode);

        $this->storeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getStoresStructure')
            ->willReturn($stores);

        $formMock->expects($this->exactly(2))
            ->method('getElement')
            ->willReturnMap(
                [
                    ['update_notification', $updateFieldMock],
                    ['warning_notification', $warningFieldMock],
                ]
            );

        $this->backendSessionMock->expects($this->once())
            ->method('getCustomerFormData')
            ->willReturn($sessionData);

        $updateFieldMock->expects($this->once())
            ->method('setIsChecked')
            ->with(true);

        $warningFieldMock->expects($this->once())
            ->method('setIsChecked')
            ->with(true);

        $formMock->expects($this->once())
            ->method('addValues')
            ->with(['data' => 'values']);

        $this->model->toHtml();
        $this->assertEquals($formMock, $this->model->getForm());
    }
}
