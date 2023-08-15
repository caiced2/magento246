<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CmsStaging\Model\ResourceModel\Grid\Collection;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\UpdateRepositoryInterface;

class UpdatePlugin
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
     * UpdatePlugin constructor.
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
     * Set flag 'disable_staging_preview'
     *
     * @param \Magento\Cms\Model\ResourceModel\AbstractCollection $subject
     * @return void
     */
    public function beforeGetItems(\Magento\Cms\Model\ResourceModel\AbstractCollection $subject)
    {
        $updateId = $this->request->getParam('update_id');
        if ($updateId) {
            try {
                $update = $this->updateRepository->get($updateId);
                if ($update->getId()) {
                    $subject->getSelect()->setPart('disable_staging_preview', true);
                }
            } catch (NoSuchEntityException $e) {
            }
        }
    }
}
