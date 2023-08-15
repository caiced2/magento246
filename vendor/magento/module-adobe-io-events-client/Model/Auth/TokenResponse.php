<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Auth;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\Framework\DataObject;

/**
 * Service data response object
 */
class TokenResponse extends DataObject implements TokenResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getAccessToken(): string
    {
        return (string)$this->getData('access_token');
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken(): string
    {
        return (string)$this->getData('refresh_token');
    }

    /**
     * @inheritDoc
     */
    public function getSub(): string
    {
        return (string)$this->getData('sub');
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return (string)$this->getData('name');
    }

    /**
     * @inheritDoc
     */
    public function getTokenType(): string
    {
        return (string)$this->getData('token_type');
    }

    /**
     * @inheritDoc
     */
    public function getGivenName(): string
    {
        return (string)$this->getData('given_name');
    }

    /**
     * @inheritDoc
     */
    public function getExpiresIn(): int
    {
        return (int)$this->getData('expires_in');
    }

    /**
     * @inheritDoc
     */
    public function getFamilyName(): string
    {
        return (string)$this->getData('family_name');
    }

    /**
     * @inheritDoc
     */
    public function getEmail(): string
    {
        return (string)$this->getData('email');
    }

    /**
     * @inheritDoc
     */
    public function getError(): string
    {
        return (string)$this->getData('error');
    }
}
