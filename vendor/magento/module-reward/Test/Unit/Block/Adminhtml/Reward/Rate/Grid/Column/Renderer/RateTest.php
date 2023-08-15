<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Block\Adminhtml\Reward\Rate\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Reward\Block\Adminhtml\Reward\Rate\Grid\Column\Renderer\Rate as RateColumn;
use Magento\Reward\Model\Reward\Rate as RateModel;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\Reward\Block\Adminhtml\Reward\Rate\Grid\Column\Renderer\Rate
 */
class RateTest extends TestCase
{
    /**
     * USD currency code
     */
    private const USD_CURRENCY_CODE = 'USD';

    /**
     * @var RateColumn
     */
    private $rateColumn;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var RateModel|MockObject
     */
    private $rateMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManagerHelper($this);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rateMock = $this->createMock(RateModel::class);
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();

        $this->rateColumn = $objectManager->getObject(
            RateColumn::class,
            [
                'storeManager' => $this->storeManagerMock,
                'rate' => $this->rateMock
            ]
        );
    }

    /**
     * Testing the rate column renderer with correct currency symbol
     *
     * @dataProvider rewardExchangeRatesDataProvider
     *
     * @param DataObject $row
     * @param string $currencyCode
     * @param string $expectedResult
     */
    public function testColumnRendererWithCorrectCurrency(
        DataObject $row,
        string $currencyCode,
        string $expectedResult
    ): void {
        $this->storeMock->expects($this->atLeastOnce())->method('getBaseCurrencyCode')
            ->willReturn($currencyCode);
        $this->storeManagerMock->expects($this->once())->method('getWebsite')->willReturn($this->storeMock);
        $this->rateMock->expects($this->once())->method('getRateText')->willReturn($expectedResult);
        $result = $this->rateColumn->render($row);
        $this->assertSame($result, $expectedResult);
    }

    /**
     * Exchange rate data provider
     *
     * @return array
     */
    public function rewardExchangeRatesDataProvider(): array
    {
        return [
            'Exchange rate direction to USD currency' => [
                new DataObject([
                    'website_id' => 0,
                    'direction' => 1,
                    'points' => 5,
                    'currency_amount' => 10
                ]),
                static::USD_CURRENCY_CODE,
                sprintf('%1$s points = %2$s', 5, '$10')
            ],
            'Exchange rate direction to points' => [
                new DataObject([
                    'website_id' => 0,
                    'direction' => 2,
                    'points' => 10,
                    'currency_amount' => 100
                ]),
                static::USD_CURRENCY_CODE,
                sprintf('%2$s = %1$s points', 10, '$100')
            ]
        ];
    }
}
