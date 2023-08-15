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
use Magento\GiftRegistry\Model\GiftRegistry\GiftRegistryRegistrantsUpdater;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;
use Magento\GiftRegistryGraphQl\Model\Resolver\Validator\GiftRegistryValidator;

/**
 * Adding registrants to gift registry
 */
class AddGiftRegistryRegistrants implements ResolverInterface
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
     * @var GiftRegistryRegistrantsUpdater
     */
    private $manageGiftRegistryRegistrants;

    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @var GiftRegistryValidator
     */
    private $giftRegistryValidator;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param GiftRegistryRegistrantsUpdater $manageGiftRegistryRegistrants
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param GiftRegistryValidator $giftRegistryValidator
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        GiftRegistryRegistrantsUpdater $manageGiftRegistryRegistrants,
        GiftRegistryHelper $giftRegistryHelper,
        GiftRegistryValidator $giftRegistryValidator
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryOutputDataMapper = $giftRegistryOutputDataMapper;
        $this->manageGiftRegistryRegistrants = $manageGiftRegistryRegistrants;
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->giftRegistryValidator = $giftRegistryValidator;
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
        $this->giftRegistryValidator->validate($context, $customerId);

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
        $recipientsCount = $giftRegistry->getRegistrantsCollection()->getSize()
            + count($args['registrants']);
        $maxRegistrants = $this->giftRegistryHelper->getMaxRegistrant();

        if ($recipientsCount > $maxRegistrants) {
            throw new GraphQlAuthorizationException(__(
                'You can\'t add more than %1 recipients for this event.',
                [$maxRegistrants]
            ));
        }

        try {
            $this->manageGiftRegistryRegistrants->execute(
                $args['registrants'],
                (int) $giftRegistry->getEntityId()
            );
            $giftRegistry->sendNewRegistryEmail();
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
