<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test class for \Magento\GiftRegistry\Model\Entity
 */
namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Data\Address;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ActionValidator\RemoveAction;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\Attribute\Config;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\Item;
use Magento\GiftRegistry\Model\ItemFactory;
use Magento\GiftRegistry\Model\PersonFactory;
use Magento\GiftRegistry\Model\Type;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityTest extends TestCase
{
    /**
     * GiftRegistry instance
     *
     * @var Entity
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_store;

    /**
     * @var MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var MockObject
     */
    private $_transportBuilderMock;

    /**
     * @var MockObject
     */
    protected $itemModelMock;

    /**
     * @var MockObject
     */
    protected $stockRegistryMock;

    /**
     * @var MockObject
     */
    protected $itemFactoryMock;

    /**
     * @var MockObject
     */
    protected $stockItemMock;

    /**
     * @var AddressInterfaceFactory|MockObject
     */
    protected $addressDataFactory;

    /**
     * @var MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject
     */
    private $serializerMock;

    /**
     * @var AddressFactory
     */
    private $addressFactoryMock;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $resource = $this->createMock(\Magento\GiftRegistry\Model\ResourceModel\Entity::class);

        $this->_store = $this->createMock(Store::class);
        $this->_storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->_storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->_store);

        $this->_transportBuilderMock = $this->createMock(TransportBuilder::class);

        $this->_transportBuilderMock->expects($this->any())->method('setTemplateOptions')->willReturnSelf();
        $this->_transportBuilderMock->expects($this->any())->method('setTemplateVars')->willReturnSelf();
        $this->_transportBuilderMock->expects($this->any())->method('addTo')->willReturnSelf();
        $this->_transportBuilderMock->expects($this->any())->method('setFrom')->willReturnSelf();
        $this->_transportBuilderMock->expects($this->any())->method('setTemplateIdentifier')->willReturnSelf();
        $this->_transportBuilderMock->expects($this->any())->method('getTransport')
            ->willReturn($this->getMockForAbstractClass(TransportInterface::class));

        $this->_store->expects($this->any())->method('getId')->willReturn(1);

        $appState = $this->createMock(State::class);

        $eventDispatcher = $this->getMockForAbstractClass(ManagerInterface::class);
        $cacheManager = $this->getMockForAbstractClass(CacheInterface::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $actionValidatorMock = $this->createMock(RemoveAction::class);
        $context = new Context(
            $logger,
            $eventDispatcher,
            $cacheManager,
            $appState,
            $actionValidatorMock
        );
        $giftRegistryData = $this->createPartialMock(Data::class, ['getRegistryLink']);
        $giftRegistryData->expects($this->any())->method('getRegistryLink')->willReturnArgument(0);
        $coreRegistry = $this->createMock(Registry::class);

        $attributeConfig = $this->createMock(Config::class);
        $this->itemModelMock = $this->createMock(Item::class);
        $type = $this->createMock(Type::class);
        $this->stockRegistryMock = $this->createMock(StockRegistry::class);
        $this->stockItemMock = $this->getMockBuilder(StockItemRepository::class)
            ->addMethods(['getIsQtyDecimal'])
            ->disableOriginalConstructor()
            ->getMock();
        $session = $this->createMock(Session::class);

        $this->addressDataFactory = $this->createPartialMock(
            AddressInterfaceFactory::class,
            ['create']
        );
        $quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $customerFactory = $this->createMock(CustomerFactory::class);
        $personFactory = $this->createMock(PersonFactory::class);
        $this->itemFactoryMock = $this->createPartialMock(ItemFactory::class, ['create']);
        $this->addressFactoryMock = $this->createPartialMock(AddressFactory::class, ['create']);
        $productRepository = $this->createMock(ProductRepository::class);
        $dateFactory = $this->createMock(DateTimeFactory::class);
        $escaper = $this->createPartialMock(Escaper::class, ['escapeHtml']);
        $escaper->expects($this->any())->method('escapeHtml')->willReturnArgument(0);
        $mathRandom = $this->createMock(Random::class);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $quoteFactory = $this->createMock(QuoteFactory::class);
        $inlineTranslate = $this->getMockForAbstractClass(StateInterface::class);

        $this->customerRepositoryMock = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);

        $this->serializerMock = $this->createPartialMock(Json::class, ['serialize', 'unserialize']);

        $this->_model = new Entity(
            $context,
            $coreRegistry,
            $giftRegistryData,
            $this->_storeManagerMock,
            $this->_transportBuilderMock,
            $type,
            $attributeConfig,
            $this->itemModelMock,
            $this->stockRegistryMock,
            $session,
            $quoteRepository,
            $customerFactory,
            $personFactory,
            $this->itemFactoryMock,
            $this->addressFactoryMock,
            $this->addressDataFactory,
            $productRepository,
            $dateFactory,
            $escaper,
            $mathRandom,
            $this->scopeConfigMock,
            $inlineTranslate,
            $quoteFactory,
            $this->customerRepositoryMock,
            $resource,
            null,
            [],
            $this->serializerMock
        );
    }

    /**
     * @param array $arguments
     * @param array $expectedResult
     * @dataProvider invalidSenderAndRecipientInfoDataProvider
     */
    public function testSendShareRegistryEmailsWithInvalidSenderAndRecipientInfoReturnsError(
        $arguments,
        $expectedResult
    ) {
        $senderEmail = 'someuser@magento.com';
        $maxRecipients = 3;
        $customerMock = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->customerRepositoryMock->expects($this->once())->method('getById')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getEmail')->willReturn($senderEmail);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($maxRecipients);
        $this->_initSenderInfo($arguments['sender_name'], $arguments['sender_message'], $senderEmail);
        $this->_model->setRecipients($arguments['recipients']);
        $result = $this->_model->sendShareRegistryEmails();

        $this->assertEquals($expectedResult['success'], $result->getIsSuccess());
        $this->assertEquals($expectedResult['error_message'], $result->getErrorMessage());
    }

    public function invalidSenderAndRecipientInfoDataProvider()
    {
        return array_merge($this->_invalidRecipientInfoDataProvider(), $this->_invalidSenderInfoDataProvider());
    }

    /**
     * Retrieve data for invalid sender cases
     *
     * @return array
     */
    protected function _invalidSenderInfoDataProvider()
    {
        return [
            [
                [
                    'sender_name' => null,
                    'sender_message' => 'Hello world',
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'You need to enter sender data.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => null,
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'You need to enter sender data.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'Please add invitees.']
            ],
        ];
    }

    /**
     * Retrieve data for invalid recipient cases
     *
     * @return array
     */
    protected function _invalidRecipientInfoDataProvider()
    {
        return [
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => [['email' => 'invalid_email']]
                ],
                ['success' => false, 'error_message' => 'Please enter a valid invitee email address.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => [['email' => 'john.doe@example.com', 'name' => '']]
                ],
                ['success' => false, 'error_message' => 'Please enter an invitee name.']
            ],
            [
                [
                    'sender_name' => 'John Doe',
                    'sender_message' => 'Hello world',
                    'recipients' => []
                ],
                ['success' => false, 'error_message' => 'Please add invitees.']
            ]
        ];
    }

    /**
     * Initialize sender info
     *
     * @param string $senderName
     * @param string $senderMessage
     * @param string $senderEmail
     * @return void
     */
    protected function _initSenderInfo($senderName, $senderMessage, $senderEmail)
    {
        $this->_model->setSenderName($senderName)->setSenderMessage($senderMessage)->setSenderEmail($senderEmail);
    }

    public function testUpdateItems()
    {
        $modelId = 1;
        $productId = 1;
        $items = [
            1 => ['note' => 'test', 'qty' => 5],
            2 => ['note' => '', 'qty' => 1, 'delete' => 1]
        ];
        $this->_model->setId($modelId);
        $modelMock = $this->getMockBuilder(AbstractModel::class)
            ->addMethods(['getProductId', 'setQty', 'setNote'])
            ->onlyMethods(['getId', 'getEntityId', 'save', 'delete', 'isDeleted'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemFactoryMock->expects($this->exactly(2))->method('create')->willReturn($this->itemModelMock);
        $this->itemModelMock->expects($this->exactly(4))->method('load')->willReturn($modelMock);
        $modelMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $modelMock->expects($this->atLeastOnce())->method('getEntityId')->willReturn(1);
        $modelMock->expects($this->once())->method('getProductId')->willReturn($productId);
        $modelMock->expects($this->once())->method('delete');
        $modelMock->expects($this->once())->method('setQty')->with($items[1]['qty']);
        $modelMock->expects($this->once())->method('setNote')->with($items[1]['note']);
        $modelMock->expects($this->once())->method('save');
        $this->stockRegistryMock->expects($this->once())->method('getStockItem')->with($productId)
            ->willReturn($this->stockItemMock);
        $this->stockItemMock->expects($this->once())->method('getIsQtyDecimal')->willReturn(10);
        $this->assertEquals($this->_model, $this->_model->updateItems($items));
    }

    public function testUpdateItemsWithIncorrectQuantity()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage(
            'The gift registry item quantity is incorrect. Verify the item quantity and try again.'
        );
        $modelId = 1;
        $productId = 1;
        $items = [
            1 => ['note' => 'test', 'qty' => '.1']
        ];
        $this->_model->setId($modelId);
        $modelMock = $this->getMockBuilder(AbstractModel::class)
            ->addMethods(['getProductId'])
            ->onlyMethods(['getId', 'getEntityId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemModelMock->expects($this->once())->method('load')->willReturn($modelMock);
        $modelMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $modelMock->expects($this->atLeastOnce())->method('getEntityId')->willReturn(1);
        $modelMock->expects($this->once())->method('getProductId')->willReturn($productId);
        $this->stockRegistryMock->expects($this->once())->method('getStockItem')->with($productId)
            ->willReturn($this->stockItemMock);
        $this->stockItemMock->expects($this->once())->method('getIsQtyDecimal')->willReturn(0);
        $this->assertEquals($this->_model, $this->_model->updateItems($items));
    }

    public function testUpdateItemsWithIncorrectItemId()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage(
            'The gift registry item ID is incorrect. Verify the gift registry item ID and try again.'
        );
        $modelId = 1;
        $items = [
            1 => ['note' => 'test', 'qty' => '.1']
        ];
        $this->_model->setId($modelId);
        $modelMock = $this->createMock(AbstractModel::class);
        $this->itemModelMock->expects($this->once())->method('load')->willReturn($modelMock);
        $this->assertEquals($this->_model, $this->_model->updateItems($items));
    }

    /**
     * @return array
     */
    public function addressDataProvider()
    {
        return [
            'withoutData' => [null],
            'withData'    => [
                ['street' => 'Baker Street'],
            ]
        ];
    }

    /**
     * @param [] $data
     * @dataProvider addressDataProvider
     */
    public function testExportAddressData($data)
    {
        $this->_model->setData('shipping_address', json_encode($data));
        $this->addressDataFactory->expects($this->once())
            ->method('create')
            ->willReturn(
                $this->getMockBuilder(Address::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );

        $this->assertInstanceOf(Address::class, $this->_model->exportAddressData());
    }

    /**
     * @param $shippingData
     * @param $expectedCalls
     * @dataProvider exportAddressDataProvider
     */
    public function testExportAddress($shippingData, $expectedCalls)
    {
        $this->_model->setData('shipping_address', '[]');

        $this->serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturn($shippingData);

        $address = $this->getMockBuilder(\Magento\Customer\Model\Address::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $address->expects($this->exactly($expectedCalls))
            ->method('setData')
            ->with($shippingData)
            ->willReturn($address);

        $this->addressFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($address);

        $this->_model->exportAddress();
    }

    public function exportAddressDataProvider()
    {
        return [
            [
                'string',
                0,
            ],
            [
                [],
                1,
            ],
        ];
    }
}
