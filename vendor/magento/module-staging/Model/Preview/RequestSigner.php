<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Staging\Model\Preview;

use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Staging\Model\VersionManager;

/**
 * Generates and validates signatures for preview URLs
 */
class RequestSigner
{
    /**
     * URL parameter containing the signature
     */
    private const SIGNATURE_PARAM_NAME = '__signature';

    /**
     * URL parameter containing the timestamp
     */
    private const TIMESTAMP_PARAM_NAME = '__timestamp';

    /**
     * Duration in seconds a signed URL is valid for
     */
    private const SIGNATURE_LIFETIME = 3600;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param DateTime $dateTime
     * @param UrlHelper $urlHelper
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        DateTime $dateTime,
        UrlHelper $urlHelper,
        EncryptorInterface $encryptor
    ) {
        $this->dateTime = $dateTime;
        $this->urlHelper = $urlHelper;
        $this->encryptor = $encryptor;
    }

    /**
     * Add signature parameters to the given URL
     *
     * @param string $url
     * @return string
     */
    public function signUrl(string $url): string
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
        parse_str(parse_url($url, \PHP_URL_QUERY), $queryParams);

        if (empty($queryParams[VersionManager::PARAM_NAME])) {
            throw new \RuntimeException('URL does not contain required preview version param');
        }

        $params = $this->generateSignatureParams($queryParams[VersionManager::PARAM_NAME]);

        return $this->urlHelper->addRequestParam(
            $url,
            $params->getData()
        );
    }

    /**
     * Validate the signature of the given URL
     *
     * @param string $url
     * @return bool True if valid
     */
    public function validateUrl(string $url): bool
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
        $query = parse_url($url, \PHP_URL_QUERY);

        if (empty($query)) {
            return false;
        }

        parse_str($query, $queryParams);

        if (empty($queryParams[VersionManager::PARAM_NAME])
            || empty($queryParams[self::TIMESTAMP_PARAM_NAME])
            || empty($queryParams[self::SIGNATURE_PARAM_NAME])
        ) {
            return false;
        }

        $currentTimestamp = $this->dateTime->timestamp();
        $providedTimestamp = $queryParams[self::TIMESTAMP_PARAM_NAME];
        $providedSignature = $queryParams[self::SIGNATURE_PARAM_NAME];
        $params = $this->generateSignatureParams($queryParams[VersionManager::PARAM_NAME], $providedTimestamp);

        if ($params->getData(self::SIGNATURE_PARAM_NAME) !== $providedSignature
            || $currentTimestamp - $providedTimestamp > self::SIGNATURE_LIFETIME
        ) {
            return false;
        }

        return true;
    }

    /**
     * Generate the params to be appended to a request
     *
     * @param string $version
     * @param string|null $timestamp Defaults to current time
     * @return DataObject
     */
    public function generateSignatureParams(string $version, string $timestamp = null): DataObject
    {
        $timestamp = $timestamp ?: $this->dateTime->timestamp();
        $signatureData = implode(',', [$version, $timestamp]);
        $signature = $this->encryptor->hash($signatureData);

        return new DataObject(
            [
                self::TIMESTAMP_PARAM_NAME => $timestamp,
                self::SIGNATURE_PARAM_NAME => $signature,
            ]
        );
    }
}
