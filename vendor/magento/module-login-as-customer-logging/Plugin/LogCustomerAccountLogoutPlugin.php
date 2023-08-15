<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Plugin;

use Closure;
use Exception;
use Magento\Customer\Controller\Account\Logout;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;
use Magento\LoginAsCustomerLogging\Model\LogValidation;

/**
 * Log admin logged out as customer
 */
class LogCustomerAccountLogoutPlugin
{
    private const ACTION = 'logout';

    /**
     * @var GetEventForLogging;
     */
    private $getEventForLogging;

    /**
     * @var Event
     */
    private $eventResource;

    /**
     * @var LogValidation
     */
    private $logValidation;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface
     */
    private $getLoggedAsCustomerAdminId;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param GetEventForLogging $getEventForLogging
     * @param Event $eventResource
     * @param LogValidation $logValidation
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     * @param Session $session
     */
    public function __construct(
        GetEventForLogging $getEventForLogging,
        Event $eventResource,
        LogValidation $logValidation,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId,
        Session $session
    ) {
        $this->getEventForLogging = $getEventForLogging;
        $this->eventResource = $eventResource;
        $this->logValidation = $logValidation;
        $this->getLoggedAsCustomerAdminId = $getLoggedAsCustomerAdminId;
        $this->session = $session;
    }

    /**
     * Log admin "logout" as a customer action.
     *
     * @param Logout $subject
     * @param Closure $proceed
     * @return Redirect
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        Logout $subject,
        Closure $proceed
    ) {
        if (!$this->logValidation->shouldBeLogged()) {
            return $proceed();
        }

        $adminId = $this->getLoggedAsCustomerAdminId->execute();
        $event = $this->getEventForLogging->execute($adminId);
        $event->setAction(self::ACTION);

        $customerId = $this->session->getCustomerId();
        $customerEmail = $this->session->getCustomer()->getEmail();
        try {
            $result = $proceed();
            $info = __(
                'Logged out from the customer account: id = %1, email = %2, ',
                $customerId,
                $customerEmail
            ) . $event->getInfo();
            $event->setInfo($info);
            $this->eventResource->save($event);
        } catch (LocalizedException $e) {
            $event->setIsSuccess(0);
            $info = __(
                'Logged out from the customer account: id = %1, email = %2, ',
                $customerId,
                $customerEmail
            ) . $event->getInfo();
            $event->setInfo($info);
            $event->setErrorMessage($e->getLogMessage());
            $this->eventResource->save($event);
            throw new LocalizedException(__($e->getMessage()));
        }

        return $result;
    }
}
