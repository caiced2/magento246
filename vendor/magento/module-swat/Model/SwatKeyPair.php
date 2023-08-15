<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Swat\Api\Data\SwatKeyPairInterface;
use phpseclib3\Crypt\RSA;

/**
 * Model class for SWAT key-pair
 */
class SwatKeyPair implements SwatKeyPairInterface
{
    const CONFIG_RSA_PAIR_PATH = 'swat/rsa_keypair';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var WriterInterface  */
    private $configWriter;

    /** @var EncryptorInterface */
    private $encryptor;

    /** @var TypeListInterface */
    private $cacheTypeList;

    /** @var Base64Json */
    private $base64Json;

    /** @var Json */
    private $json;

    /** @var array */
    private $keyPair;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     * @param TypeListInterface $cacheTypeList
     * @param Base64Json $base64Json
     * @param Json $json
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor,
        TypeListInterface $cacheTypeList,
        Base64Json $base64Json,
        Json $json
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
        $this->cacheTypeList = $cacheTypeList;
        $this->base64Json = $base64Json;
        $this->json = $json;
    }

    /**
     * @inheritDoc
     */
    public function getPublicKey(): string
    {
        $this->loadKeyPair();
        return $this->keyPair['publickey'];
    }

    /**
     * @inheritDoc
     */
    public function getPrivateKey(): string
    {
        $this->loadKeyPair();
        return $this->keyPair['privatekey'];
    }

    /**
     * @inheritDoc
     */
    public function getJwks(): array
    {
        return [
            'publicKey' => $this->getPublicKey()
        ];
    }

    /**
     * Loads/regenerate the key pair
     *
     * @return void
     */
    private function loadKeyPair()
    {
        if (!$this->loadKeys()) {
            $this->regenerateKeys();
        }
    }

    /**
     * Loads the key pair
     *
     * @return bool
     */
    private function loadKeys()
    {
        $keyPair = $this->scopeConfig->getValue(self::CONFIG_RSA_PAIR_PATH);
        // Check config for rsa key pair and load if necessary
        if ($keyPair) {
            $this->keyPair = $this->base64Json->unserialize($this->encryptor->decrypt($keyPair));
        }
        return !empty($this->keyPair);
    }

    /**
     * Regenerate the key pair
     *
     * @return void
     */
    private function regenerateKeys()
    {
        // phpstan:ignore "File has calls static method. (phpStaticMethodCalls)"
        $privateKey = RSA::createKey();
        $this->keyPair = [
            'privatekey' => $privateKey->toString($privateKey->getLoadedFormat()),
            'publickey' => $privateKey->getPublicKey()->toString($privateKey->getLoadedFormat())
        ];
        $keyPairJson = $this->base64Json->serialize($this->keyPair);
        $this->configWriter->save(self::CONFIG_RSA_PAIR_PATH, $this->encryptor->encrypt($keyPairJson));
        $this->cacheTypeList->cleanType('config');
    }
}
