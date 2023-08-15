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
use Magento\GiftRegistry\Model\GiftRegistry\GiftRegistryRegistrantsUpdater;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryDataMapper;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;

/**
 * Create a new customer gift registry
 */
class CreateGiftRegistry implements ResolverInterface
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
     * @var AddAddressToGiftRegistry
     */
    private $addAddressToGiftRegistry;

    /**
     * @var GiftRegistryRegistrantsUpdater
     */
    private $giftRegistryRegistrantsUpdater;

    /**
     * @var GiftRegistryDataMapper
     */
    private $giftRegistryDataMapper;

    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param AddAddressToGiftRegistry $addAddressToGiftRegistry
     * @param GiftRegistryRegistrantsUpdater $giftRegistryRegistrantsUpdater
     * @param GiftRegistryDataMapper $giftRegistryDataMapper
     * @param GiftRegistryHelper $giftRegistryHelper
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        AddAddressToGiftRegistry $addAddressToGiftRegistry,
        GiftRegistryRegistrantsUpdater $giftRegistryRegistrantsUpdater,
        GiftRegistryDataMapper $giftRegistryDataMapper,
        GiftRegistryHelper $giftRegistryHelper
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryOutputDataMapper = $giftRegistryOutputDataMapper;
        $this->addAddressToGiftRegistry = $addAddressToGiftRegistry;
        $this->giftRegistryRegistrantsUpdater = $giftRegistryRegistrantsUpdater;
        $this->giftRegistryDataMapper = $giftRegistryDataMapper;
        $this->giftRegistryHelper = $giftRegistryHelper;
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
        $giftRegistryData = $this->giftRegistryDataMapper->map(
            $args['giftRegistry']
        );
        $giftRegistry = $this->entityFactory->create();

        if ($giftRegistry->setTypeById($giftRegistryData['type_id']) === false) {
            throw new GraphQlInputException(__(
                'The type is incorrect. Verify and try again.'
            ));
        }
        $giftRegistry->importData($giftRegistryData);
        $giftRegistry->setCustomerId($customerId);
        $addressData = $giftRegistryData['shipping_address'] ?? [];

        if (empty($addressData) || count($addressData) > 1) {
            throw new GraphQlInputException(__(
                'Either address data or address ID should be provided.'
            ));
        }

        try {
            $giftRegistry = $this->addAddressToGiftRegistry->execute(
                $giftRegistry,
                $addressData
            );
            $this->entityResourceModel->save($giftRegistry);
            $this->giftRegistryRegistrantsUpdater->execute(
                $giftRegistryData['registrants'],
                (int) $giftRegistry->getEntityId()
            );
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
