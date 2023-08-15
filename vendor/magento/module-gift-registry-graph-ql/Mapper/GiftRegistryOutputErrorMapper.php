<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Mapper;

use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\Enum\DataMapperInterface;
use Magento\GiftRegistry\Model\Entity as GiftRegistry;
use Magento\GiftRegistry\Model\GiftRegistry\Data\Error;

/**
 * Prepares the gift registry error output as associative array
 */
class GiftRegistryOutputErrorMapper
{
    /**
     * @var DataMapperInterface
     */
    private $enumDataMapper;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param DataMapperInterface $enumDataMapper
     * @param Uid $idEncoder
     */
    public function __construct(
        DataMapperInterface $enumDataMapper,
        Uid $idEncoder
    ) {
        $this->enumDataMapper = $enumDataMapper;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Mapping gift registry error
     *
     * @param Error[] $errors
     * @param GiftRegistry $giftRegistry
     *
     * @return array
     */
    public function map(array $errors, GiftRegistry $giftRegistry): array
    {
        return array_map(
            function (Error $error) use ($giftRegistry) {
                return [
                    'code' => $error->getCode(),
                    'message' => $error->getMessage(),
                    'product_uid' => $this->idEncoder->encode((string)$error->getProductId()),
                    'gift_registry_uid' => $giftRegistry->getUrlKey()
                ];
            },
            $errors
        );
    }
}
