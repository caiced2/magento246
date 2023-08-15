<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Authorization type options.
 */
class AuthorizationType implements OptionSourceInterface
{
    public const JWT = 'jwt';
    public const OAUTH = 'oauth';

    /**
     * @param array $authorizationTypes
     */
    public function __construct(private array $authorizationTypes)
    {
    }

    /**
     * Returns a list of Authorization type options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->authorizationTypes;
    }
}
