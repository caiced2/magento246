<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Model\Report\Group\Configuration;

use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPool;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Model\MethodInterface;
use Psr\Log\LoggerInterface;
use Laminas\Server\Reflection;

/**
 * PaymentFunctionalityMatrixSection for support report
 */
class PaymentFunctionalityMatrixSection extends AbstractConfigurationSection
{
    const NOT_AVAILABLE = 'n/a';

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManger;

    /**
     * @var Reflection
     */
    protected $reflection;

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $config
     * @param ObjectManagerInterface $objectManager
     * @param Reflection $reflection
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $config,
        ObjectManagerInterface $objectManager,
        Reflection $reflection
    ) {
        $this->config = $config;
        $this->objectManger = $objectManager;
        $this->reflection = $reflection;
        parent::__construct($logger);
    }

    /**
     * @inheritDoc
     */
    public function generate()
    {
        $data = $this->getPaymentsData();
        return [
            $this->getReportTitle() => [
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
                'data' => $data,
                'count' => count($data),
            ],
        ];
    }

    /**
     * Return extended information about payment methods
     *
     * @return array
     */
    public function getPaymentsData()
    {
        $data = [];
        if (!($methods = $this->config->getValue(Custom::XML_PATH_PAYMENT))) {
            return [];
        }

        foreach ($methods as $code => $info) {
            if (!($model = $this->getByKey($info, 'model'))) {
                continue;
            }

            $payment = $this->objectManger->create($model);
            $title = $this->getByKey($info, 'title', '');
            $group = $this->getByKey($info, 'group', '');

            if (($methodData = $this->getPaymentData($payment, $title, $group, $code))) {
                $data[] = $methodData;
            }
        }

        return $data;
    }

    /**
     * Return data for single payment method
     *
     * @codeCoverageIgnore
     * @param MethodInterface $payment
     * @param string $title
     * @param string $group
     * @param string $code
     * @return array|null
     */
    protected function getPaymentData(MethodInterface $payment, $title, $group, $code)
    {
        try {
            if ($payment instanceof AbstractMethod) {
                return $this->extractRealMethod($payment, $title, $group, $code);
            } elseif ($payment instanceof Adapter) {
                return $this->extractVirtualMethod($payment, $title, $group, $code);
            } elseif ($payment instanceof MethodInterface) {
                return $this->extractBasicMethod($payment, $title, $group, $code);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return null;
    }

    /**
     * Extracting information about real method
     *
     * @param MethodInterface $payment
     * @param string $title
     * @param string $group
     * @param string $code
     * @return array
     */
    protected function extractRealMethod(
        MethodInterface $payment,
        $title,
        $group,
        $code
    ) {
        /** @var \ReflectionClass $reflectionPayment */
        $reflectionPayment = $this->reflection->reflectClass($payment);
        /** @var \ReflectionProperty $isGateway */
        $isGateway = $reflectionPayment->getProperty('_isGateway');
        $isGateway->setAccessible(true);
        /** @var \ReflectionProperty $canVoid */
        $canVoid = $reflectionPayment->getProperty('_canVoid');
        $canVoid->setAccessible(true);
        /** @var \ReflectionProperty $canUseCheckout */
        $canUseCheckout = $reflectionPayment->getProperty('_canUseCheckout');
        $canUseCheckout->setAccessible(true);
        /** @var \ReflectionProperty $canCapture */
        $canCapture = $reflectionPayment->getProperty('_canCapture');
        $canCapture->setAccessible(true);
        /** @var \ReflectionProperty $canCapturePartial */
        $canCapturePartial = $reflectionPayment->getProperty('_canCapturePartial');
        $canCapturePartial->setAccessible(true);
        /** @var \ReflectionProperty $canRefund */
        $canRefund = $reflectionPayment->getProperty('_canRefund');
        $canRefund->setAccessible(true);
        /** @var \ReflectionProperty $canRefundInvoicePartial */
        $canRefundInvoicePartial = $reflectionPayment->getProperty('_canRefundInvoicePartial');
        $canRefundInvoicePartial->setAccessible(true);

        return [
            $code,
            $title,
            $group,
            $this->toFlag($isGateway->getValue($payment)),
            $this->toFlag($canVoid->getValue($payment)),
            $this->toFlag($canUseCheckout->getValue($payment)),
            self::FLAG_YES,
            $this->toFlag($canCapture->getValue($payment)),
            $this->toFlag($canCapture->getValue($payment) && $canCapturePartial->getValue($payment)),
            $this->toFlag($canRefund->getValue($payment)),
            $this->toFlag($canRefund->getValue($payment) && $canRefundInvoicePartial->getValue($payment)),
            self::FLAG_YES,
            $this->toFlag($canCapture->getValue($payment) && $canCapturePartial->getValue($payment)),
            self::FLAG_YES,
            $this->toFlag($canRefund->getValue($payment) && $canRefundInvoicePartial->getValue($payment)),
        ];
    }

    /**
     * Extracting information about virtual method
     *
     * @param Adapter $payment
     * @param string $title
     * @param string $group
     * @param string $code
     * @return array
     * @throws \ReflectionException
     */
    protected function extractVirtualMethod(
        Adapter $payment,
        $title,
        $group,
        $code
    ) {
        /** @var \ReflectionClass $reflectionPayment */
        $reflectionPayment = $this->reflection->reflectClass($payment);

        if ($payment instanceof \Magento\Framework\Interception\InterceptorInterface) {
            /** @var \ReflectionClass $reflectionPayment */
            $reflectionPayment = $reflectionPayment->getParentClass();
        }

        /** @var \ReflectionProperty $valueHandlerPool */
        $valueHandlerProperty = $reflectionPayment->getProperty('valueHandlerPool');
        $valueHandlerProperty->setAccessible(true);
        /** @var ValueHandlerPool $valueHandlerPool */
        $valueHandlerPool = $valueHandlerProperty->getValue($payment);
        $defaultHandler = $valueHandlerPool->get(ValueHandlerPool::DEFAULT_HANDLER);
        $storeId = $payment->getStore();

        $canCapture = $defaultHandler->handle(['field' => 'can_capture'], $storeId);
        $canRefund = $defaultHandler->handle(['field' => 'can_refund'], $storeId);
        $canCapturePartial = $defaultHandler->handle(['field' => 'can_capture_partial'], $storeId);
        $canCapturePartialPerInvoice = $defaultHandler->handle(['field' => 'can_refund_partial_per_invoice'], $storeId);

        return [
            $code,
            $title,
            $group,
            $this->toFlag($defaultHandler->handle(['field' => 'is_gateway'], $storeId)),
            $this->toFlag($defaultHandler->handle(['field' => 'can_void'], $storeId)),
            $this->toFlag($defaultHandler->handle(['field' => 'can_use_checkout'], $storeId)),
            self::FLAG_YES,
            $this->toFlag($canCapture),
            $this->toFlag($canCapture && $canCapturePartial),
            $this->toFlag($canRefund),
            $this->toFlag($canRefund && $canCapturePartialPerInvoice),
            self::FLAG_YES,
            $this->toFlag($canCapture && $canCapturePartial),
            self::FLAG_YES,
            $this->toFlag($canRefund && $canCapturePartialPerInvoice),
        ];
    }

    /**
     * Extract basic method
     *
     * @param MethodInterface $payment
     * @param string $title
     * @param string $group
     * @param string $code
     * @return array
     */
    protected function extractBasicMethod(
        MethodInterface $payment,
        $title,
        $group,
        $code
    ) {
        return [
            $code,
            $title,
            $group,
            $this->toFlag($payment->isGateway()),
            $this->toFlag($payment->canVoid()),
            $this->toFlag($payment->canUseCheckout()),
            self::FLAG_YES,
            $this->toFlag($payment->canCapture()),
            $this->toFlag($payment->canCapture() && $payment->canCapturePartial()),
            $this->toFlag($payment->canRefund()),
            $this->toFlag($payment->canRefund() && $payment->canRefundPartialPerInvoice()),
            self::FLAG_YES,
            $this->toFlag($payment->canCapture() && $payment->canCapturePartial()),
            self::FLAG_YES,
            $this->toFlag($payment->canRefund() && $payment->canRefundPartialPerInvoice()),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getReportTitle()
    {
        return (string)__('Payments Functionality Matrix');
    }
}
