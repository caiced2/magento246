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
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistryGraphQl\Model\Resolver\Validator\GiftRegistryValidator;

/**
 * Remove a new customer gift registry
 */
class RemoveGiftRegistry implements ResolverInterface
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
     * @var GiftRegistryValidator
     */
    private $giftRegistryValidator;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param GiftRegistryValidator $giftRegistryValidator
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryHelper $giftRegistryHelper,
        GiftRegistryValidator $giftRegistryValidator
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
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
        try {
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

            if ($customerId !== (int) $giftRegistry->getCustomerId()) {
                throw new GraphQlInputException(__(
                    'You don\'t allow to remove this gift registry.'
                ));
            }
            $this->entityResourceModel->delete($giftRegistry);
        } catch (LocalizedException $exception) {
            throw new GraphQlInputException(__($exception->getMessage()));
        } catch (Exception $exception) {
            throw new GraphQlInputException(__(
                'We couldn\'t delete this gift registry.'
            ));
        }

        return [
            'success' => true
        ];
    }
}
