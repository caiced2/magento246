<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver\Validator;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;

/**
 * Validate gift registry
 */
class GiftRegistryValidator
{
    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @param GiftRegistryHelper $giftRegistryHelper
     */
    public function __construct(
        GiftRegistryHelper $giftRegistryHelper
    ) {
        $this->giftRegistryHelper = $giftRegistryHelper;
    }

    /**
     * validate gift registry
     * @param ContextInterface $context
     * @param int|null $customerId
     * @return bool
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     */
    public function validate(
        ContextInterface $context,
        int $customerId = null
    ): bool {
        if (!$this->giftRegistryHelper->isEnabled()) {
            throw new GraphQlInputException(__('The %1 is not currently available.', ['gift registry']));
        }

        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(__(
                'The current user cannot perform operations on %1',
                ['gift registry']
            ));
        }
        return true;
    }
}
