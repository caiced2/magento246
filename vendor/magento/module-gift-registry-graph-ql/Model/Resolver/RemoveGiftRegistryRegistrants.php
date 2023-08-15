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
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\GiftRegistry\RemoveRegistrantsFromGiftRegistry;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistry\Model\ResourceModel\Person\Collection;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;

/**
 * Removing registrants to gift registry
 */
class RemoveGiftRegistryRegistrants implements ResolverInterface
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
     * @var RemoveRegistrantsFromGiftRegistry
     */
    private $removeRegistrantsFromGiftRegistry;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param RemoveRegistrantsFromGiftRegistry $removeRegistrantsFromGiftRegistry
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param Uid $idEncoder
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        RemoveRegistrantsFromGiftRegistry $removeRegistrantsFromGiftRegistry,
        GiftRegistryHelper $giftRegistryHelper,
        Uid $idEncoder
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryOutputDataMapper = $giftRegistryOutputDataMapper;
        $this->removeRegistrantsFromGiftRegistry = $removeRegistrantsFromGiftRegistry;
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

        if (!isset($args['registrantsUid'])) {
            throw new GraphQlInputException(__(
                '"%1" value should be specified',
                ['registrantsUid']
            ));
        }
        $registrantsId = $this->decodeRegistrantsUid($args['registrantsUid']);
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
        $this->validateRegistrantsToRemove(
            $giftRegistry->getRegistrantsCollection(),
            $registrantsId
        );

        try {
            $this->removeRegistrantsFromGiftRegistry->execute(
                $registrantsId,
                (int) $giftRegistry->getEntityId()
            );
        } catch (LocalizedException $exception) {
            throw new GraphQlInputException(__($exception->getMessage()));
        } catch (Exception $exception) {
            throw new GraphQlInputException(__(
                'We have encountered some problems removing the registrants.'
            ));
        }

        return [
            'gift_registry' => $this->giftRegistryOutputDataMapper->map(
                $giftRegistry
            )
        ];
    }

    /**
     * Decode registrants UID to ID.
     *
     * @param array $registrantsUid
     * @return array
     * @throws GraphQlInputException
     */
    private function decodeRegistrantsUid(array $registrantsUid): array
    {
        $errors = [];
        $registrantsId = [];

        foreach ($registrantsUid as $registrantUid) {
            if (!$this->idEncoder->isValidBase64($registrantUid)) {
                $errors[] = $registrantUid;
            }
            $registrantsId[] = $this->idEncoder->decode((string) $registrantUid);
        }

        if (!empty($errors)) {
            throw new GraphQlInputException(__(
                'The registrant ID(s)="%1" is not valid.',
                implode(',', $errors)
            ));
        }

        return $registrantsId;
    }

    /**
     * Validate registrants IDs to remove.
     *
     * @param Collection $registrantCollection
     * @param array $registrantsId
     * @throws GraphQlInputException
     */
    private function validateRegistrantsToRemove(
        Collection $registrantCollection,
        array $registrantsId
    ): void {
        $errors = [];
        $existingRegistrants = $registrantCollection->getItems();

        foreach ($registrantsId as $registrantId) {
            if (!array_key_exists($registrantId, $existingRegistrants)) {
                $errors[] = $registrantId;
            }
        }

        if (!empty($errors)) {
            throw new GraphQlInputException(__(
                'The registrant(s) "%1" does not exist.',
                implode(',', $errors)
            ));
        }
    }
}
