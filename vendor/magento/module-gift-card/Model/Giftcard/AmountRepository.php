<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Giftcard;

use Magento\Framework\EntityManager\EntityManager;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory as GiftcardAmountFactory;

/**
 * Repository for gift card amounts.
 * @deprecated 101.0.0
 */
class AmountRepository
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var GiftcardAmountFactory
     */
    protected $amountFactory;

    /**
     * @param EntityManager $entityManager
     * @param GiftcardAmountFactory $amountFactory
     */
    public function __construct(
        EntityManager $entityManager,
        GiftcardAmountFactory $amountFactory
    ) {
        $this->entityManager = $entityManager;
        $this->amountFactory = $amountFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(GiftcardAmountInterface $amount)
    {
        return $this->entityManager->save($amount);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(GiftcardAmountInterface $amount)
    {
        return $this->entityManager->delete($amount);
    }

    /**
     * {@inheritdoc}
     */
    public function get($identifier)
    {
        // @todo add registry usage
        return $this->entityManager->load($this->amountFactory->create(), $identifier);
    }
}
