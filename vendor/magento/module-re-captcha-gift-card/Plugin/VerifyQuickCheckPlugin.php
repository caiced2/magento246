<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\GiftCardAccount\Controller\Cart\QuickCheck;
use Magento\ReCaptchaGiftCard\Model\QuickCheck\ErrorProcessor;
use Magento\ReCaptchaGiftCard\Model\QuickCheck\ReCaptchaResolver;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolver;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\Framework\View\Result\LayoutFactory;

/**
 * Giftcard Quick Check plugin
 *
 */
class VerifyQuickCheckPlugin
{
    /**
     * Recaptcha key
     */
    private const RECAPTCHA_KEY = 'giftcard';

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidationConfigResolverInterface
     */
    private $validationConfigResolver;

    /**
     * @var ValidatorInterface
     */
    private $captchaValidator;

    /**
     * @var bool
     */
    private $shallProceed = true;

    /**
     * @var ErrorProcessor
     */
    private $errorProcessor;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @param ValidatorInterface $captchaValidator
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param LoggerInterface $logger
     * @param ValidationConfigResolver $validationConfigResolver
     * @param ErrorProcessor $errorProcessor
     * @param LayoutFactory $layoutFactory
     **/
    public function __construct(
        ValidatorInterface $captchaValidator,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        LoggerInterface $logger,
        ValidationConfigResolver $validationConfigResolver,
        ErrorProcessor  $errorProcessor,
        LayoutFactory  $layoutFactory
    ) {
        $this->captchaValidator = $captchaValidator;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->logger = $logger;
        $this->validationConfigResolver = $validationConfigResolver;
        $this->errorProcessor = $errorProcessor;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Plugin for QuickCheck controller
     *
     * @param QuickCheck $subject
     * @param \Closure $proceed
     * @return \Closure|null
     * @throws InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(QuickCheck $subject, \Closure  $proceed): ?\Closure
    {
        /** @var ReCaptchaResolver $resolver */
        $resolver = new ReCaptchaResolver();
        if ($this->isCaptchaEnabled->isCaptchaEnabledFor(self::RECAPTCHA_KEY)) {
            /** @var RequestInterface $request */
            $request = $subject->getRequest();
            try {
                $reCaptchaResponse = $resolver->resolve($request);
            } catch (InputException $e) {
                $this->shallProceed = false;
                $this->logger->error($e);
                $this->errorProcessor->processError(
                    [],
                    self::RECAPTCHA_KEY
                );

                if (!$this->shallProceed) {
                    $this->layoutFactory->create();
                     return null;
                } else {
                    return $proceed();
                }
            }

            if (!empty($reCaptchaResponse)) {
                $validationResult = $this->captchaValidator->isValid(
                    $reCaptchaResponse,
                    $this->validationConfigResolver->get(self::RECAPTCHA_KEY)
                );

                if (false === $validationResult->isValid()) {
                    $this->errorProcessor->processError(
                        $validationResult->getErrors(),
                        self::RECAPTCHA_KEY
                    );
                }
            }

        }
        if (!$this->shallProceed) {
            $this->layoutFactory->create();
            return null;
        } else {
            return $proceed();
        }
    }
}
