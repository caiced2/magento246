<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\ResourceModel\Entity\Collection as GiftRegistryCollection;
use Magento\GiftRegistry\Model\ResourceModel\Entity\CollectionFactory as GiftRegistryCollectionFactory;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;

/**
 * Fetches the customer gift registry list
 */
class GiftRegistryList implements ResolverInterface
{
    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @var GiftRegistryCollectionFactory
     */
    private $giftRegistryCollectionFactory;

    /**
     * @var GiftRegistryOutputDataMapper
     */
    private $giftRegistryDataMapper;

    /**
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param GiftRegistryCollectionFactory $giftRegistryCollectionFactory
     * @param GiftRegistryOutputDataMapper $giftRegistryDataMapper
     */
    public function __construct(
        GiftRegistryHelper $giftRegistryHelper,
        GiftRegistryCollectionFactory $giftRegistryCollectionFactory,
        GiftRegistryOutputDataMapper $giftRegistryDataMapper
    ) {
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->giftRegistryCollectionFactory = $giftRegistryCollectionFactory;
        $this->giftRegistryDataMapper = $giftRegistryDataMapper;
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
        if (!$this->giftRegistryHelper->isEnabled()) {
            throw new GraphQlInputException(__(
                'The %1 is not currently available.',
                ['gift registry']
            ));
        }
        $customerId = (int) $context->getUserId() ?? null;

        /* Guest checking */
        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(__(
                'The current user cannot perform operations on %1',
                ['gift registry']
            ));
        }

        /** @var GiftRegistryCollection $collection */
        $collection = $this->giftRegistryCollectionFactory->create();
        $collection->filterByCustomerId($customerId);
        $collection->addRegistryInfo();
        $data = [];
        /** @var Entity $item */
        foreach ($collection->getItems() as $item) {
            $data[] = $this->giftRegistryDataMapper->map($item, true);
        }

        return $data;
    }
}
