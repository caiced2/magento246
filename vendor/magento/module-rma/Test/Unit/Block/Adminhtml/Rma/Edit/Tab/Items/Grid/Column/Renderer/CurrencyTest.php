<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Directory\Model\Currency as CurrencyModel;
use Magento\Directory\Model\Currency\DefaultLocator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Reports\Block\Adminhtml\Grid\Column\Renderer\Currency as CurrencyBlock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Currency
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class CurrencyTest extends TestCase
{
    /**
     * @var Currency
     */
    private $model;

    /**
     * @var MockObject
     */
    private $scopeConfigMock;

    /**
     * @var MockObject
     */
    private $storeManagerMock;

    /**
     * @var DefaultLocator|MockObject
     */
    private $currencyLocatorMock;

    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrencyMock;

    /**
     * @var Column|MockObject
     */
    private $gridColumnMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteMock;

    /**
     * @var DataObject
     */
    private $row;

    /**
     * @var CurrencyModel|MockObject
     */
    private $currencyMock;

    /**
     * @var MockObject
     */
    private $backendCurrencyMock;

    /**
     * Initialize the test class
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->scopeConfigMock = $this->getMockForAbstractClass(
            ScopeConfigInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue']
        );

        $this->storeManagerMock = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getStore', 'getWebsite']
        );

        $this->storeMock = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getWebsiteId', 'getCurrentCurrencyCode']
        );

        $this->websiteMock = $this->getMockForAbstractClass(
            WebsiteInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getBaseCurrencyCode']
        );

        $this->currencyLocatorMock = $this->getMockBuilder(DefaultLocator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyMock = $this->createMock(CurrencyModel::class);
        $this->currencyMock->expects($this->any())->method('load')->willReturnSelf();

        $currencyFactoryMock = $this->createPartialMock(CurrencyFactory::class, ['create']);
        $currencyFactoryMock->expects($this->any())->method('create')->willReturn($this->currencyMock);

        $this->backendCurrencyMock = $this->getMockBuilder(CurrencyBlock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeCurrencyMock = $this->getMockForAbstractClass(
            CurrencyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getCurrency']
        );

        $this->gridColumnMock = $this->getMockBuilder(Column::class)
            ->addMethods(['getIndex', 'getCurrency', 'getRateField'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $objectManager->getObject(
            Currency::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'storeManager' => $this->storeManagerMock,
                'currencyLocator' => $this->currencyLocatorMock,
                'currencyFactory' => $currencyFactoryMock,
                'localeCurrency' => $this->localeCurrencyMock
            ]
        );
        $this->model->setColumn($this->gridColumnMock);
    }

    /**
     * Test render function which converts store currency based on price scope settings
     *
     * @param float $rate
     * @param string $columnIndex
     * @param int $catalogPriceScope
     * @param int $adminWebsiteId
     * @param string $adminCurrencyCode
     * @param string $storeCurrencyCode
     * @param float $originalPrice
     * @param float $basePrice
     * @param float $taxPrice
     * @throws NoSuchEntityException
     * @dataProvider getCurrencyDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRender(
        float $rate,
        string $columnIndex,
        int $catalogPriceScope,
        int $adminWebsiteId,
        string $adminCurrencyCode,
        string $storeCurrencyCode,
        float $originalPrice,
        float $basePrice,
        float $taxPrice
    ): void {
        $this->row = new DataObject(
            [
                $columnIndex => $originalPrice,
                'rate' => $rate,
                'original_price' => $originalPrice,
                'price' => $basePrice,
                'taxPrice' => $taxPrice
            ]
        );
        $this->backendCurrencyMock
            ->expects($this->any())
            ->method('getColumn')
            ->willReturn($this->gridColumnMock);
        $this->gridColumnMock
            ->expects($this->any())
            ->method('getIndex')
            ->willReturn($columnIndex);
        $this->currencyMock
            ->expects($this->any())
            ->method('getRate')
            ->willReturn($rate);
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn($catalogPriceScope);
        $this->storeManagerMock
            ->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock
            ->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($adminWebsiteId);
        $this->storeManagerMock
            ->expects($this->any())
            ->method('getWebsite')
            ->with($adminWebsiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock
            ->expects($this->any())
            ->method('getBaseCurrencyCode')
            ->willReturn($adminCurrencyCode);
        $this->currencyLocatorMock
            ->expects($this->any())
            ->method('getDefaultCurrency')
            ->willReturn($storeCurrencyCode);
        $currLocaleMock = $this->createMock(\Magento\Framework\Currency\Data\Currency::class);
        $currLocaleMock
            ->expects($this->any())
            ->method('toCurrency')
            ->willReturn((string)$originalPrice);
        $this->localeCurrencyMock
            ->expects($this->any())
            ->method('getCurrency')
            ->with($storeCurrencyCode)
            ->willReturn($currLocaleMock);
        $this->gridColumnMock->method('getCurrency')->willReturn('USD');
        $this->gridColumnMock->method('getRateField')->willReturn('test_rate_field');
        $actualAmount = $this->model->render($this->row);
        $this->assertEquals($originalPrice, $actualAmount);
    }

    /**
     * DataProvider for testRender.
     *
     * @return array
     */
    public function getCurrencyDataProvider(): array
    {
        return [
            'render price when tax is included from the price' => [
                'rate' => 1.00,
                'columnIndex' => 'price',
                'catalogPriceScope' => 1,
                'adminWebsiteId' => 1,
                'adminCurrencyCode' => 'USD',
                'storeCurrencyCode' => 'USD',
                'originalPrice' => 30.00,
                'basePrice' => 30.00,
                'taxPrice' => 0.00
            ],
            'render price when tax is excluded from the price' => [
                'rate' => 1.4150,
                'columnIndex' => 'price',
                'catalogPriceScope' => 0,
                'adminWebsiteId' => 1,
                'adminCurrencyCode' => 'USD',
                'storeCurrencyCode' => 'USD',
                'originalPrice' => 30.00,
                'basePrice' => 33.00,
                'taxPrice' => 3.00
            ]
        ];
    }

    protected function tearDown(): void
    {
        unset($this->scopeConfigMock);
        unset($this->storeManagerMock);
        unset($this->currencyLocatorMock);
        unset($this->localeCurrencyMock);
        unset($this->websiteMock);
        unset($this->storeMock);
        unset($this->currencyMock);
        unset($this->backendCurrencyMock);
        unset($this->gridColumnMock);
    }
}
