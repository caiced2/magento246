<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Block\Checkout;

use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ConfigInterface;

/**
 * @codeCoverageIgnore
 */
class Total extends \Magento\Checkout\Block\Total\DefaultTotal
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param ConfigInterface $salesConfig
     * @param array $layoutProcessors
     * @param array $data
     * @param Data|null $checkoutHelper
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
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
     * Totals calculation template when checkout using reward points
     *
     * @var string
     */
    protected $_template = 'checkout/total.phtml';

    /**
     * Return url to remove reward points from totals calculation
     *
     * @return string
     */
    public function getRemoveRewardTotalUrl()
    {
        return $this->getUrl('magento_reward/cart/remove');
    }
}
