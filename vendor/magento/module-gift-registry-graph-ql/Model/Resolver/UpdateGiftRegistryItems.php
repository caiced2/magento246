<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistry\Model\ResourceModel\Item\Collection;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;
use function array_key_exists;

/**
 * The GraphQl resolver to update the items to gift registry.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateGiftRegistryItems implements ResolverInterface
{
    /**
     * @var GiftRegistryFactory
     */
    private $entityFactory;

    /**
     * @var GiftRegistryResourceModel
     */
    private $entityResourceModel;

    /**
     * @var GiftRegistryOutputDataMapper
     */
    private $giftRegistryOutputDataMapper;

    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param Uid $idEncoder
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        GiftRegistryHelper $giftRegistryHelper,
        Uid $idEncoder
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryOutputDataMapper = $giftRegistryOutputDataMapper;
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->idEncoder = $idEncoder;
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

        if (0 === $customerId) {
            throw new GraphQlAuthorizationException(__(
                'The current user cannot perform operations on %1',
                ['gift registry']
            ));
        }

        $giftRegistry = $this->entityFactory->create();
        $this->entityResourceModel->load(
            $giftRegistry,
            $args['giftRegistryUid'],
            'url_key'
        );

        if (!$giftRegistry->getEntityId()) {
            throw new GraphQlInputException(__(
                'The %1 ID is incorrect. Verify the ID and try again.',
                ['gift registry']
            ));
        }
        $items = $this->prepareItems($args['items']);
        $this->validateItemsToUpdate(
            $giftRegistry->getItemsCollection(),
            $items
        );

        try {
            $giftRegistry->updateItems($items);
        } catch (Exception $exception) {
            throw new GraphQlInputException(__(
                'We couldn\'t update gift registry items.'
            ));
        }

        return [
            'gift_registry' => $this->giftRegistryOutputDataMapper->map(
                $giftRegistry
            )
        ];
    }

    /**
     * Prepare items to update.
     *
     * @param array $itemsData
     * @return array
     * @throws GraphQlInputException
     */
    private function prepareItems(array $itemsData): array
    {
        $items = [];

        foreach ($itemsData as $itemData) {
            $itemId = $this->idEncoder->decode((string) $itemData['gift_registry_item_uid']);
            $items[$itemId] = [
                'qty' => $itemData['quantity']
            ];

            if (isset($itemData['note'])) {
                $items[$itemId]['note'] = $itemData['note'];
            }
        }

        return $items;
    }

    /**
     * Validate items to update.
     *
     * @param Collection $itemCollection
     * @param array $items
     * @throws GraphQlInputException
     */
    private function validateItemsToUpdate(
        Collection $itemCollection,
        array $items
    ): void {
        $errors = [];

        foreach ($items as $itemId => $itemData) {
            if (!array_key_exists($itemId, $itemCollection->getItems())) {
                $errors[] = $this->idEncoder->encode((string) $itemId);
            }
        }

        if (!empty($errors)) {
            throw new GraphQlInputException(__(
                'The item(s) "%1" does not exist.',
                implode(',', $errors)
            ));
        }
    }
}
