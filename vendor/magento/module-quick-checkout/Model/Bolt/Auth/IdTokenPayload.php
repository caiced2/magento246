<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Bolt\Auth;

class IdTokenPayload
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var bool
     */
    private $isEmailVerified;

    /**
     * DecodedOauthToken class constructor
     *
     * @param string $email
     * @param bool $isEmailVerified
     */
    public function __construct(string $email, bool $isEmailVerified = false)
    {
        $this->email = $email;
        $this->isEmailVerified = $isEmailVerified;
    }

    /**
     * Get the email of the account
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Returns true if the email is verified
     *
     * @return bool
     */
    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }
}
