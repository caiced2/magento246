<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Controller\Adminhtml\Reporting;

use Exception;
use InvalidArgumentException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Filters;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\ReportingService;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_QuickCheckoutAdminPanel::adminpanel';

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ReportingService
     */
    private ReportingService $service;

    /**
     * @var JsonFactory $jsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ReportingService $service
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ReportingService $service,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->service = $service;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();
        $response = [];

        try {
            $response = $this->service->generate($this->extractFilters());
            $result->setHttpResponseCode(200);
        } catch (InvalidArgumentException $exception) {
            $result->setHttpResponseCode(400);
        } catch (Exception $exception) {
            $result->setHttpResponseCode(500);
        } finally {
            $result->setData($response);
            return $result;
        }
    }

    /**
     * Extract the filters from the request query params
     *
     * @return Filters
     */
    public function extractFilters(): Filters
    {
        $params = $this->request->getParams();

        return new Filters(
            $params['start_date'] ?? '',
            $params['end_date'] ?? ''
        );
    }
}
