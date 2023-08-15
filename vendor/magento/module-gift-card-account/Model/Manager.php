<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\GiftCardAccount\Model\Spi\UsageAttemptFactoryInterface;
use Magento\GiftCardAccount\Model\Spi\UsageAttemptsManagerInterface;

/**
 * @inheritDoc
 */
class Manager implements GiftCardAccountManagerInterface
{
    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $repo;

    /**
     * @var UsageAttemptsManagerInterface
     */
    private $attempts;

    /**
     * @var UsageAttemptFactoryInterface
     */
    private $attemptFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @param GiftCardAccountRepositoryInterface $repo
     * @param UsageAttemptsManagerInterface $attempts
     * @param UsageAttemptFactoryInterface $attemptFactory
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        GiftCardAccountRepositoryInterface $repo,
        UsageAttemptsManagerInterface $attempts,
        UsageAttemptFactoryInterface $attemptFactory,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->repo = $repo;
        $this->attempts = $attempts;
        $this->attemptFactory = $attemptFactory;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function requestByCode(
        string $code,
        ?int $websiteId = null,
        ?float $balanceGTE = null,
        bool $onlyEnabled = true,
        bool $notExpired = true
    ): GiftCardAccountInterface {
        $this->attempts->attempt($this->attemptFactory->create($code));
        $accounts = $this->repo->getList(
            $this->criteriaBuilder->addFilter('code', $code)->setPageSize(1)->create()
        )->getItems();
        if (!$accounts) {
            throw new NoSuchEntityException();
        }
        /** @var Giftcardaccount $account */
        $account = array_pop($accounts);

        try {
            $account->isValid($notExpired, $onlyEnabled, $websiteId, $balanceGTE === 0.0 ? true : $balanceGTE);
        } catch (LocalizedException $exception) {
            throw new \InvalidArgumentException('Gift Card Account is invalid', 0, $exception);
        }

        return $account;
    }
}
