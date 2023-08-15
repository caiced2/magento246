<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ObserverInterface;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

class SetGoogleAnalyticsOnOrderSuccessPageViewObserver implements ObserverInterface
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\ViewInterface
     */
    protected $view;

    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfig;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\App\ViewInterface $view
     * @param TagManagerConfig|null $tagManagerConfig
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\App\ViewInterface $view,
        TagManagerConfig $tagManagerConfig = null
    ) {
        $this->helper = $helper;
        $this->view = $view;
        $this->tagManagerConfig = $tagManagerConfig ?? ObjectManager::getInstance()->get(
            TagManagerConfig::class
        );
    }

    /**
     * Add order information into GA block to render on checkout success pages
     * The method overwrites the GoogleAnalytics observer method by the system.xml event settings
     * Fired by the checkout_onepage_controller_success_action and
     * checkout_multishipping_controller_success_action events
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isGoogleAnalyticsAvailable() && !$this->tagManagerConfig->isGoogleAnalyticsAvailable()) {
            return $this;
        }

        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return $this;
        }
        /** @var \Magento\GoogleTagManager\Block\Ga $block */
        $block = $this->view->getLayout()->getBlock('google_analyticsuniversal');
        $blockGa4 = $this->view->getLayout()->getBlock('google_gtag_analyticsgtm');
        if ($block) {
            $block->setOrderIds($orderIds);
        }
        if ($blockGa4) {
            $blockGa4->setOrderIds($orderIds);
        }

        return $this;
    }
}
