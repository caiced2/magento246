<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Api;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\Framework\Exception\AuthorizationException;

/**
 * Get access token from JWT
 *
 * @api
 * @since 1.1.0
 */
interface AccessTokenProviderInterface
{
    /**
     * Call IMS to fetch Access Token from Technical Account JWT
     *
     * @return TokenResponseInterface
     * @throws AuthorizationException
     */
    public function getAccessToken(): TokenResponseInterface;
}
