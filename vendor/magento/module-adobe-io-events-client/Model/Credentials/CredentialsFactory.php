<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Credentials;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Creates credentials object based on a given authorization type.
 */
class CredentialsFactory
{
    /**
     * @param array $credentials
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private array $credentials,
        private ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Creates credentials object based on a given authorization type.
     *
     * @param string $type
     * @return CredentialsInterface
     * @throws NotFoundException
     */
    public function create(string $type): CredentialsInterface
    {
        if (!isset($this->credentials[$type])) {
            throw new NotFoundException(__('Credentials with type %1 are not registered', $type));
        }

        return $this->objectManager->create($this->credentials[$type]);
    }
}
