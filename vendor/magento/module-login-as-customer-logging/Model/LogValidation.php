<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Model;

use Magento\Logging\Model\Config;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;

class LogValidation
{
    private const EVENT_CODE = 'login_as_customer';

    /**
     * @var GetLoggedAsCustomerAdminIdInterface
     */
    private $getLoggedAsCustomerAdminId;

    /**
     * Logging events config
     *
     * @var Config
     */
    private Config $config;

    /**
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     * @param Config $config
     */
    public function __construct(
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId,
        Config $config
    ) {
        $this->getLoggedAsCustomerAdminId = $getLoggedAsCustomerAdminId;
        $this->config = $config;
    }

    /**
     * Action should be logged if logging for this group enabled and admin logged as a customer
     *
     * @return bool
     */
    public function shouldBeLogged(): bool
    {
        return $this->config->isEventGroupLogged(self::EVENT_CODE)
            && $this->getLoggedAsCustomerAdminId->execute();
    }
}
