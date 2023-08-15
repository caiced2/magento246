<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use Magento\Payment\Gateway\Http\ClientException;

/**
 * Process error responses, see https://help.bolt.com/developers/references/error-codes for the list of errors
 */
class ErrorProcessor
{
    /**
     * Process error response
     *
     * @param array $errors
     * @return string
     */
    public function process(array $errors) : string
    {
        $defaultErrorCode = -1;
        $defaultErrorMessage = __('An error happened when processing the request. Try again later.');

        $errorsToDisplay = [];
        foreach ($errors as $error) {
            if (!isset($error['code'])) {
                continue;
            }
            $message = $this->getErrorByCode((int) $error['code']);
            if ($message) {
                $errorsToDisplay[$error['code']] = $message;
            } else {
                $errorsToDisplay[$defaultErrorCode] = $defaultErrorMessage;
            }
        }
        if (empty($errorsToDisplay)) {
            $errorsToDisplay[$defaultErrorCode] = $defaultErrorMessage;
        }

        return implode(' ', $errorsToDisplay);
    }

    /**
     * Get error by code
     *
     * @param int $code
     * @return string
     */
    private function getErrorByCode(int $code) : string
    {
        return array_key_exists($code, $this->getErrors()) ? (string) $this->getErrors()[$code] : '';
    }

    /**
     * Get errors
     *
     * @return array
     */
    private function getErrors() : array
    {
        return [
            4 => __('Invalid address. Country is not supported.'),
            5 => __('Payment declined.'),
            7 => __('A valid email address must be provided.'),
            12 => __('Invalid address. A valid phone number must be provided.'),
            13 => __('Invalid address. A valid zip/postal code must be provided.'),
            17 => __('Payment declined.'),
            43 => __('Invalid address. PO boxes are not supported.'),
            44 => __('Invalid address. Select or enter a different address.'),
            1013 => __('Payment declined.'),
            1000003 => __('Payment declined.'),
            1000009 => __('Payment declined.'),
            1000012 => __('Payment declined.'),
            1000013 => __('Payment declined.'),
            1000014 => __('Payment declined.'),
            1000015 => __('Payment declined.'),
            1000016 => __('Payment declined.'),
        ];
    }
}
