<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Rma\Status;

use Magento\Framework\Event\Manager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Config;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Rma\Model\RmaFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\ResourceModel\Order\Address\Collection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class HistoryTest extends TestCase
{
    /**
     * @var History
     */
    protected $history;

    /**
     * @var Config|MockObject
     */
    protected $rmaConfig;

    /**
     * @var StateInterface|MockObject
     */
    protected $inlineTranslation;

    /**
     * @var TransportBuilder|MockObject
     */
    protected $transportBuilder;

    /**
     * @var Data|MockObject
     */
    protected $rmaHelper;

    /**
     * @var AbstractResource|MockObject
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime|MockObject
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|MockObject
     */
    protected $dateTimeDateTime;

    /**
     * @var Manager|MockObject
     */
    protected $eventManager;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var Order|MockObject
     */
    protected $order;

    /**
     * @var Collection|MockObject
     */
    protected $addressCollection;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $localeDate;

    /**
     * @var RmaFactory|MockObject
     */
    protected $rmaFactory;

    /**
     * @var Rma|MockObject
     */
    protected $rma;

    /**
     * @var MockObject
     */
    protected $rmaRepositoryMock;

    /**
     * @var Renderer|MockObject
     */
    protected $addressRendererMock;

    /**
     * @var Address|MockObject
     */
    protected $addressMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->eventManager = $this->createMock(Manager::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $context = $this->createMock(Context::class);
        $context->expects($this->once())->method('getEventDispatcher')->willReturn($this->eventManager);
        $this->rmaConfig = $this->createPartialMock(
            Config::class,
            [
                'getRootCommentEmail',
                'getCustomerEmailRecipient',
                'getRootCustomerCommentEmail',
                'init',
                'isEnabled',
                'getCopyTo',
                'getCopyMethod',
                'getGuestTemplate',
                'getTemplate',
                'getIdentity',
                'getRootRmaEmail',
                'getRootAuthEmail',

            ]
        );
        $this->rma = $this->createPartialMock(
            Rma::class,
            [
                'getId',
                'getStatus',
                'getStoreId',
                'getOrder',
                'getItemsForDisplay',
                'load', 'getEntityId',
                'getCreatedAtFormated',
                'getStatusLabel'
            ]
        );
        $this->inlineTranslation = $this->getMockForAbstractClass(StateInterface::class);
        $this->transportBuilder = $this->createMock(TransportBuilder::class);
        $this->rmaHelper = $this->createMock(Data::class);
        $this->resource = $this->createMock(\Magento\Rma\Model\ResourceModel\Rma\Status\History::class);
        $this->dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime::class);
        $this->dateTimeDateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->localeDate = $this->createMock(Timezone::class);
        $this->order = $this->createPartialMock(
            Order::class,
            ['getStore', 'getBillingAddress', 'getShippingAddress', 'getAddressesCollection']
        );
        $this->addressCollection = $this->createPartialMock(
            Collection::class,
            ['getItems']
        );
        $this->rmaFactory = $this->getMockBuilder(RmaFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['__wakeup'])
            ->onlyMethods(['create'])
            ->getMock();
        $this->rmaRepositoryMock = $this->getMockForAbstractClass(RmaRepositoryInterface::class);
        $this->addressRendererMock = $this->createMock(Renderer::class);
        $this->addressMock = $this->createMock(Address::class);
        $this->addressRendererMock->expects($this->any())->method('format')->willReturn(1);
        $this->history = $objectManagerHelper->getObject(
            History::class,
            [
                'storeManager' => $this->storeManager,
                'rmaFactory' => $this->rmaFactory,
                'rmaConfig' => $this->rmaConfig,
                'transportBuilder' => $this->transportBuilder,
                'inlineTranslation' => $this->inlineTranslation,
                'rmaHelper' => $this->rmaHelper,
                'resource' => $this->resource,
                'dateTime' => $this->dateTime,
                'dateTimeDateTime' => $this->dateTimeDateTime,
                'localeDate' => $this->localeDate,
                'context' => $context,
                'rmaRepositoryInterface' => $this->rmaRepositoryMock,
                'addressRenderer' => $this->addressRendererMock
            ]
        );
    }

    public function testGetStore()
    {
        $store = $this->createMock(Store::class);
        $this->order->expects($this->once())
            ->method('getStore')
            ->willReturn($store);
        $this->history->setOrder($this->order);

        $this->assertEquals($store, $this->history->getStore());
    }

    public function testGetRma()
    {
        $this->history->setData('rma_entity_id', 10003);
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->with(10003)
            ->willReturn($this->rma);
        $this->assertEquals($this->rma, $this->history->getRma());
    }

    public function testGetStoreNoOrder()
    {
        $store = $this->createMock(Store::class);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);
        $this->assertEquals($store, $this->history->getStore());
    }

    public function testSaveComment()
    {
        $comment = 'comment';
        $visible = true;
        $isAdmin = true;
        $id = 1;
        $status = 'status';
        $emailSent = true;
        $date = 'today';

        $this->prepareSaveComment($id, $status, $date, $emailSent);

        $this->history->saveComment($comment, $visible, $isAdmin);

        $this->assertEquals($comment, $this->history->getComment());
        $this->assertEquals($visible, $this->history->isVisibleOnFront());
        $this->assertEquals($isAdmin, $this->history->isAdmin());
        $this->assertEquals($emailSent, $this->history->isCustomerNotified());
        $this->assertEquals($date, $this->history->getCreatedAt());
        $this->assertEquals($status, $this->history->getStatus());
    }

    public function testSendNewRmaEmail()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';
        $this->order->setCustomerEmail($customerEmail);
        $this->prepareRmaModel($storeId, $name, $customerEmail);

        $this->stepAddressFormat();

        $this->rma->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->prepareTransportBuilder();

        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $store = $this->getStore();
        $store->expects($this->any())->method('getConfig')->willReturn('support@example.com');
        $this->transportBuilder->expects($this->once())->method('addTo');
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendNewRmaEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    /**
     * Initializate and return store.
     *
     * @return Store
     */
    private function getStore(): Store
    {
        $store = $this->createMock(Store::class);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        return $store;
    }

    public function testSendAuthorizeEmail()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';
        $this->stepAddressFormat();
        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('bcc');
        $this->prepareTransportBuilder();

        $this->order->setCustomerEmail($customerEmail);
        $this->order->setCustomerIsGuest(false);
        $store = $this->getStore();
        $store->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturnMap(
                [
                    ['trans_email/ident_support/email', 'support@example.com'],
                    ['general/store_information/phone', '+1234567890'],
                ]
            );
        $this->history->setRma($this->rma);
        $this->assertNull($this->history->getEmailSent());

        $this->history->sendAuthorizeEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendAuthorizeEmailGuest()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';
        $this->stepAddressFormat();

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('copy');
        $this->prepareTransportBuilder();

        $this->order->setCustomerIsGuest(true);
        $this->addressMock->expects($this->once())
            ->method('getName')
            ->willReturn($name);
        $store = $this->getStore();
        $store->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturnMap(
                [
                    ['trans_email/ident_support/email', 'support@example.com'],
                    ['general/store_information/phone', '+1234567890'],
                ]
            );
        $this->history->sendAuthorizeEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    protected function prepareTransportBuilder()
    {
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setTemplateIdentifier')->willReturnSelf();
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setTemplateOptions')->willReturnSelf();
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setTemplateVars')->willReturnSelf();
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('setFromByScope')->willReturnSelf();
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('addTo')->willReturnSelf();
        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('addBcc')->willReturnSelf();

        $transport = $this->getMockForAbstractClass(TransportInterface::class);
        $transport->expects($this->atLeastOnce())
            ->method('sendMessage');

        $this->transportBuilder->expects($this->atLeastOnce())
            ->method('getTransport')
            ->willReturn($transport);
    }

    /**
     * @param string $copyMethod
     */
    protected function prepareRmaConfig($copyMethod)
    {
        $template = 'some html';
        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        if ($copyMethod == 'bcc') {
            $copyTo = 'copyTo';
        } else {
            $copyTo = ['email@com.com'];
        }
        $this->rmaConfig->expects($this->once())
            ->method('getCopyTo')
            ->willReturn($copyTo);
        $this->rmaConfig->expects($this->once())
            ->method('getCopyMethod')
            ->willReturn($copyMethod);
        if ($this->order->getCustomerIsGuest()) {
            $this->rmaConfig->expects($this->once())
                ->method('getGuestTemplate')
                ->willReturn($template);
        }
    }

    /**
     * @param $storeId
     * @param $name
     * @param $customerEmail
     */
    protected function prepareRmaModel($storeId, $name, $customerEmail)
    {
        $this->rma->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn($storeId);
        $this->rma->expects($this->atLeastOnce())
            ->method('getOrder')
            ->willReturn($this->order);
        $this->rma->method('getCreatedAtFormated')
            ->willReturn($this->dateTime);
        $this->rma->method('getStatusLabel')
            ->willReturn('status label');
        $this->rma->setCustomerName($name);
        $this->rma->setCustomerCustomEmail($customerEmail);
        $this->rma->setIsSendAuthEmail(true);
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
    }

    public function testSendCommentEmail()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('bcc');
        $this->prepareTransportBuilder();

        $this->order->setCustomerEmail($customerEmail);
        $this->order->setCustomerName($name);
        $this->order->setCustomerIsGuest(false);
        $this->history->setRma($this->rma);
        $store = $this->getStore();
        $store->expects($this->once())->method('getConfig')->willReturn('support@example.com');
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendCommentEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendCommentEmailGuest()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('copy');
        $this->prepareTransportBuilder();

        $address = $this->createMock(Address::class);
        $address->expects($this->once())
            ->method('getName')
            ->willReturn($name);
        $this->order->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($address);

        $this->order->setCustomerEmail($customerEmail);
        $this->order->setCustomerName($name);
        $this->order->setCustomerIsGuest(true);
        $this->history->setRma($this->rma);
        $store = $this->getStore();
        $store->expects($this->any())->method('getConfig')->willReturn('support@example.com');
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendCommentEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendCustomerCommentEmail()
    {
        $storeId = 5;
        $customerEmail = 'custom@email.com';
        $name = 'name';
        $commentRoot = 'sales_email/magento_rma_customer_comment';

        $this->prepareRmaModel($storeId, $name, $customerEmail);
        $this->prepareRmaConfig('bcc');
        $this->rmaConfig->expects($this->once())
            ->method('getCustomerEmailRecipient')
            ->with($storeId)
            ->willReturn($customerEmail);
        $this->rmaConfig->expects($this->once())
            ->method('getRootCustomerCommentEmail')
            ->willReturn($commentRoot);
        $this->prepareTransportBuilder();

        $this->order->setCustomerIsGuest(false);
        $this->history->setRma($this->rma);
        $store = $this->getStore();
        $store->expects($this->once())->method('getConfig')->willReturn('support@example.com');
        $this->assertNull($this->history->getEmailSent());
        $this->history->sendCustomerCommentEmail();
        $this->assertTrue($this->history->getEmailSent());
    }

    public function testSendCustomerCommentEmailDisabled()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->assertEquals($this->history, $this->history->sendCustomerCommentEmail());
    }

    public function testSendAuthorizeEmailNotSent()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rma->setIsSendAuthEmail(false);
        $this->assertEquals($this->history, $this->history->sendAuthorizeEmail());
        $this->assertNull($this->history->getEmailSent());
    }

    public function testSendRmaEmailWithItemsDisabled()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rma->setIsSendAuthEmail(true);
        $this->rmaConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->assertEquals($this->history, $this->history->sendAuthorizeEmail());
    }

    public function testSendAuthorizeEmailFail()
    {
        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->rma->setIsSendAuthEmail(false);
        $this->assertEquals($this->history, $this->history->sendAuthorizeEmail());
    }

    public function testGetCreatedAtDate()
    {
        $date = '2015-01-02 03:04:05';
        $dateObject = new \DateTime($date);
        $datetime = $dateObject->format('Y-m-d H:i:s');
        $this->localeDate->expects($this->once())
            ->method('date')
            ->with($dateObject, null, true)
            ->willReturn($datetime);

        $this->history->setCreatedAt($date);
        $this->assertEquals($datetime, $this->history->getCreatedAtDate());
    }

    /**
     * @dataProvider statusProvider
     * @param string $status
     * @param string $expected
     */
    public function testGetSystemCommentByStatus($status, $expected)
    {
        $this->assertEquals($expected, History::getSystemCommentByStatus($status));
    }

    public function statusProvider()
    {
        return [
            [Status::STATE_PENDING, __('We placed your Return request.')],
            [Status::STATE_AUTHORIZED, __('We authorized your Return request.')],
            [Status::STATE_PARTIAL_AUTHORIZED, __('We partially authorized your Return request.')],
            [Status::STATE_RECEIVED, __('We received your Return request.')],
            [Status::STATE_RECEIVED_ON_ITEM, __('We partially received your Return request.')],
            [Status::STATE_APPROVED_ON_ITEM, __('We partially approved your Return request.')],
            [Status::STATE_REJECTED_ON_ITEM, __('We partially rejected your Return request.')],
            [Status::STATE_CLOSED, __('We closed your Return request.')],
            [Status::STATE_PROCESSED_CLOSED, __('We processed and closed your Return request.')]
        ];
    }

    /**
     * @param $id
     * @param $status
     * @param $date
     * @param $emailSent
     */
    protected function prepareSaveComment($id, $status, $date, $emailSent)
    {
        $this->rma->expects($this->once())
            ->method('getEntityId')
            ->willReturn($id);
        $this->rma->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn($status);

        $this->dateTimeDateTime->expects($this->once())
            ->method('gmtDate')
            ->willReturn($date);

        $this->resource->expects($this->once())
            ->method('save')
            ->with($this->history);

        $this->rmaRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->rma);
        $this->history->setEmailSent($emailSent);
    }

    public function testSaveSystemComment()
    {
        $id = 1;
        $status = 'status';
        $emailSent = true;
        $date = 'today';
        $this->rma->setStatus($status);
        $this->prepareSaveComment($id, $status, $date, $emailSent);

        $this->history->saveSystemComment();

        $this->assertEquals($emailSent, $this->history->isCustomerNotified());
        $this->assertEquals($date, $this->history->getCreatedAt());
        $this->assertEquals($status, $this->history->getStatus());
    }

    private function stepAddressFormat()
    {
        $this->order->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);
        $this->order->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->addressMock);
        $this->order->expects($this->any())
            ->method('getAddressesCollection')
            ->willReturn($this->addressCollection);
        $this->addressCollection->expects($this->any())->method('getItems')->willReturn([$this->addressMock]);
    }
}
