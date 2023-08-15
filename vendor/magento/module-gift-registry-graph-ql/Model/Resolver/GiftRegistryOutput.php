<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Resolves gift registry output
 */
class GiftRegistryOutput implements TypeResolverInterface
{
    /**
     * GiftRegistryOutput
     */
    private const TYPE = 'GiftRegistryOutput';

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        return self::TYPE;
    }
}
