<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Swat\Controller\Key;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\ResultFactory;
use Magento\Swat\Model\SwatKeyPair;

/**
 * Controller class for retrieving the SWAT public key
 */
class Index extends Action implements HttpGetActionInterface
{
    /** @var SwatKeyPair */
    private $swatKeyPair;

    /**
     * @param Context $context
     * @param SwatKeyPair $swatKeyPair
     */
    public function __construct(
        Context $context,
        SwatKeyPair $swatKeyPair
    ) {
        $this->swatKeyPair = $swatKeyPair;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var ResultJson $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $resultJson->setData($this->swatKeyPair->getJwks());
    }
}
