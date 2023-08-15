<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Catalog\Product\Type;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Configuration\Item\OptionFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Option;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftcardTest extends TestCase
{
    /**
     * @var Giftcard
     */
    protected $_model;

    /**
     * @var array
     */
    protected $_customOptions;

    /**
     * @var Product
     */
    protected $_productResource;

    /**
     * @var Option
     */
    protected $_optionResource;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var Store
     */
    protected $_store;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManagerMock;

    /**
     * @var \Magento\Quote\Model\Quote\Item\Option
     */
    protected $_quoteItemOption;

    /**
     * Serializer interface instance.
     *
     * @var Json
     */
    private $serializer;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->_store = $this->createPartialMock(
            Store::class,
            ['getCurrentCurrencyRate', '__sleep']
        );
        $this->_storeManagerMock = $this->getMockBuilder(
            StoreManagerInterface::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['getStore']
            )->getMockForAbstractClass();
        $this->_storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->_store);
        $this->_mockModel(['_isStrictProcessMode']);
    }

    /**
     * Create model Mock
     *
     * @param $mockedMethods
     */
    protected function _mockModel($mockedMethods)
    {
        $eventManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $productRepository = $this->getMockForAbstractClass(ProductRepositoryInterface::class);
        $filesystem =
            $this->getMockBuilder(Filesystem::class)
                ->disableOriginalConstructor()
                ->getMock();
        $storage = $this->getMockBuilder(
            Database::class
        )->disableOriginalConstructor()
            ->getMock();
        $locale = $this->createPartialMock(Format::class, ['getNumber']);
        $locale->expects($this->any())->method('getNumber')->willReturnArgument(0);
        $coreRegistry = $this->createMock(Registry::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $productOption = $this->createMock(\Magento\Catalog\Model\Product\Option::class);
        $eavConfigMock = $this->createMock(Config::class);
        $productTypeMock = $this->createMock(Type::class);
        $priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->getMock();
        $priceCurrency->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($price) {
                    return round($price, 2);
                }
            );
        $this->serializer = $this->getMockBuilder(Json::class)
            ->setMethods(['unserialize'])
            ->getMockForAbstractClass();
        $this->_model = $this->getMockBuilder(Giftcard::class)
            ->setMethods($mockedMethods)
            ->setConstructorArgs(
                [
                    $productOption,
                    $eavConfigMock,
                    $productTypeMock,
                    $eventManager,
                    $storage,
                    $filesystem,
                    $coreRegistry,
                    $logger,
                    $productRepository,
                    $this->_storeManagerMock,
                    $locale,
                    $this->getMockForAbstractClass(ScopeConfigInterface::class),
                    $priceCurrency,
                    $this->serializer
                ]
            )
            ->getMock();
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _preConditions()
    {
        $this->_store->expects($this->any())->method('getCurrentCurrencyRate')->willReturn(1);
        $this->_productResource = $this->createMock(Product::class);
        $this->_optionResource = $this->createMock(Option::class);

        $productCollection = $this->createMock(Collection::class);

        $itemFactoryMock = $this->createPartialMock(
            OptionFactory::class,
            ['create']
        );
        $stockItemFactoryMock = $this->createPartialMock(
            StockItemInterfaceFactory::class,
            ['create']
        );
        $productFactoryMock = $this->createPartialMock(ProductFactory::class, ['create']);
        $categoryFactoryMock = $this->createPartialMock(CategoryFactory::class, ['create']);

        $objectManagerHelper = new ObjectManager($this);
        $arguments = $objectManagerHelper->getConstructArguments(
            \Magento\Catalog\Model\Product::class,
            [
                'itemOptionFactory' => $itemFactoryMock,
                'stockItemFactory' => $stockItemFactoryMock,
                'productFactory' => $productFactoryMock,
                'categoryFactory' => $categoryFactoryMock,
                'resource' => $this->_productResource,
                'resourceCollection' => $productCollection,
                'collectionFactory' => $this->createMock(CollectionFactory::class)
            ]
        );
        $this->_product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(
                [
                    'getGiftcardAmounts',
                    'getAllowOpenAmount',
                    'getOpenAmountMax',
                    'getOpenAmountMin',
                    '__wakeup',
                    'getSkipCheckRequiredOption'
                ]
            )
            ->setConstructorArgs($arguments)
            ->getMock();
        $this->_product->expects($this->any())
            ->method('getSkipCheckRequiredOption')
            ->willReturn(true);

        $this->_customOptions = [];
        for ($i = 1; $i <= 3; $i++) {
            $option = $objectManagerHelper->getObject(
                \Magento\Catalog\Model\Product\Option::class,
                ['resource' => $this->_optionResource]
            );
            $option->setIdFieldName('id');
            $option->setId($i);
            $option->setIsRequire(true);
            $this->_customOptions[AbstractType::OPTION_PREFIX . $i] =
                new DataObject(['value' => 'value']);
            $this->_product->addOption($option);
        }

        $this->_quoteItemOption = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);

        $this->_customOptions['info_buyRequest'] = $this->_quoteItemOption;

        $this->_product->expects($this->any())->method('getAllowOpenAmount')->willReturn(true);

        $this->_product->setSkipCheckRequiredOption(false);
        $this->_product->setCustomOptions($this->_customOptions);
    }

    public function testValidateEmptyFields()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            "{}"
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));

        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please specify all the required information.');
        $this->_model->checkProductBuyState($this->_product);
    }

    public function testValidateEmptyAmount()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email"}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));

        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a gift card amount.');
    }

    public function testValidateMaxAmount()
    {
        $this->_preConditions();
        $this->_product->expects($this->once())->method('getOpenAmountMax')->willReturn(10);
        $this->_product->expects($this->once())->method('getOpenAmountMin')->willReturn(3);
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"custom_giftcard_amount":15}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Gift Card max amount is ');
    }

    public function testValidateMinAmount()
    {
        $this->_preConditions();
        $this->_product->expects($this->once())->method('getOpenAmountMax')->willReturn(10);
        $this->_product->expects($this->once())->method('getOpenAmountMin')->willReturn(3);
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"custom_giftcard_amount":2}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Gift Card min amount is ');
    }

    public function testValidateNoAllowedAmount()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"custom_amount":7}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a gift card amount.');
    }

    public function testValidateRecipientName()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"giftcard_amount":5}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a recipient name.');
    }

    public function testValidateSenderName()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_recipient_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"giftcard_amount":5}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a sender name.');
    }

    public function testValidateRecipientEmail()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_sender_email":"email",'
                . '"giftcard_amount":5}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a recipient email.');
    }

    public function testValidateSenderEmail()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_amount":5}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a sender email.');
    }

    public function testValidate()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_customOptions['info_buyRequest'] = $this->_quoteItemOption;
        $this->_product->setCustomOptions($this->_customOptions);

        $this->_setStrictProcessMode(false);
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Test _getCustomGiftcardAmount when rate is equal
     */
    public function testGetCustomGiftcardAmountForEqualRate()
    {
        $giftcardAmount = 11.54;
        $this->_mockModel(['_isStrictProcessMode', '_getAmountWithinConstraints']);
        $this->_preConditions();
        $this->_setStrictProcessMode(false);
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->willReturn(
            '{"custom_giftcard_amount":' . $giftcardAmount . ',"giftcard_amount":"custom"}'
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_model->expects(
            $this->once()
        )->method(
            '_getAmountWithinConstraints'
        )->with(
            $this->equalTo($this->_product),
            $this->equalTo($giftcardAmount),
            $this->equalTo(false)
        )->willReturn(
            $giftcardAmount
        );
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Test _getCustomGiftcardAmount when current currency rate is not equal
     */
    public function testGetCustomGiftcardAmountForDifferentRate()
    {
        $giftcardAmount = 11.54;
        $storeRate = 2;
        $this->_store->expects($this->any())->method('getCurrentCurrencyRate')->willReturn($storeRate);
        $this->_mockModel(['_isStrictProcessMode', '_getAmountWithinConstraints']);
        $this->_preConditions();
        $this->_setStrictProcessMode(false);
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_quoteItemOption->expects($this->any())
            ->method('getValue')
            ->willReturn('{"custom_giftcard_amount":' . $giftcardAmount . ',"giftcard_amount":"custom"}');
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_model->expects($this->once())
            ->method('_getAmountWithinConstraints')
            ->with(
                $this->equalTo($this->_product),
                $this->equalTo($giftcardAmount / $storeRate),
                $this->equalTo(false)
            )
            ->willreturn($giftcardAmount);
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Running validation with specified exception message
     *
     * @param string $exceptionMessage
     */
    protected function _runValidationWithExpectedException($exceptionMessage)
    {
        $this->_customOptions['info_buyRequest'] = $this->_quoteItemOption;

        $this->_product->setCustomOptions($this->_customOptions);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Set getGiftcardAmount return value to empty array
     */
    protected function _setGetGiftcardAmountsReturnEmpty()
    {
        $this->_product->expects($this->once())->method('getGiftcardAmounts')->willReturn([]);
    }

    /**
     * Set getGiftcardAmount return value
     */
    protected function _setGetGiftcardAmountsReturnArray()
    {
        $this->_product->expects($this->once())->method('getGiftcardAmounts')->willReturn([['website_value' => 5]]);
    }

    /**
     * Set strict mode
     *
     * @param bool $mode
     */
    protected function _setStrictProcessMode($mode)
    {
        $this->_model->expects($this->once())->method('_isStrictProcessMode')->willReturn((bool)$mode);
    }

    protected function _setAmountWithConstraints()
    {
        $this->_model->expects($this->once())->method('_getAmountWithinConstraints')->willReturnArgument(1);
    }

    public function testHasWeightTrue()
    {
        $this->assertTrue($this->_model->hasWeight(), 'This product has not weight, but it should');
    }
}
