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
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;

/**
 * Share the customer gift registry
 */
class ShareGiftRegistry implements ResolverInterface
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
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryHelper $giftRegistryHelper
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryHelper $giftRegistryHelper
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
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
        $customerId = (int) $context->getUserId() ?? null;

        if (!$this->giftRegistryHelper->isEnabled()) {
            throw new GraphQlInputException(__(
                'The %1 is not currently available.',
                ['gift registry']
            ));
        }

        if (null === $customerId || 0 === $customerId) {
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
                'The gift registry ID is incorrect. Verify the ID and try again.'
            ));
        }

        if ($customerId !== (int) $giftRegistry->getCustomerId()) {
            throw new GraphQlAuthorizationException(__(
                'The gift registry ID is incorrect. Verify the ID and try again.'
            ));
        }

        $data = [
            'sender_name' => $args['sender']['name'],
            'sender_message' => $args['sender']['message'],
            'recipients' => $args['invitees']
        ];
        $giftRegistry->addData($data);
        $result = $giftRegistry->sendShareRegistryEmails();
        $successfullySent = $result->getData('is_success');

        if ($successfullySent === false) {
            throw new GraphQlInputException(__(
                $result->getData('error_message')
            ));
        }

        return [
            'is_shared' => $successfullySent
        ];
    }
}
