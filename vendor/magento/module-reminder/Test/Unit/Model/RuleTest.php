<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Model;

use Magento\Customer\Model\Customer;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Quote\Model\QueryResolver;
use Magento\Reminder\Helper\Data;
use Magento\Reminder\Model\Rule;
use Magento\Reminder\Model\Rule\Condition\Combine\RootFactory;
use Magento\Rule\Model\Action\Collection;
use Magento\Rule\Model\Action\CollectionFactory;
use Magento\Rule\Model\Condition\Combine;
use Magento\SalesRule\Model\Coupon;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class RuleTest extends TestCase
{
    /**
     * @var Rule
     */
    protected $rule;

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
     * @var TimezoneInterface|MockObject
     */
    protected $timezoneMock;

    /**
     * @var \Magento\Reminder\Model\Rule\Condition\Combine\RootFactory|MockObject
     */
    protected $rootFactoryMock;

    /**
     * @var \Magento\Rule\Model\Action\CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var \Magento\Customer\Model\CustomerFactory|MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory|MockObject
     */
    protected $couponFactoryMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory|MockObject
     */
    protected $dateTimeFactoryMock;

    /**
     * @var \Magento\SalesRule\Model\Rule|MockObject
     */
    protected $ruleMock;

    /**
     * @var \Magento\Reminder\Helper\Data|MockObject
     */
    protected $reminderHelperMock;

    /**
     * @var \Magento\Reminder\Model\ResourceModel\Rule|MockObject
     */
    protected $ruleResourceMock;

    /**
     * @var TransportBuilder|MockObject
     */
    protected $transportBuilderMock;

    /**
     * @var StateInterface|MockObject
     */
    protected $stateMock;

    /**
     * @var QueryResolver|MockObject
     */
    protected $queryResolverMock;

    /**
     * @var Manager|MockObject
     */
    protected $eventManagerMock;

    /**
     * @var AbstractDb|MockObject
     */
    protected $dbMock;

    /**
     * @var Customer|MockObject
     */
    protected $customerMock;

    /**
     * @var Coupon|MockObject
     */
    protected $couponMock;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->eventManagerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())->method('getEventDispatcher')->willReturn($this->eventManagerMock);

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->getMock();
        $this->formFactoryMock = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->getMock();
        $this->rootFactoryMock = $this->getMockBuilder(RootFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->customerFactoryMock = $this->getMockBuilder(\Magento\Customer\Model\CustomerFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $this->couponFactoryMock = $this->getMockBuilder(\Magento\SalesRule\Model\CouponFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->dateTimeFactoryMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->ruleMock = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reminderHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleResourceMock = $this->getMockBuilder(\Magento\Reminder\Model\ResourceModel\Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportBuilderMock = $this->getMockBuilder(TransportBuilder::class)
            ->onlyMethods(
                [
                    'setTemplateIdentifier',
                    'setTemplateOptions',
                    'setTemplateVars',
                    'setFrom',
                    'addTo',
                    'getTransport'
                ]
            )
            ->addMethods(['sendMessage'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateMock = $this->getMockBuilder(StateInterface::class)
            ->getMock();
        $this->queryResolverMock = $this->getMockBuilder(QueryResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dbMock = $this->getMockBuilder(AbstractDb::class)->disableOriginalConstructor()
            ->onlyMethods(['getResource', 'getSelect', '_fetchAll'])
            ->addMethods(['addDateFilter', 'addIsActiveFilter'])
            ->getMock();
        $this->dbMock->expects($this->any())->method('addDateFilter')->willReturnSelf();
        $this->dbMock->expects($this->any())->method('addIsActiveFilter')->willReturnSelf();
        $this->dbMock->expects($this->any())->method('getSelect')->willReturn($selectMock);

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->onlyMethods(['load', 'getId', 'getStore', 'getName'])
            ->addMethods(['getStoreId', 'getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);

        $extensionAttributeFactoryMock = $this->createMock(ExtensionAttributesFactory::class);
        $attributeValueFactoryMock = $this->createMock(AttributeValueFactory::class);

        $this->prepareObjectManager(
            [
                [
                    ExtensionAttributesFactory::class,
                    $extensionAttributeFactoryMock
                ],
                [
                    AttributeValueFactory::class,
                    $attributeValueFactoryMock
                ],
                [
                    Json::class,
                    $this->getSerializerMock()
                ]
            ]
        );

        $this->rule = new Rule(
            $this->contextMock,
            $this->registryMock,
            $this->formFactoryMock,
            $this->timezoneMock,
            $this->rootFactoryMock,
            $this->collectionFactoryMock,
            $this->customerFactoryMock,
            $this->storeManagerMock,
            $this->couponFactoryMock,
            $this->dateTimeFactoryMock,
            $this->ruleMock,
            $this->reminderHelperMock,
            $this->ruleResourceMock,
            $this->transportBuilderMock,
            $this->stateMock,
            $this->queryResolverMock,
            $this->dbMock
        );
    }

    /**
     * Get mock for serializer.
     *
     * @return Json|MockObject
     */
    private function getSerializerMock(): Json
    {
        $serializerMock = $this->getMockBuilder(Json::class)->disableOriginalConstructor()
            ->onlyMethods(['serialize', 'unserialize'])
            ->getMock();

        $serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        return $serializerMock;
    }

    /**
     * @return void
     */
    protected function setSavePreconditions(): void
    {
        $conditionMock = $this->getMockBuilder(Combine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['asArray'])
            ->addMethods(['setRule', 'setId', 'setPrefix'])
            ->getMock();
        $conditionMock->expects($this->once())->method('setRule')->willReturnSelf();
        $conditionMock->expects($this->once())->method('setId')->willReturnSelf();
        $conditionMock->expects($this->once())->method('setPrefix')->willReturnSelf();
        $conditionMock->expects($this->once())->method('asArray')->willReturn([]);

        $this->rootFactoryMock->expects($this->any())->method('create')->willReturn($conditionMock);

        $actionMock = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()
            ->onlyMethods(['asArray'])
            ->addMethods(['setRule', 'setId', 'setPrefix'])
            ->getMock();
        $actionMock->expects($this->once())->method('setRule')->willReturnSelf();
        $actionMock->expects($this->once())->method('setId')->willReturnSelf();
        $actionMock->expects($this->once())->method('setPrefix')->willReturnSelf();
        $actionMock->expects($this->once())->method('asArray')->willReturn([]);

        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($actionMock);

        $this->eventManagerMock
            ->method('dispatch')
            ->withConsecutive(
                ['model_save_before'],
                ['core_abstract_save_before']
            );
    }

    /**
     * @return void
     */
    public function testBeforeSaveNew(): void
    {
        $this->setSavePreconditions();

        $this->assertTrue($this->rule->isObjectNew());
        $this->rule->beforeSave();
    }

    /**
     * @return void
     */
    public function testBeforeSaveExisting(): void
    {
        $this->setSavePreconditions();

        $this->rule->setId(1);
        $this->rule->setSalesruleId(1);

        $this->assertFalse($this->rule->isObjectNew());
        $this->rule->beforeSave();
    }

    /**
     * @return void
     */
    public function testGetConditionsInstance(): void
    {
        $conditionMock = $this->getMockBuilder(Combine::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rootFactoryMock->expects($this->once())->method('create')->willReturn($conditionMock);

        $this->assertInstanceOf(Combine::class, $this->rule->getConditionsInstance());
    }

    /**
     * @return void
     */
    public function testGetActionsInstance(): void
    {
        $actionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($actionMock);

        $this->assertInstanceOf(Collection::class, $this->rule->getActionsInstance());
    }

    /**
     * @param string $label
     * @param string $description
     *
     * @return array
     */
    protected function setSendReminderEmailsPreconditions(
        string $label = 'label',
        string $description = 'description'
    ): array {
        $storeId = 11;
        $customerId = 1;
        $ruleId = 2;
        $couponId = 3;
        $storeData = ['template_id' => 0, 'label' => $label, 'description' => $description];
        $recipient = ['customer_id' => $customerId, 'rule_id' => $ruleId, 'coupon_id' => $couponId];

        $this->transportBuilderMock->expects($this->once())->method('setTemplateIdentifier')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setTemplateOptions')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setTemplateVars')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('setFrom')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('addTo')->willReturnSelf();
        $this->transportBuilderMock->expects($this->once())->method('getTransport')->willReturnSelf();

        $this->couponMock = $this->getMockBuilder(Coupon::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->couponMock->expects($this->once())->method('load')->with($couponId)->willReturnSelf();

        $this->couponFactoryMock->expects($this->once())->method('create')->willReturn($this->couponMock);

        $dateMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($dateMock);

        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getCustomersForNotification')
            ->willReturn([$recipient]);
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getStoreTemplateData')
            ->with($ruleId, $storeId)
            ->willReturn($storeData);

        $this->customerMock->expects($this->once())->method('load')->with($customerId)->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getId')->willReturn($customerId);

        $this->dbMock->expects($this->any())->method('_fetchAll')->willReturn([]);

        $this->rule->setDefaultLabel('default label');
        $this->rule->setDefaultDescription('default description');

        return ['customer_id' => $customerId, 'rule_id' => $ruleId, 'coupon_id' => $couponId, 'store_id' => $storeId];
    }

    /**
     * Run test SendReminderEmails.
     *
     * @param string $storeLabel
     * @param string $storeDescription
     * @param string $resultLabel
     * @param string $resultDescription
     *
     * @return void
     * @dataProvider storeDataProvider
     */
    public function testSendReminderEmails(
        string $storeLabel,
        string $storeDescription,
        string $resultLabel,
        string $resultDescription
    ): void {
        $result = $this->setSendReminderEmailsPreconditions($storeLabel, $storeDescription);

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('getId')->willReturn($result['store_id']);

        $this->customerMock->expects($this->once())->method('getStoreId')->willReturn($result['store_id']);
        $this->customerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $this->customerMock->method('getName')->willReturn('name');

        $this->transportBuilderMock->expects($this->once())->method('sendMessage')->willReturnSelf();

        $templateVars = [
            'store' => $storeMock,
            'coupon' => $this->couponMock,
            'customer' => $this->customerMock,
            'customer_data' => [
                'name' => $this->customerMock->getName()
            ],
            'promotion_name' => $resultLabel,
            'promotion_description' => $resultDescription
        ];

        $this->transportBuilderMock->expects($this->once())
            ->method('setTemplateVars')
            ->with($templateVars)
            ->willReturnSelf();

        $this->ruleResourceMock
            ->expects($this->once())
            ->method('addNotificationLog')
            ->with($result['rule_id'], $result['customer_id']);

        $this->rule->sendReminderEmails();
    }

    /**
     * @return void
     */
    public function testSendReminderEmailsWithException(): void
    {
        $result = $this->setSendReminderEmailsPreconditions();

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('getId')->willReturn($result['store_id']);

        $this->customerMock->expects($this->once())->method('getStoreId')->willReturn(0);
        $this->customerMock->expects($this->once())->method('getWebsiteId')->willReturn(1);

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();
        $websiteMock->expects($this->once())->method('getDefaultStore')->willReturn($storeMock);

        $this->storeManagerMock->expects($this->once())->method('getWebsite')->willReturn($websiteMock);

        $phrase = new Phrase('text');

        $this->transportBuilderMock
            ->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(
                new MailException($phrase)
            );

        $this->ruleResourceMock
            ->expects($this->once())
            ->method('updateFailedEmailsCounter')
            ->with($result['rule_id'], $result['customer_id']);

        $this->rule->sendReminderEmails();
    }

    /**
     * @return void
     */
    public function testGetStoreData(): void
    {
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getStoreTemplateData')
            ->with(1, 2)
            ->willReturn(['template_id' => 0]);
        $this->assertEquals(['template_id' => 'magento_reminder_email_template'], $this->rule->getStoreData(1, 2));
    }

    /**
     * @return void
     */
    public function testGetStoreDataWithNull(): void
    {
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('getStoreTemplateData')
            ->with(1, 2)
            ->willReturn(null);
        $this->assertFalse($this->rule->getStoreData(1, 2));
    }

    /**
     * @return void
     */
    public function testDetachSalesRule(): void
    {
        $salesRuleId = 1;
        $this->ruleResourceMock
            ->expects($this->once())
            ->method('detachSalesRule')
            ->with($salesRuleId);
        $this->rule->detachSalesRule($salesRuleId);
    }

    /**
     * Data provider for test.
     *
     * @return array
     */
    public function storeDataProvider(): array
    {
        return [
            'case1' => [
                'storeLabel' => 'label',
                'storeDescription' => 'description',
                'resultLabel' => 'label',
                'resultDescription' => 'description'
            ],
            'case2' => [
                'storeLabel' => '',
                'storeDescription' => '',
                'resultLabel' => 'default label',
                'resultDescription' => 'default description'
            ]
        ];
    }

    /**
     * @param array $map
     *
     * @return void
     */
    private function prepareObjectManager(array $map): void
    {
        $objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->addMethods(['getInstance'])
            ->getMockForAbstractClass();
        $objectManagerMock->expects($this->any())->method('getInstance')->willReturnSelf();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap($map);
        $reflectionClass = new \ReflectionClass(ObjectManager::class);
        $reflectionProperty = $reflectionClass->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($objectManagerMock);
    }
}
