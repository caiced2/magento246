<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Model\ResourceModel\Type\Collection as TypeCollection;
use Magento\GiftRegistry\Model\ResourceModel\Type\CollectionFactory as TypeCollectionFactory;
use Magento\GiftRegistry\Model\Type;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Fetches the customer gift registry types
 */
class GiftRegistryTypes implements ResolverInterface
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var TypeCollectionFactory
     */
    private $typeCollectionFactory;

    /**
     * @param Uid $idEncoder
     * @param TypeCollectionFactory $typeCollectionFactory
     */
    public function __construct(
        Uid $idEncoder,
        TypeCollectionFactory $typeCollectionFactory
    ) {
        $this->idEncoder = $idEncoder;
        $this->typeCollectionFactory = $typeCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var TypeCollection $typeCollection */
        $typeCollection = $this->typeCollectionFactory->create();
        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();
        $typeCollection->addStoreData((int) $store->getId())
            ->applyListedFilter()
            ->applySortOrder();

        $data = [];
        /** @var Type $type */
        foreach ($typeCollection->getItems() as $type) {
            $data[] = [
                'uid' => $this->idEncoder->encode((string) $type->getTypeId()),
                'label' => $type->getLabel(),
                'typeId' => $type->getTypeId()
            ];
        }

        return $data;
    }
}
