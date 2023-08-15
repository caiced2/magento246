<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Controller\Adminhtml\System\Config;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\QuickCheckout\Model\CallbackUrlService;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\AbstractAction;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Update callback URL
 */
class ConfigureCallbackUrl extends AbstractAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Config::config';

    /**
     * @var CallbackUrlService
     */
    private $callbackUrlService;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @param Context $context
     * @param CallbackUrlService $callbackUrlService
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        CallbackUrlService $callbackUrlService,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->callbackUrlService = $callbackUrlService;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $response = ['success' => true];
        $result = $this->resultJsonFactory->create();
        $websiteId = (string)$this->getRequest()->getParam('website_id');

        if ($this->isDefaultScope($websiteId)) {
            $response['success'] = false;
            // phpcs:ignore
            $response['message'] = __('Could not configure callback URL in default scope. Switch scope to \'Main Website\' to configure.');
            return $result->setData($response);
        }

        try {
            $this->callbackUrlService->update($websiteId);
        } catch (NoSuchEntityException $exception) {
            $response['success'] = false;
            $response['message'] = __('Invalid website. Please refresh the page and try again.');
        } catch (InvalidArgumentException $exception) {
            $response['success'] = false;
            $response['message'] = __('Invalid Bolt account configuration.');
        } catch (Exception $exception) {
            $response['success'] = false;
            $response['message'] = __('Unable to update callback URL. Please try again later.');
        } finally {
            return $result->setData($response);
        }
    }

    /**
     * Check if is the default scope
     *
     * @param string $websiteId
     * @return bool
     */
    public function isDefaultScope(string $websiteId): bool
    {
        return empty($websiteId);
    }
}
