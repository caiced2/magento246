<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Block\Adminhtml\Customer\Edit\Tab\Customerbalance;

use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance\Form;
use Magento\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance\Js;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
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
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class FormTest extends TestCase
{
    /**
     * @var Form
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var FormFactory|MockObject
     */
    protected $formFactoryMock;

    /**
     * @var CustomerFactory|MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManagerMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layoutMock;

    /**
     * @var Session|MockObject
     */
    protected $backendSessionMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var State|MockObject
     */
    protected $appStateMock;

    /**
     * @var Resolver|MockObject
     */
    protected $resolverMock;

    /**
     * @var Filesystem|MockObject
     */
    protected $filesystemMock;

    /**
     * @var ReadInterface|MockObject
     */
    protected $readMock;

    /**
     * @var Validator|MockObject
     */
    protected $validatorMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @inheritDoc
     */
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
        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
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
            ->addMethods(['getCustomerFormData'])
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

        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
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

        $this->model = new Form(
            $this->contextMock,
            $this->registryMock,
            $this->formFactoryMock,
            $this->customerFactoryMock,
            $this->storeMock
        );
    }

    /**
     * @return void
     */
    public function testToHtml(): void
    {
        $customerId = 11;
        $isSingleStoreMode = false;
        $sessionData = [
            'customer' => [
                'entity_id' => $customerId
            ],
            'customerbalance' => [
                'notify_by_email' => true,
                'data' => 'values'
            ]
        ];

        /** @var \Magento\Framework\Data\Form|MockObject $formMock */
        $formMock = $this->getMockBuilder(\Magento\Framework\Data\Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Fieldset|MockObject $fieldsetMock */
        $fieldsetMock = $this->getMockBuilder(Fieldset::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Checkbox|MockObject $notifyFieldMock */
        $notifyFieldMock = $this->getMockBuilder(Checkbox::class)
            ->onlyMethods(['setIsChecked'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Customer|MockObject $customerMock */
        $customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Element|MockObject $rendererMock */
        $rendererMock = $this->getMockBuilder(
            Element::class
        )->disableOriginalConstructor()
            ->getMock();
        /** @var Js|MockObject $jsMock */
        $jsMock = $this->getMockBuilder(
            Js::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->once())
            ->method('addFieldset')
            ->willReturn($fieldsetMock);
        $this->formFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id', null)
            ->willReturn($customerId);

        $this->customerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($customerMock);

        $customerMock->expects($this->once())
            ->method('load')
            ->with($customerId)
            ->willReturnSelf();
        $customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->storeManagerMock->expects($this->any())
            ->method('isSingleStoreMode')
            ->willReturn($isSingleStoreMode);

        $fieldsetMock
            ->method('addField')
            ->willReturn($notifyFieldMock);

        $formMock->expects($this->once())
            ->method('getElement')
            ->willReturnMap(
                [
                    ['notify_by_email', $notifyFieldMock],
                ]
            );

        $this->layoutMock->expects($this->exactly(2))
            ->method('createBlock')
            ->willReturnMap(
                [
                    [
                        Element::class,
                        '',
                        [],
                        $rendererMock
                    ],
                    [
                        Js::class,
                        'customerbalance_edit_js',
                        [],
                        $jsMock
                    ]
                ]
            );

        $this->backendSessionMock->expects($this->once())
            ->method('getCustomerFormData')
            ->willReturn($sessionData);

        $notifyFieldMock->expects($this->once())
            ->method('setIsChecked')
            ->with(true);

        $formMock->expects($this->once())
            ->method('addValues')
            ->with(['data' => 'values']);

        $this->model->toHtml();
        $this->assertEquals($formMock, $this->model->getForm());
    }
}
