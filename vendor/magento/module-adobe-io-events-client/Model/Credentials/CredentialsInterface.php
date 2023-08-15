<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Credentials;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\Framework\Exception\AuthorizationException;

/**
 * Interface for api credentials based on Authorization type
 */
interface CredentialsInterface
{
    /**
     * Returns client id.
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getClientId(): string;

    /**
     * Returns IMS organization id.
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getImsOrgId(): string;

    /**
     * Returns token response.
     *
     * @return TokenResponseInterface
     * @throws InvalidConfigurationException
     * @throws AuthorizationException
     */
    public function getToken(): TokenResponseInterface;
}
