<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Controller\Ajax;

use Magento\Backend\App\Action\Context;
use Magento\Banner\Model\Banner\DataFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Response;

/**
 * Banner loading
 */
class Load extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var RawFactory
     */
    protected $rawFactory;

    /**
     * @var DataFactory
     */
    protected $dataFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param RawFactory $rawFactory
     * @param DataFactory $dataFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        RawFactory $rawFactory,
        DataFactory $dataFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->rawFactory = $rawFactory;
        $this->dataFactory = $dataFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $dataObject = ($this->getRequest()->getParam('data'))
            ? $this->dataFactory->create(['data' => $this->getRequest()->getParam('data')])
            : $this->dataFactory->create();
        $resultJson = $this->jsonFactory->create();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $resultJson->setStatusHeader(
                Response::STATUS_CODE_400,
                AbstractMessage::VERSION_11,
                'Invalid Request'
            );
            $response = ['message' => __('Invalid Request')];
        } else {
            $response = ['data' => $dataObject->getSectionData()];
        }
        return $resultJson->setData($response);
    }
}
