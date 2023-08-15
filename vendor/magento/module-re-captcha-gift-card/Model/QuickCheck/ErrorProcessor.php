<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Model\QuickCheck;

use Magento\Framework\Exception\LocalizedException;
use Magento\ReCaptchaUi\Model\ErrorMessageConfigInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\ReCaptchaValidationApi\Model\ValidationErrorMessagesProvider;

/**
 * Process error during Giftcard Quickcheck
 *
 * Set "errormessage" in registry
 */
class ErrorProcessor
{
    /**
     * @var ErrorMessageConfigInterface
     */
    private $errorMessageConfig;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidationErrorMessagesProvider
     */
    private $validationErrorMessagesProvider;

    /**
     * @param ErrorMessageConfigInterface $errorMessageConfig
     * @param Registry $coreRegistry
     * @param LoggerInterface $logger
     * @param ValidationErrorMessagesProvider $validationErrorMessagesProvider
     */
    public function __construct(
        ErrorMessageConfigInterface  $errorMessageConfig,
        Registry $coreRegistry,
        LoggerInterface $logger,
        ValidationErrorMessagesProvider $validationErrorMessagesProvider
    ) {
        $this->errorMessageConfig = $errorMessageConfig;
        $this->coreRegistry = $coreRegistry;
        $this->logger = $logger;
        $this->validationErrorMessagesProvider = $validationErrorMessagesProvider;
    }

    /**
     * Use to set 'no dispatch' flag while processing errors
     *
     * @param array $errorMessages
     * @param string $sourceKey
     * @return void
     * @throws LocalizedException
     */
    public function processError(array $errorMessages, string $sourceKey) :void
    {
        $technicalErrorText = $this->errorMessageConfig->getTechnicalFailureMessage();
        $validationErrorText = $this->errorMessageConfig->getValidationFailureMessage();
        $message = !empty($errorMessages) ? $validationErrorText : $technicalErrorText;
        foreach ($errorMessages as $errorMessageCode => $errorMessage) {
            if (!$this->isValidationError($errorMessageCode)) {
                $message = $technicalErrorText;
                $this->logger->error(
                    __(
                        'reCAPTCHA \'%1\' form error: %2',
                        $sourceKey,
                        $errorMessage
                    )
                );
            }
        }

        $this->coreRegistry->unregister('current_giftcardaccount_check_error');
        $this->coreRegistry->register('current_giftcardaccount_check_error', $message);
    }

    /**
     * Use to Verify error code in validation list.
     *
     * @param string $errorMessageCode
     * @return bool
     */
    private function isValidationError(string $errorMessageCode): bool
    {
        return $errorMessageCode !== $this->validationErrorMessagesProvider->getErrorMessage($errorMessageCode);
    }
}
