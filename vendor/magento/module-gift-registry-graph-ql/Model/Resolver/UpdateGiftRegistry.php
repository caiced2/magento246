<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\GiftRegistry\AddAddressToGiftRegistry;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryDataMapper;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;

/**
 * Updating the gift registry
 */
class UpdateGiftRegistry implements ResolverInterface
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
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @var GiftRegistryOutputDataMapper
     */
    private $giftRegistryOutputDataMapper;

    /**
     * @var AddAddressToGiftRegistry
     */
    private $addAddressToGiftRegistry;

    /**
     * @var GiftRegistryDataMapper
     */
    private $giftRegistryDataMapper;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param AddAddressToGiftRegistry $addAddressToGiftRegistry
     * @param GiftRegistryDataMapper $giftRegistryDataMapper
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryHelper $giftRegistryHelper,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        AddAddressToGiftRegistry $addAddressToGiftRegistry,
        GiftRegistryDataMapper $giftRegistryDataMapper
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->giftRegistryOutputDataMapper = $giftRegistryOutputDataMapper;
        $this->addAddressToGiftRegistry = $addAddressToGiftRegistry;
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
        $customerId = (int) $context->getUserId() ?? null;

        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(__(
                'The current user cannot perform operations on %1',
                ['gift registry']
            ));
        }

        if (!$this->giftRegistryHelper->isEnabled()) {
            throw new GraphQlInputException(__(
                'The %1 is not currently available.',
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
                'The gift registry ID is incorrect. Verify the ID and try again.'
            ));
        }
        $giftRegistryData = $this->giftRegistryDataMapper->map(
            $args['giftRegistry']
        );
        $addressData = $giftRegistryData['shipping_address'] ?? [];
        unset($giftRegistryData['shipping_address']);

        if (!empty($addressData)) {
            if (empty($addressData) || count($addressData) > 1) {
                throw new GraphQlInputException(__(
                    'Either address data or address ID should be provided.'
                ));
            }
        }

        try {
            $giftRegistry->addData($giftRegistryData);

            if (!empty($addressData)) {
                $giftRegistry = $this->addAddressToGiftRegistry->execute(
                    $giftRegistry,
                    $addressData
                );
            }

            $this->entityResourceModel->save($giftRegistry);
        } catch (LocalizedException $exception) {
            throw new GraphQlInputException(__($exception->getMessage()));
        } catch (Exception $exception) {
            throw new GraphQlInputException(__(
                'We couldn\'t save this gift registry.'
            ));
        }

        return [
            'gift_registry' => $this->giftRegistryOutputDataMapper->map(
                $giftRegistry
            )
        ];
    }
}
