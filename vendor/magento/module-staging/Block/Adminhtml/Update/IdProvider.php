<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\UpdateRepositoryInterface;

/**
 * Class IdProvider
 */
class IdProvider
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @param RequestInterface $request
     * @param UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        RequestInterface $request,
        UpdateRepositoryInterface $updateRepository
    ) {
        $this->request = $request;
        $this->updateRepository = $updateRepository;
    }

    /**
     * Return Update Id
     *
     * @return int|null
     */
    public function getUpdateId()
    {
        try {
            $update = $this->updateRepository->get($this->request->getParam('update_id'));
            return $update->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }
}
