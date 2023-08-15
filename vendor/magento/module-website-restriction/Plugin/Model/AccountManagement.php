<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\WebsiteRestriction\Plugin\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\WebsiteRestriction\Model\ConfigInterface;
use Magento\WebsiteRestriction\Model\Mode;

/**
 * Check access to registration new customer.
 */
class AccountManagement
{
    /**
     * Website Restriction config.
     *
     * @var ConfigInterface
     */
    private $restrictionConfig;

    /**
     * @param ConfigInterface $restrictionConfig
     */
    public function __construct(ConfigInterface $restrictionConfig)
    {
        $this->restrictionConfig = $restrictionConfig;
    }

    /**
     * Check there is access to registration.
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param CustomerInterface $customer
     * @param string|null $password
     * @param string|null $redirectUrl
     * @return null
     * @throws LocalizedException in case there is no access to registration.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCreateAccount(
        \Magento\Customer\Model\AccountManagement $subject,
        CustomerInterface $customer,
        $password = null,
        $redirectUrl = ''
    ) {
        if (!$customer->getId() && $this->restrictionConfig->isRestrictionEnabled()
            && Mode::ALLOW_REGISTER !== $this->restrictionConfig->getMode()
        ) {
            throw new LocalizedException(__('Can not register new customer due to restrictions are enabled.'));
        }

        return null;
    }
}
