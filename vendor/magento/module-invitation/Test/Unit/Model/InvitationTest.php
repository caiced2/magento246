<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Invitation\Helper\Data;
use Magento\Invitation\Model\Config;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\Invitation\HistoryFactory;
use Magento\Invitation\Model\Invitation\Status;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InvitationTest extends TestCase
{
    /** @var MockObject  */
    private $context;

    /** @var MockObject  */
    private $registry;

    /** @var MockObject  */
    private $invitationData;

    /** @var MockObject  */
    private $resource;

    /** @var MockObject  */
    private $storeManager;

    /** @var MockObject  */
    private $config;

    /** @var MockObject  */
    private $historyFactory;

    /** @var MockObject  */
    private $customerFactory;

    /** @var MockObject  */
    private $transactionBuilder;

    /** @var MockObject  */
    private $mathRandom;

    /** @var MockObject  */
    private $dateTime;

    /** @var MockObject  */
    private $scopeConfig;

    /** @var MockObject  */
    private $invitationStatus;

    /** @var MockObject  */
    private $customerRepository;

    /** @var Invitation  */
    private $invitation;

    /** @var  CustomerInterface|MockObject */
    private $customer;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->invitationData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resource = $this->getMockBuilder(\Magento\Invitation\Model\ResourceModel\Invitation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyFactory = $this
            ->getMockBuilder(HistoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->customerFactory = $this
            ->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->transactionBuilder = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mathRandom = $this->getMockBuilder(Random::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTime = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->invitationStatus = $this->getMockBuilder(Status::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this
            ->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["save", "getById", "get", "getList", "deleteById", "delete"])
            ->getMockForAbstractClass();

        $this->customer = $this
            ->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $helper = new ObjectManager($this);

        $this->invitation = $helper->getObject(Invitation::class, [
            "context" => $this->context,
            "registry" => $this->registry,
            "invitationData" => $this->invitationData,
            "resource" => $this->resource,
            "storeManager" => $this->storeManager,
            "config" => $this->config,
            "historyFactory" => $this->historyFactory,
            "customerFactory" => $this->customerFactory,
            "transactionBuilder" => $this->transactionBuilder,
            "mathRandom" => $this->mathRandom,
            "dateTime" => $this->dateTime,
            "scopeConfig" => $this->scopeConfig,
            "status" => $this->invitationStatus
        ]);

        $helper->setBackwardCompatibleProperty(
            $this->invitation,
            "customerRepository",
            $this->customerRepository
        );
    }

    public function testAssignInvitationDataToCustomer()
    {
        $customerId = 1;
        $customerGroupId = 2;

        $this->invitation->setGroupId($customerGroupId); //setting group id

        $this->customer->expects($this->any())
            ->method("getId")
            ->willReturn($customerId);
        $this->customer->expects($this->once())
            ->method("setGroupId")
            ->with($customerGroupId);

        $this->customerRepository->expects($this->once())
            ->method("getById")
            ->with($customerId)
            ->willReturn($this->customer);

        $this->customerRepository->expects($this->once())
            ->method("save")
            ->with($this->customer);

        $this->invitation->assignInvitationDataToCustomer($customerId);
    }

    public function testAcceptWithInputExceptionWhenMissingInvitationId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $websiteId  = 1;
        $referalId  = 1;

        $this->invitation->accept($websiteId, $referalId);
    }

    public function testAcceptWithInputExceptionWhenStatusIsIncorrect()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $websiteId  = 1;
        $referalId  = 1;
        $newStatus = Status::STATUS_NEW;
        $acceptedStatus = Status::STATUS_ACCEPTED;

        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$acceptedStatus]);
        $this->invitation->setStatus($newStatus);
        $this->invitation->setId(1);
        $this->invitation->accept($websiteId, $referalId);
    }

    public function testAcceptWithInputExceptionWhenWebsiteIdIsIncorrect()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $websiteId  = 1;
        $anotherWebsiteId = 14;
        $referalId  = 1;
        $newStatus = Status::STATUS_NEW;
        $storeId = 1;

        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$newStatus]);
        $this->invitation->setStatus($newStatus);
        $this->invitation->setId(1);

        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())
            ->method("getId")
            ->willReturn($storeId);
        $store->expects($this->atLeastOnce())
            ->method("getWebsiteId")
            ->willReturn($anotherWebsiteId);
        $this->storeManager->expects($this->atLeastOnce())
            ->method("getStore")
            ->willReturn($store);

        $this->invitation->accept($websiteId, $referalId);
    }

    public function provideCustomerId()
    {
        return [
            [12],
            ["12"]
        ];
    }

    /**
     * @dataProvider provideCustomerId
     */
    public function testAcceptWithNoSuchCustomerEntity($referalId)
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $invitationId = 4;
        $websiteId  = 1;
        $customerId = 2;
        $storeId    = 1;
        $newStatus = Status::STATUS_NEW;
        $customerGroupId = 2;

        $this->invitation->setGroupId($customerGroupId); //setting group id

        $this->customer->expects($this->any())
            ->method("getId")
            ->willReturn(null);

        $this->customerRepository->expects($this->once())
            ->method("getById")
            ->with($referalId)
            ->willReturn($this->customer);

        $this->invitation->setStatus($newStatus);
        $this->invitation->setCustomerId($customerId);
        $this->invitation->setId($invitationId);

        $this->resource->expects($this->once())
            ->method("trackReferral")
            ->with($customerId, $referalId);
        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$newStatus]);
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())
            ->method("getId")
            ->willReturn($storeId);
        $store->expects($this->atLeastOnce())
            ->method("getWebsiteId")
            ->willReturn($websiteId);
        $this->storeManager->expects($this->atLeastOnce())
            ->method("getStore")
            ->willReturn($store);

        $this->invitation->accept($websiteId, $referalId);
    }

    public function testAcceptWithTrackReferal()
    {
        $invitationId = 4;
        $websiteId  = 1;
        $referalId  = 1;
        $customerId = 2;
        $storeId    = 1;
        $newStatus = Status::STATUS_NEW;
        $customerGroupId = 2;

        $this->invitation->setGroupId($customerGroupId); //setting group id

        $this->customer->expects($this->any())
            ->method("getId")
            ->willReturn($referalId);
        $this->customer->expects($this->once())
            ->method("setGroupId")
            ->with($customerGroupId);

        $this->customerRepository->expects($this->once())
            ->method("getById")
            ->with($referalId)
            ->willReturn($this->customer);

        $this->invitation->setStatus($newStatus);
        $this->invitation->setCustomerId($customerId);
        $this->invitation->setId($invitationId);

        $this->resource->expects($this->once())
            ->method("trackReferral")
            ->with($customerId, $referalId);
        $this->invitationStatus->expects($this->atLeastOnce())
            ->method("getCanBeAcceptedStatuses")
            ->willReturn([$newStatus]);
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())
            ->method("getId")
            ->willReturn($storeId);
        $store->expects($this->atLeastOnce())
            ->method("getWebsiteId")
            ->willReturn($websiteId);
        $this->storeManager->expects($this->atLeastOnce())
            ->method("getStore")
            ->willReturn($store);

        $this->invitation->accept($websiteId, $referalId);
    }
}
