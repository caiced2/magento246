<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Block\Adminhtml\Page\Update;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\StoreIdProviderInterface;

/**
 * Entity for Page store ID provider
 */
class StoreIdProvider implements StoreIdProviderInterface
{
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     * @param PageRepositoryInterface $pageRepository
     */
    public function __construct(
        RequestInterface $request,
        PageRepositoryInterface $pageRepository
    ) {
        $this->request = $request;
        $this->pageRepository = $pageRepository;
    }

    /**
     * Return Page Store ID
     *
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getStoreId(): ?int
    {
        try {
            $storeIds = $this->getPage()->getData('store_id');

            if (!is_array($storeIds)) {
                $storeIds = [$storeIds];
            }

            return !empty($storeIds) ? (int)reset($storeIds) : null;
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__("The page doesn't exist. Verify and try again."));
        }
    }

    /**
     * Return Page by request
     *
     * @return PageInterface
     */
    private function getPage(): PageInterface
    {
        return $this->pageRepository->getById($this->request->getParam('page_id'));
    }
}
