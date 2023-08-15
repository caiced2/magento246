<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Configuration;

use Laminas\Server\Reflection;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Support\Model\Report\Group\Configuration\PaymentFunctionalityMatrixSection;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentFunctionalityMatrixSectionTest extends AbstractConfigurationSectionTest
{
    /**
     * @var PaymentFunctionalityMatrixSection
     */
    protected $paymentFunctionalityMatrixReport;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Reflection|MockObject
     */
    protected $reflectionMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->reflectionMock = $this->createMock(Reflection::class);
        $this->paymentFunctionalityMatrixReport = $this->objectManagerHelper->getObject(
            PaymentFunctionalityMatrixSection::class,
            [
                'logger' => $this->loggerMock,
                'config' => $this->configMock,
                'objectManager' => $this->objectManagerMock,
                'reflection' => $this->reflectionMock,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testGetReportTitle()
    {
        $this->assertSame(
            (string)__('Payments Functionality Matrix'),
            $this->paymentFunctionalityMatrixReport->getReportTitle()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testGenerate()
    {
        $this->configMock->expects($this->once())
            ->method('getValue')
            ->willReturnMap([
                [
                    Custom::XML_PATH_PAYMENT,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    [],
                ]
            ]);
        $expectedData = [];
        $expected = [
            $this->paymentFunctionalityMatrixReport->getReportTitle() => [
                'headers' => [
                    (string)__('Code'),
                    (string)__('Title'),
                    (string)__('Group'),
                    (string)__('Is Gateway'),
                    (string)__('Void'),
                    (string)__('For Checkout'),
                    (string)__('For Multishipping'),
                    (string)__('Capture Online'),
                    (string)__('Partial Capture Online'),
                    (string)__('Refund Online'),
                    (string)__('Partial Refund Online'),
                    (string)__('Capture Offline'),
                    (string)__('Partial Capture Offline'),
                    (string)__('Refund Offline'),
                    (string)__('Partial Refund Offline'),
                ],
                'data' => $expectedData,
                'count' => count($expectedData),
            ],
        ];
        $this->assertSame($expected, $this->paymentFunctionalityMatrixReport->generate());
    }
}
