<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\UsageAttempt;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Spi\Data\UsageAttemptInterface;
use Magento\GiftCardAccount\Model\Spi\UsageAttemptsManagerInterface;
use Magento\Captcha\Helper\Data as CaptchaData;
use Magento\Captcha\Model\DefaultModel as Captcha;
use Magento\Captcha\Observer\CaptchaStringResolver as CaptchaResolver;
use Magento\Framework\App\RequestInterface;

/**
 * @inheritDoc
 */
class UsageAttemptsManager implements UsageAttemptsManagerInterface
{
    /**
     * @var CaptchaData
     */
    private $captchaHelper;

    /**
     * @var CaptchaResolver
     */
    private $stringResolver;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $repository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var bool
     */
    private $forcingCaptchaMode = false;

    /**
     * @param CaptchaData $captchaHelper
     * @param CaptchaResolver $stringResolver
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepository
     * @param GiftCardAccountRepositoryInterface $repository
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        CaptchaData $captchaHelper,
        CaptchaResolver $stringResolver,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        GiftCardAccountRepositoryInterface $repository,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->captchaHelper = $captchaHelper;
        $this->stringResolver = $stringResolver;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->repository = $repository;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * Figure out login ID for logs.
     *
     * @param UsageAttemptInterface $attempt
     *
     * @return string|null
     */
    private function solveLogin(UsageAttemptInterface $attempt): ?string
    {
        if ($attempt->getCustomerId()) {
            try {
                return $this->customerRepository
                    ->getById($attempt->getCustomerId())
                    ->getEmail();
            } catch (NoSuchEntityException $exception) {
                return null;
            }
        }

        return null;
    }

    /**
     * Process a user's attempt to request a gift card by code.
     *
     * @param UsageAttemptInterface $attempt
     * @throws TooManyAttemptsException
     *
     * @return void
     */
    private function registerAttempt(UsageAttemptInterface $attempt): void
    {
        $formId = 'gift_code_request';
        //Captcha instance for gift card code actions.
        $captcha = $this->captchaHelper->getCaptcha($formId);
        $login = $this->solveLogin($attempt);
        $required = $captcha instanceof Captcha && $captcha->isRequired($login);
        $correctCaptcha = false;
        $value = $this->stringResolver->resolve($this->request, $formId);
        if ($value) {
            $correctCaptcha = $captcha->isCorrect($value);
        }

        //Logging only invalid code requests
        $found = $this->repository->getList(
            $this->criteriaBuilder->addFilter('code', $attempt->getCode())->setPageSize(1)->create()
        )->getItems();
        if (!$found) {
            $captcha->logAttempt($login);
        }
        //We can proceed if number of attempts are not above
        //a limit (if it's set) or a valid captcha is provided.
        $valid = !$required || $correctCaptcha;
        if (!$valid) {
            if ($value && !$correctCaptcha) {
                $message = __('Incorrect CAPTCHA');
            } else {
                $message = __('Too many attempts, please try again later.');
            }
            throw new TooManyAttemptsException($message);
        }
    }

    /**
     * @inheritDoc
     */
    public function attempt(UsageAttemptInterface $attempt): void
    {
        $this->forcingCaptchaMode = true;
        try {
            $this->registerAttempt($attempt);
        } finally {
            $this->forcingCaptchaMode = false;
        }
    }

    /**
     * Whether to force captcha mode.
     *
     * Are we currently processing gift card code captcha so we'd need to force
     * after-fail mode for captcha?
     *
     * @return bool
     */
    public function isForcingCaptchaMode(): bool
    {
        return $this->forcingCaptchaMode;
    }
}
