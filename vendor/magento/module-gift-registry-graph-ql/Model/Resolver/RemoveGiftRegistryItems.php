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
use Magento\GiftRegistry\Model\ResourceModel\Item as ItemResourceModel;
use Magento\GiftRegistry\Model\ResourceModel\Item\Collection;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;

/**
 * The GraphQl resolver for removing items to gift registry.
 */
class RemoveGiftRegistryItems implements ResolverInterface
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
     * @var ItemResourceModel
     */
    private $itemResourceModel;

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
     * @param ItemResourceModel $itemResourceModel
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param Uid $idEncoder
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        ItemResourceModel $itemResourceModel,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        GiftRegistryHelper $giftRegistryHelper,
        Uid $idEncoder
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->itemResourceModel = $itemResourceModel;
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

        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(__(
                'The current user cannot perform operations on %1',
                ['gift registry']
            ));
        }

        if (!isset($args['itemsUid'])) {
            throw new GraphQlInputException(__(
                '"%1" value should be specified',
                ['itemsUid']
            ));
        }
        $itemsId = $this->decodeItemsUid($args['itemsUid']);
        $giftRegistry = $this->entityFactory->create();
        $this->entityResourceModel->load(
            $giftRegistry,
            $args['giftRegistryUid'],
            'url_key'
        );

        if (!$giftRegistry->getEntityId()) {
            throw new GraphQlInputException(__(
                'The "%1" ID is incorrect. Verify the ID and try again.',
                ['gift registry']
            ));
        }
        $giftRegistryItemCollection = $giftRegistry->getItemsCollection();
        $this->validateItemsToRemove(
            $giftRegistryItemCollection,
            $itemsId
        );

        try {
            foreach ($itemsId as $itemId) {
                $item = $giftRegistryItemCollection->getItemById($itemId);
                $this->itemResourceModel->delete($item);
            }
        } catch (Exception $exception) {
            throw new GraphQlInputException(__(
                'We couldn\'t delete "%1" items.',
                ['gift registry']
            ));
        }

        return [
            'gift_registry' => $this->giftRegistryOutputDataMapper->map(
                $giftRegistry
            )
        ];
    }

    /**
     * Decode items UID to ID.
     *
     * @param array $itemsUid
     * @return array
     * @throws GraphQlInputException
     */
    private function decodeItemsUid(array $itemsUid): array
    {
        $errors = [];
        $itemsId = [];

        foreach ($itemsUid as $itemUid) {
            if (!$this->idEncoder->isValidBase64($itemUid)) {
                $errors[] = $itemUid;
            }
            $itemsId[] = $this->idEncoder->decode((string) $itemUid);
        }

        if (!empty($errors)) {
            throw new GraphQlInputException(__(
                'The items ID(s)="%1" is not valid.',
                implode(',', $errors)
            ));
        }

        return $itemsId;
    }

    /**
     * Validate items IDs to remove.
     *
     * @param Collection $itemCollection
     * @param array $itemsId
     * @throws GraphQlInputException
     */
    private function validateItemsToRemove(
        Collection $itemCollection,
        array $itemsId
    ): void {
        $errors = [];
        $existingItems = $itemCollection->getItems();

        foreach ($itemsId as $itemId) {
            if (!array_key_exists($itemId, $existingItems)) {
                $errors[] = $itemId;
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
