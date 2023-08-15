<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Staging\Model\VersionManager;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class RestPlugin
 *
 * The main purpose of this plugin is set version from request to instance of VersionManager
 */
class RestPlugin
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var UserContextInterface
     */
    private UserContextInterface $userContext;

    /**
     * RestPlugin constructor
     * @param VersionManager $versionManager
     * @param Request $request
     * @param UserContextInterface $context
     */
    public function __construct(VersionManager $versionManager, Request $request, UserContextInterface $context)
    {
        $this->versionManager = $versionManager;
        $this->request = $request;
        $this->userContext = $context;
    }

    /**
     * Triggers before original dispatch
     * This method triggers before original \Magento\Webapi\Controller\Rest::dispatch and set version
     * from request params to VersionManager instance
     *
     * @param FrontControllerInterface $subject
     * @param RequestInterface $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request
    ) {
        $params = $this->request->getRequestData();
        try {
            if (empty($params[VersionManager::PARAM_NAME])) {
                return;
            }
            $customerSessionId = $this->userContext && $this->userContext->getUserId() ?
                (int)$this->userContext->getUserId() : 0;
            if (!$customerSessionId) {
                throw new LocalizedException(__('Operation not allowed'));
            } else {
                $this->versionManager->setCurrentVersionId($params[VersionManager::PARAM_NAME]);
            }
        } catch (\Exception $e) {
            return [$request];
        }
    }
}
