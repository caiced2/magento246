<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\GiftRegistry;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Model\PersonFactory;
use Magento\GiftRegistry\Model\ResourceModel\Person as PersonResourceModel;

/**
 * Removing registrants from git registry
 */
class RemoveRegistrantsFromGiftRegistry
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
     * Removing registrants from gift registry
     *
     * @param array $recipients
     * @param int $giftRegistryId
     *
     * @return void
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(array $recipients, int $giftRegistryId): void
    {
        $errors = [];

        foreach ($recipients as $recipientId) {
            /** @var Person $person */
            $person = $this->personFactory->create();
            $this->personResourceModel->load($person, $recipientId);

            if (!$person->getId()) {
                $errors[] = __('The registrant with id="%1" was not found.', $recipientId);
            }

            if ((int) $person->getEntityId() === $giftRegistryId) {
                $this->personResourceModel->delete($person);
            }
        }

        if (!empty($errors)) {
            throw new LocalizedException(__(implode("\n", $errors)));
        }
    }
}
