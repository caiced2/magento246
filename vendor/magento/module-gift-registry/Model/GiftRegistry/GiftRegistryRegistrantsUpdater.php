<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\GiftRegistry;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Model\Person;
use Magento\GiftRegistry\Model\PersonFactory;
use Magento\GiftRegistry\Model\ResourceModel\Person as PersonResourceModel;

/**
 * Updating the gift registry's registrants
 */
class GiftRegistryRegistrantsUpdater
{
    /**
     * @var PersonFactory
     */
    private $personFactory;

    /**
     * @var PersonResourceModel
     */
    private $personResourceModel;

    /**
     * @param PersonFactory $personFactory
     * @param PersonResourceModel $personResourceModel
     */
    public function __construct(
        PersonFactory $personFactory,
        PersonResourceModel $personResourceModel
    ) {
        $this->personFactory = $personFactory;
        $this->personResourceModel = $personResourceModel;
    }

    /**
     * Managing the registrants information
     *
     * @param array $recipients
     * @param int $giftRegistryId
     *
     * @return void
     *
     * @throws LocalizedException
     * @throws AlreadyExistsException
     */
    public function execute(array $recipients, int $giftRegistryId): void
    {
        $errors = [];

        foreach ($recipients as $recipient) {
            $dynamicAttributes = $recipient['dynamic_attributes'] ?? [];

            foreach ($dynamicAttributes as $dynamicAttribute) {
                $recipient[$dynamicAttribute['code']] = $dynamicAttribute['value'];
            }

            /** @var Person $person */
            $person = $this->personFactory->create();

            if (isset($recipient['id'])) {
                $this->personResourceModel->load($person, (int) $recipient['id']);

                if ((int) $person->getEntityId() !== $giftRegistryId) {
                    $errors[] = __('The registrant with ID=%1 doesn\'t belong to the current gift registry.',
                        [$recipient['id']]
                    );
                    continue;
                }

                $recipient = array_merge($person->getData(), $recipient);
            }

            $person->setData($recipient);
            $person->setEntityId($giftRegistryId);
            $validationErrors = $person->validate();

            if (is_array($validationErrors)) {
                $errors = array_merge($errors, $validationErrors);
                continue;
            }

            $this->personResourceModel->save($person);
        }

        if (!empty($errors)) {
            throw new LocalizedException(__(implode("\n", $errors)));
        }
    }
}
