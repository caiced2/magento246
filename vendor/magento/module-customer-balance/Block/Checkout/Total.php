<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer balance total block for checkout
 *
 */
namespace Magento\CustomerBalance\Block\Checkout;

use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ConfigInterface;

class Total extends \Magento\Checkout\Block\Total\DefaultTotal
{
    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param ConfigInterface $salesConfig
     * @param array $layoutProcessors
     * @param array $data
     * @param Data|null $checkoutHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CheckoutSession $checkoutSession,
        ConfigInterface $salesConfig,
        array $layoutProcessors = [],
        array $data = [],
        Data $checkoutHelper = null
    ) {
        $data['checkoutHelper'] = $checkoutHelper ?? ObjectManager::getInstance()->get(Data::class);
        parent::__construct($context, $customerSession, $checkoutSession, $salesConfig, $layoutProcessors, $data);
    }

    /**
     * Custom constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_isScopePrivate = true;
    }

    /**
     * @var string
     */
    protected $_template = 'Magento_CustomerBalance::checkout/total.phtml';
}
