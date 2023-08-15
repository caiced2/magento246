<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Block\Adminhtml\Payment;

use Magento\Backend\Model\Auth\Session;
use Magento\QuickCheckout\Model\Config;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form as PaymentForm;

/**
 * Render quick checkout payment method
 *
 * @api
 */
class Form extends PaymentForm
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $authSession;

    /**
     * @var string
     */
    protected $_template = 'Magento_QuickCheckout::payment/form.phtml';

    /**
     * @param Context $context
     * @param Config $config
     * @param Session $authSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->authSession = $authSession;
    }

    /**
     * Get payment method code
     *
     * @return string
     */
    public function getMethodCode() : string
    {
        return 'quick_checkout';
    }

    /**
     * Get form config as JSON
     *
     * @return string
     */
    public function getCreditCardFormConfigJson() : string
    {
        $config = [
            'code' => $this->getMethodCode(),
            'locale' => $this->getLocale(),
            'publishableKey' => $this->config->getPublishableKey(),
            'creditCardFormConfig' => $this->config->getCreditCardFormConfig()
        ];
        return json_encode($config);
    }

    /**
     * Get locale of the logged in admin user
     *
     * @return string
     */
    private function getLocale() : string
    {
        return str_replace('_', '-', $this->authSession->getUser()->getInterfaceLocale());
    }
}
