<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Plugin;

use Magento\Customer\Model\Session;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * Class UpdateCachePlugin to update Context with data
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class UpdateCachePlugin
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @param Session $customerSession
     * @param ConfigInterface $permissionsConfig
     */
    public function __construct(
        Session $customerSession,
        ConfigInterface $permissionsConfig
    ) {
        $this->customerSession = $customerSession;
        $this->permissionsConfig = $permissionsConfig;
    }

    /**
     * Update the context with current category and customer group id
     *
     * @param HttpContext $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetData(HttpContext $subject, array $result)
    {
        if (!$this->permissionsConfig->isEnabled()) {
            return $result;
        }

        $customerGroupId = $this->customerSession->getCustomerGroupId();
        if ($customerGroupId) {
            $result['customer_group'] = $customerGroupId;
        }

        return $result;
    }
}
