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
use Magento\Support\Model\Report\Group\Configuration\ShippingMethodsSection;

class ShippingMethodsSectionTest extends AbstractScopedConfigurationSectionTest
{
    /**
     * @var ShippingMethodsSection
     */
    protected $shippingMethodsReport;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->shippingMethodsReport = $this->objectManagerHelper->getObject(
            ShippingMethodsSection::class,
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
    public function testGenerate()
    {
        $carriers = [
            'test' => [
                'active' => '1',
                'title' => 'Test carrier',
                'sallowspecific' => '0',
                'nondoc_methods' => '1,3,4,8,P,Q,E,F,H,J,M,V,Y',
                'doc_methods' => '2,5,6,7,9,B,C,D,U,K,L,G,W,I,N,O,R,S,T,X',
                'free_method' => 'G',
                'gateway_url' => 'https://test.com',
                'id' => '',
                'password' => '',
                'content_type' => 'N',
                'specificerrmsg' => 'Test payment method message.',
            ],
        ];
        $this->configMock->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                [Custom::XML_PATH_CARRIERS, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, $carriers],
                [
                    Custom::XML_PATH_CARRIERS . '/test/active',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    $carriers['test']
                ],
            ]);
        $expectedData = [
            [
                'test',
                '',
                'Test carrier',
                AbstractScopedConfigurationSection::FLAG_YES,
                AbstractScopedConfigurationSection::SCOPE_DEFAULT
            ],
        ];
        $expectedResult = [
            $this->shippingMethodsReport->getReportTitle() => [
                'headers' => [
                    (string)__('Code'),
                    (string)__('Name'),
                    (string)__('Title'),
                    (string)__('Enabled'),
                    (string)__('Scope')
                ],
                'data' => $expectedData,
                'count' => count($expectedData),
            ],
        ];
        $this->assertSame($expectedResult, $this->shippingMethodsReport->generate());
    }

    /**
     * {@inheritdoc}
     */
    public function testGetReportTitle()
    {
        $this->assertSame((string)__('Shipping Methods'), $this->shippingMethodsReport->getReportTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function testGetConfigDataItem()
    {
        $expected = [
            'testCode',
            'testName',
            'testTitle',
            AbstractScopedConfigurationSection::FLAG_YES,
            AbstractScopedConfigurationSection::SCOPE_DEFAULT,
        ];
        $configInfo = [
            'extra' => [
                'code' => 'testCode',
                'name' => 'testName',
                'title' => 'testTitle',
            ],
        ];
        $actual = $this->shippingMethodsReport->getConfigDataItem(
            true,
            $configInfo,
            AbstractScopedConfigurationSection::SCOPE_DEFAULT
        );
        $this->assertSame($expected, $actual);
    }
}
