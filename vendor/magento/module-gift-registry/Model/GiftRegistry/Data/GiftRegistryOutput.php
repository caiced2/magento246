<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\GiftRegistry\Data;

use Magento\GiftRegistry\Model\Entity as GiftRegistry;

/**
 * DTO represent output for \Magento\GiftRegistryGraphQl\Model\Resolver\MoveCartItemsToGiftRegistry
 */
class GiftRegistryOutput
{
    /**
     * @var GiftRegistry
     */
    private $giftRegistry;

    /**
     * @var Error[]
     */
    private $errors;

    /**
     * @param GiftRegistry $giftRegistry
     * @param Error[] $errors
     */
    public function __construct(GiftRegistry $giftRegistry, array $errors)
    {
        $this->giftRegistry = $giftRegistry;
        $this->errors = $errors;
    }

    /**
     * Get GiftRegistry
     *
     * @return GiftRegistry
     */
    public function getGiftRegistry(): GiftRegistry
    {
        return $this->giftRegistry;
    }

    /**
     * Get errors happened during adding products to gift registry
     *
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
