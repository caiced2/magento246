<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Plugin\LoginAsCustomerApi;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\LoginAsCustomerApi\Api\AuthenticateCustomerBySecretInterface;
use Magento\LoginAsCustomerApi\Api\GetAuthenticationDataBySecretInterface;
use Magento\LoginAsCustomerApi\Api\Data\AuthenticationDataInterface;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;

/**
 * Log admin logged in as customer plugin.
 */
class LogAuthenticationPlugin
{
    private const ACTION = 'login';

    /**
     * @var GetAuthenticationDataBySecretInterface
     */
    private $getAuthenticationDataBySecret;

    /**
     * @var GetEventForLogging
     */
    private $eventForLogging;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Event
     */
    private $eventResource;

    /**
     * @param GetAuthenticationDataBySecretInterface $getAuthenticationDataBySecret
     * @param GetEventForLogging $eventForLogging
     * @param CustomerRepositoryInterface $customerRepository
     * @param Event $eventResource
     */
    public function __construct(
        GetAuthenticationDataBySecretInterface $getAuthenticationDataBySecret,
        GetEventForLogging $eventForLogging,
        CustomerRepositoryInterface $customerRepository,
        Event $eventResource
    ) {
        $this->getAuthenticationDataBySecret = $getAuthenticationDataBySecret;
        $this->eventForLogging = $eventForLogging;
        $this->customerRepository = $customerRepository;
        $this->eventResource = $eventResource;
    }

    /**
     * Log authentication as customer.
     *
     * @param AuthenticateCustomerBySecretInterface $subject
     * @param \Closure $proceed
     * @param string $secret
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        AuthenticateCustomerBySecretInterface $subject,
        \Closure $proceed,
        string $secret
    ): void {
        $authenticationData = $this->getAuthenticationDataBySecret->execute($secret);

        $event = $this->eventForLogging->execute($authenticationData->getAdminId());
        $event->setAction(self::ACTION);

        $customerId = $authenticationData->getCustomerId();
        $email = $this->customerRepository->getById($customerId)->getEmail();

        try {
            $proceed($secret);
            $info = __('Logged in as customer: id = %1, email = %2, ', $customerId, $email) . $event->getInfo();
            $event->setInfo($info);
            $this->eventResource->save($event);
        } catch (LocalizedException $e) {
            $event->setIsSuccess(0);
            $info = __('Try to login as customer id = %1, email = %2, ', $customerId, $email) . $event->getInfo();
            $event->setInfo($info);
            $event->setErrorMessage($e->getLogMessage());
            $this->eventResource->save($event);
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
