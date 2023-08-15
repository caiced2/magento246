<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Sales\Pdf;

use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Helper\Data as GiftWrappingHelper;
use Magento\GiftWrapping\Model\Sales\Pdf\Totals;
use Magento\GiftWrapping\Model\System\Config\Source\Display\Type as GWDisplayType;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test gift wrap totals PDF model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TotalsTest extends TestCase
{
    /**
     * @var Totals
     */
    private $model;
    /**
     * @var GiftWrappingHelper
     */
    private $giftWrappingHelper;
    /**
     * @var Invoice
     */
    private $invoice;
    /**
     * @var MockObject
     */
    private $config;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->config = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->giftWrappingHelper = $objectManager->getObject(
            GiftWrappingHelper::class,
            [
                'context' => $objectManager->getObject(
                    Context::class,
                    [
                        'scopeConfig' => $this->config
                    ]
                )
            ]
        );
        $this->model = new Totals(
            $objectManager->getObject(TaxHelper::class),
            $objectManager->getObject(Calculation::class),
            $objectManager->getObject(CollectionFactory::class),
            $this->giftWrappingHelper
        );
        $this->invoice = $this->createInvoice();
        $this->model->setSource($this->invoice);
        $this->model->setOrder($this->invoice->getOrder());
    }

    /**
     * Test use cases of gif wrapping totals
     *
     * @param array $configData
     * @param array $invoiceData
     * @param array $expected
     * @dataProvider getTotalsForDisplayDataProvider
     */
    public function testGetTotalsForDisplay(array $configData, array $invoiceData, array $expected)
    {
        $this->config->method('getValue')
            ->willReturnCallback(
                function ($path) use ($configData) {
                    return $configData[$path] ?? false;
                }
            );

        $this->invoice->setData($invoiceData);
        $this->assertEquals($expected, $this->model->getTotalsForDisplay());
    }

    /**
     * Provide use case scenarios of gif wrapping totals
     *
     * @return array
     */
    public function getTotalsForDisplayDataProvider(): array
    {
        return [
            [
                [],
                [],
                []
            ],
            [
                [],
                [
                    'gw_price' => 5,
                    'gw_base_price' => 5,
                ],
                [
                    [
                        'label' => 'Gift Wrapping for Order:',
                        'amount' => '$5.00',
                        'font_size' => 7,
                    ]
                ]
            ],
            [
                [
                    GiftWrappingHelper::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING => GWDisplayType::DISPLAY_TYPE_BOTH
                ],
                [
                    'gw_tax_amount' => 2.50,
                    'gw_base_tax_amount' => 2.50,
                    'gw_price' => 5,
                    'gw_base_price' => 5,
                ],
                [
                    [
                        'label' => 'Gift Wrapping for Order (Excl. Tax):',
                        'amount' => '$5.00',
                        'font_size' => 7,
                    ],
                    [
                        'label' => 'Gift Wrapping for Order (Incl. Tax):',
                        'amount' => '$7.50',
                        'font_size' => 7,
                    ]
                ]
            ]
        ];
    }

    /**
     * Create invoice instance for test
     *
     * @return Invoice
     */
    private function createInvoice()
    {
        $objectManager = new ObjectManager($this);
        $currency = $this->createMock(Currency::class);
        $currency->method('formatTxt')
            ->willReturnCallback(
                function ($value) {
                    return sprintf('$%.2f', (float) $value);
                }
            );
        $currencyFactory = $this->createMock(CurrencyFactory::class);
        $currencyFactory->method('create')->willReturn($currency);
        $invoice = $objectManager->getObject(Invoice::class);
        $order = $objectManager->getObject(
            Order::class,
            [
                'currencyFactory' => $currencyFactory
            ]
        );
        $invoice->setOrder($order);
        return $invoice;
    }
}
