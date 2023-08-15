<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Configuration;

use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Support\Model\Report\Group\Configuration\AbstractScopedConfigurationSection;
use Magento\Support\Model\Report\Group\Configuration\PaymentMethodsSection;

class PaymentMethodsSectionTest extends AbstractScopedConfigurationSectionTest
{
    /**
     * @var PaymentMethodsSection
     */
    protected $paymentMethodsReport;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentMethodsReport = $this->objectManagerHelper->getObject(
            PaymentMethodsSection::class,
            [
                'logger' => $this->loggerMock,
                'config' => $this->configMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testGetReportTitle()
    {
        $this->assertSame((string)__('Payment Methods'), $this->paymentMethodsReport->getReportTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function testGetConfigDataItem()
    {
        $expected = [
            'testCode',
            'testGroup',
            'testTitle',
            AbstractScopedConfigurationSection::FLAG_YES,
            'testScope'
        ];
        $configInfo = [
            'title' => 'testTitle',
            'enabled_flag' => true,
            'extra' => [
                'code' => 'testCode',
                'group' => 'testGroup',
            ],
        ];
        $this->assertSame($expected, $this->paymentMethodsReport->getConfigDataItem(true, $configInfo, 'testScope'));
    }

    /**
     * {@inheritdoc}
     */
    public function testGetConfigDataItemWithDisabledFlag()
    {
        $expected = [
            'testCode',
            'testGroup',
            'testTitle',
            '',
            'testScope'
        ];
        $configInfo = [
            'title' => 'testTitle',
            'enabled_flag' => false,
            'extra' => [
                'code' => 'testCode',
                'group' => 'testGroup',
            ],
        ];
        $this->assertSame($expected, $this->paymentMethodsReport->getConfigDataItem(true, $configInfo, 'testScope'));
    }

    /**
     * {@inheritdoc}
     */
    public function testGenerate()
    {
        $payments = [
            'test1' => [
                'active' => '1',
                'order_status' => 'pending',
                'title' => 'No Payment Information Required',
                'allowspecific' => '0',
                'sort_order' => '1',
                'group' => 'offline',
                'specificcountry' => null,
            ],
            'test2' => [
                'active' => '1',
                'order_status' => 'pending',
                'title' => 'Cash On Delivery',
                'allowspecific' => '0',
                'group' => 'offline',
                'specificcountry' => null,
                'instructions' => null,
                'min_order_total' => null,
                'max_order_total' => null,
                'sort_order' => null,
            ]
        ];
        $this->configMock->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                [Custom::XML_PATH_PAYMENT, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, $payments],
                [
                    Custom::XML_PATH_PAYMENT . '/test1/active',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    $payments['test1']
                ],
                [
                    Custom::XML_PATH_PAYMENT . '/test2/active',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    $payments['test2']
                ],
            ]);
        $expectedData = [
            [
                'test1',
                'offline',
                'No Payment Information Required',
                AbstractScopedConfigurationSection::FLAG_YES,
                AbstractScopedConfigurationSection::SCOPE_DEFAULT
            ],
            [
                'test2',
                'offline',
                'Cash On Delivery',
                AbstractScopedConfigurationSection::FLAG_YES,
                AbstractScopedConfigurationSection::SCOPE_DEFAULT
            ],
        ];
        $expectedResult = [
            $this->paymentMethodsReport->getReportTitle() => [
                'headers' => [
                    (string)__('Code'),
                    (string)__('Group'),
                    (string)__('Title'),
                    (string)__('Enabled'),
                    (string)__('Scope'),
                ],
                'data' => $expectedData,
                'count' => count($expectedData),
            ]
        ];
        $this->assertSame($expectedResult, $this->paymentMethodsReport->generate());
    }
}
