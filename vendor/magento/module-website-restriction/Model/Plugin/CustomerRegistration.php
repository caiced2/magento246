<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Model\Plugin;

class CustomerRegistration
{
    /**
     * @var \Magento\WebsiteRestriction\Model\ConfigInterface
     */
    protected $_restrictionConfig;

    /**
     * @param \Magento\WebsiteRestriction\Model\ConfigInterface $restrictionConfig
     */
    public function __construct(\Magento\WebsiteRestriction\Model\ConfigInterface $restrictionConfig)
    {
        $this->_restrictionConfig = $restrictionConfig;
    }

    /**
     * Check if registration is allowed
     *
     * @param \Magento\Customer\Model\Registration $subject
     * @param boolean $invocationResult
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsAllowed(\Magento\Customer\Model\Registration $subject, $invocationResult)
    {
        if ($invocationResult) {
            $invocationResult = !$this->_restrictionConfig->isRestrictionEnabled() ||
                \Magento\WebsiteRestriction\Model\Mode::ALLOW_REGISTER === $this->_restrictionConfig->getMode();
        }
        return $invocationResult;
    }
}
