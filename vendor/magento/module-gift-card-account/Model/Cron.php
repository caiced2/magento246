<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount\Collection;
use Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Update gift account states depending on the expiration date
 */
class Cron
{
    /**
     * @var GiftcardaccountFactory
     */
    protected $_giftCAFactory = null;

    /**
     * @var DateTime
     */
    protected $_coreDate = null;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @param GiftcardaccountFactory $giftCAFactory
     * @param DateTime $coreDate
     * @param CollectionFactory|null $collectionFactory
     * @param StoreManagerInterface|null $storeManager
     * @param TimezoneInterface|null $localeDate
     */
    public function __construct(
        GiftcardaccountFactory $giftCAFactory,
        DateTime $coreDate,
        ?CollectionFactory $collectionFactory = null,
        ?StoreManagerInterface $storeManager = null,
        ?TimezoneInterface $localeDate = null
    ) {
        $this->_giftCAFactory = $giftCAFactory;
        $this->_coreDate = $coreDate;
        $this->collectionFactory = $collectionFactory ?? ObjectManager::getInstance()->get(CollectionFactory::class);
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->localeDate = $localeDate ?? ObjectManager::getInstance()->get(TimezoneInterface::class);
    }

    /**
     * Update Gift Card Account states by cron
     *
     * @return $this
     */
    public function updateStates()
    {
        /** * @var Giftcardaccount $model */
        $model = $this->_giftCAFactory->create();
        $states = [
            [
                'from' => Giftcardaccount::STATE_AVAILABLE,
                'to' => Giftcardaccount::STATE_EXPIRED,
                'expired' => true
            ],
            [
                'from' => Giftcardaccount::STATE_EXPIRED,
                'to' => Giftcardaccount::STATE_AVAILABLE,
                'expired' => false
            ]
        ];
        foreach ($states as $state) {
            $ids = [];
            foreach ($this->storeManager->getWebsites() as $website) {
                $timezone = $this->localeDate->getConfigTimezone(
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $website->getDefaultStore()->getId()
                );
                $currentDate = (new \DateTime('now', new \DateTimeZone($timezone)))->format('Y-m-d');

                /** * @var Collection $collection */
                $collection = $this->collectionFactory->create()
                    ->addFieldToFilter(
                        'website_id',
                        $website->getId()
                    )
                    ->addFieldToFilter(
                        'state',
                        $state['from']
                    )
                    ->addFieldToFilter(
                        'date_expires',
                        ['notnull' => true]
                    )
                    ->addFieldToFilter(
                        'date_expires',
                        [$state['expired'] ? 'lt' : 'gteq' => $currentDate]
                    );
                $ids += array_flip($collection->getAllIds());
            }
            $model->updateState(array_keys($ids), $state['to']);
        }
        return $this;
    }
}
