<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CmsStaging\Ui\Component\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
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
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * UpdatePlugin constructor.
     * @param RequestInterface $request
     * @param UpdateRepositoryInterface $updateRepository
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        RequestInterface $request,
        UpdateRepositoryInterface $updateRepository,
        FilterBuilder $filterBuilder
    ) {
        $this->request = $request;
        $this->updateRepository = $updateRepository;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * Add filter by staging update
     *
     * @param DataProviderInterface $subject
     * @return void
     */
    public function beforeGetSearchResult(DataProviderInterface $subject)
    {
        $updateId = $this->request->getParam('update_id');
        if ($updateId) {
            try {
                $update = $this->updateRepository->get($updateId);
                if ($update->getId()) {
                    $filterBuilder = $this->filterBuilder
                        ->setField('created_in')
                        ->setConditionType('eq')
                        ->setValue($update->getId());
                    $subject->addFilter($filterBuilder->create());
                }
            } catch (NoSuchEntityException $e) {
            }
        }
    }
}
