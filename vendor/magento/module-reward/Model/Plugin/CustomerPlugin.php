<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Plugin;

use Magento\Reward\Helper\Data;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\RequestInterface;

class CustomerPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Data
     */
    private $rewardHelper;

    /**
     * @param RequestInterface $request
     * @param Data $rewardHelper
     */
    public function __construct(
        RequestInterface $request,
        Data $rewardHelper
    ) {
        $this->request = $request;
        $this->rewardHelper = $rewardHelper;
    }

    /**
     * Set reward-related attributes to customer model
     *
     * @param Customer $customer
     * @return void
     */
    public function beforeBeforeSave(Customer $customer)
    {
        if ($this->request->getFullActionName() == 'customer_index_save' && $this->rewardHelper->isEnabled()) {
            $data = $this->request->getPost('reward');

            if (!$customer->getId()) {
                // Use configuration settings for new customers
                $subscribeByDefault = (int)$this->rewardHelper->getNotificationConfig(
                    'subscribe_by_default',
                    (int) $customer->getWebsiteId()
                );
                $data['reward_update_notification'] = $subscribeByDefault;
                $data['reward_warning_notification'] = $subscribeByDefault;
            }

            $customer->setData('reward_update_notification', empty($data['reward_update_notification']) ? 0 : 1);
            $customer->setData('reward_warning_notification', empty($data['reward_warning_notification']) ? 0 : 1);
        }
    }
}
