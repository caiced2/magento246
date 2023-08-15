<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Model;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\UrlCoder;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Swat\Api\Data\JwtInterface;
use Magento\User\Model\User;

/**
 * Model class for a JWT
 */
class Jwt implements JwtInterface
{
    const CONFIG_JWT_ALG = 'swat/jwt/alg';
    const CONFIG_JWT_EXP = 'swat/jwt/exp';

    /** @var Context */
    private $context;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var Base64Json */
    private $base64Json;

    /** @var UrlCoder */
    private $urlCoder;

    /** @var SwatKeyPair */
    private $swatKeyPair;

    /** @var string */
    private $tokenString;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Base64Json $base64Json
     * @param UrlCoder $urlCoder
     * @param SwatKeyPair $swatKeyPair
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Base64Json $base64Json,
        UrlCoder $urlCoder,
        SwatKeyPair $swatKeyPair
    ) {
        $this->context = $context;
        $this->scopeConfig = $scopeConfig;
        $this->base64Json = $base64Json;
        $this->urlCoder = $urlCoder;
        $this->swatKeyPair = $swatKeyPair;
    }

    /**
     * @inheritDoc
     */
    public function loadToken(string $jwt): bool
    {
        if (substr_count($jwt, '.') < 2) {
            return false;
        }
        $this->tokenString = $jwt;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): array
    {
        if ($this->tokenString === null) {
            $this->generateToken();
        }
        list(, $base64Payload, ) = explode('.', $this->tokenString);
        return $this->base64Json->unserialize($base64Payload);
    }

    /**
     * @inheritDoc
     */
    public function getTokenString(): string
    {
        if ($this->tokenString === null) {
            $this->generateToken();
        }
        return $this->tokenString;
    }

    /**
     * Creates the JWT
     */
    private function generateToken()
    {
        // Create JWT token
        $issuer = $this->context->getBackendUrl()->getBaseUrl();
        $alg = $this->scopeConfig->getValue(self::CONFIG_JWT_ALG);
        $exp = time() + $this->scopeConfig->getValue(self::CONFIG_JWT_EXP);
        $header = [
            'alg' => $alg,
            'typ' => 'JWT',
            'kid' => 1,
            'iss' => $issuer,
            'exp' => $exp
        ];

        // Create the token payload
        /** @var User $user*/
        $user = $this->context->getAuth()->getUser();
        $payload = [
            'iss' => $issuer,
            'exp' => $exp,
            'sub' => $user->getId()
        ];

        // Encode Header
        $base64UrlHeader = $this->base64Json->serialize($header);

        // Encode Payload
        $base64UrlPayload = $this->base64Json->serialize($payload);

        // Create Signature Hash
        openssl_sign(
            $base64UrlHeader . '.' . $base64UrlPayload,
            $signature,
            $this->swatKeyPair->getPrivateKey(),
            OPENSSL_ALGO_SHA256
        );

        // Encode Signature to Base64Url String
        $base64UrlSignature = base64_encode($signature);

        // Create JWT
        $this->tokenString = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        list($base64UrlHeader, , ) = explode('.', $this->tokenString);
        $header = $this->base64Json->unserialize($base64UrlHeader);
        return (isset($header['exp']) && time() < $header['exp']);
    }

    /**
     * @inheritDoc
     */
    public function isSignatureValid(): bool
    {
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = explode('.', $this->tokenString);
        openssl_sign(
            $base64UrlHeader . '.' . $base64UrlPayload,
            $verifySignature,
            $this->swatKeyPair->getPrivateKey(),
            OPENSSL_ALGO_SHA256
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $passedSignature = base64_decode($base64UrlSignature);
        return ($passedSignature === $verifySignature);
    }
}
