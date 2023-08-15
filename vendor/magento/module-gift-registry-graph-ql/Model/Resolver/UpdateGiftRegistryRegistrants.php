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
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\GiftRegistry\GiftRegistryRegistrantsUpdater;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistry\Model\ResourceModel\Person\Collection;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;
use Magento\GiftRegistryGraphQl\Model\Resolver\Validator\GiftRegistryValidator;

/**
 * Updating the gift registry's registrants
 */
class UpdateGiftRegistryRegistrants implements ResolverInterface
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
    private $giftRegistryRegistrantsUpdater;

    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var GiftRegistryValidator
     */
    private $giftRegistryValidator;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param GiftRegistryRegistrantsUpdater $giftRegistryRegistrantsUpdater
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param GiftRegistryValidator $giftRegistryValidator
     * @param Uid $idEncoder
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        GiftRegistryRegistrantsUpdater $giftRegistryRegistrantsUpdater,
        GiftRegistryHelper $giftRegistryHelper,
        GiftRegistryValidator $giftRegistryValidator,
        Uid $idEncoder
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryOutputDataMapper = $giftRegistryOutputDataMapper;
        $this->giftRegistryRegistrantsUpdater = $giftRegistryRegistrantsUpdater;
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->idEncoder = $idEncoder;
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
            $registrantsData = $this->prepareRegistrantsData(
                $args['registrants']
            );
            $this->validateRegistrants($giftRegistry->getRegistrantsCollection(), $registrantsData);
            $this->giftRegistryRegistrantsUpdater->execute(
                $registrantsData,
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

    /**
     * Prepare registrants data with decoded UIDs if it exists.
     *
     * @param array $registrants
     * @return array
     * @throws GraphQlInputException
     */
    private function prepareRegistrantsData(array $registrants): array
    {
        $registrantsData = [];
        foreach ($registrants as $registrant) {
            $registrantData = $registrant;

            if (isset($registrant['gift_registry_registrant_uid'])) {
                $registrantData['id'] = $this->idEncoder->decode(
                    (string) $registrant['gift_registry_registrant_uid']
                );
                unset($registrantData['gift_registry_registrant_uid']);
            }
            $registrantsData[] = $registrantData;
        }

        return $registrantsData;
    }


    /**
     * Validate registrants before updating.
     *
     * @param Collection $registrantCollection
     * @param array $registrantsData
     *
     * @throws GraphQlInputException
     */
    private function validateRegistrants(
        Collection $registrantCollection,
        array $registrantsData
    ): void {
        $errors = [];

        foreach ($registrantsData as $registrantData) {
            if (!array_key_exists($registrantData['id'], $registrantCollection->getItems())) {
                $errors[] = $this->idEncoder->encode((string) $registrantData['id']);
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
