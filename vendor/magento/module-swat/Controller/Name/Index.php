<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Swat\Controller\Name;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Encryption\UrlCoder;
use Magento\Swat\Model\Jwt;
use Magento\User\Model\UserFactory;

/**
 * Controller class for retrieving the admin user's full name
 */
class Index extends Action implements HttpGetActionInterface
{
    const CONFIG_RSA_PAIR_PATH = 'swat/rsa_keypair';

    const ERROR_RESPONSE = [
        'status' => 'fail',
        'message' => 'invalid jwt'
    ];

    /** @var UrlCoder */
    private $urlCoder;

    /** @var UserFactory */
    private $userFactory;

    /** @var Jwt */
    private $jwt;

    /**
     * @param Context $context
     * @param UrlCoder $urlCoder
     * @param UserFactory $userFactory
     * @param Jwt $jwt
     */
    public function __construct(
        Context $context,
        UrlCoder $urlCoder,
        UserFactory $userFactory,
        Jwt $jwt
    ) {
        $this->urlCoder = $urlCoder;
        $this->userFactory = $userFactory;
        $this->jwt = $jwt;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var ResultJson $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $params = $this->getRequest()->getParams();
        $result = isset($params['jwt']) ? $this->parseJwt($params['jwt']) : self::ERROR_RESPONSE;

        return $resultJson->setData($result);
    }

    /**
     * Parse the JWT for valid contents and return results.
     *
     * @param string $jwtString
     * @return array|string[]
     */
    private function parseJwt(string $jwtString): array
    {
        // decode + and / characters from URL parameter
        $jwtString = $this->urlCoder->decode($jwtString);
        if (!$this->jwt->loadToken($jwtString)) {
            return self::ERROR_RESPONSE;
        }

        if (!$this->jwt->isActive() || !$this->jwt->isSignatureValid()) {
            return self::ERROR_RESPONSE;
        }

        // parse the payload for the user
        $payload = $this->jwt->getPayload();
        if (!isset($payload['sub'])) {
            return self::ERROR_RESPONSE;
        }
        $userId = $payload['sub'];
        $user = $this->userFactory->create()->load($userId);
        return [
            'status' => 'success',
            'admin_name' => $user->getName()
        ];
    }
}
