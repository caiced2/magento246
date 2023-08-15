<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Rule;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for @see Rule
 */
class RuleFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create Rule class instance
     *
     * @param array $data
     * @return RuleInterface
     */
    public function create(array $data = []): RuleInterface
    {
        return $this->objectManager->create(RuleInterface::class, $data);
    }
}
