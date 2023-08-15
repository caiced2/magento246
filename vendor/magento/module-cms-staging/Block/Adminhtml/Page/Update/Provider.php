<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Block\Adminhtml\Page\Update;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface;

/**
 * Class GenericButton
 */
class Provider implements EntityProviderInterface
{
    /**
     * @var PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UrlProviderInterface
     */
    protected $previewProvider;

    /**
     * @param RequestInterface $request
     * @param PageRepositoryInterface $pageRepository
     * @param VersionManager $versionManager
     * @param UrlProviderInterface $previewProvider
     */
    public function __construct(
        RequestInterface $request,
        PageRepositoryInterface $pageRepository,
        VersionManager $versionManager,
        UrlProviderInterface $previewProvider
    ) {
        $this->request = $request;
        $this->pageRepository = $pageRepository;
        $this->versionManager = $versionManager;
        $this->previewProvider = $previewProvider;
    }

    /**
     * Return Page by request
     *
     * @return PageInterface
     */
    protected function getPage()
    {
        return $this->pageRepository->getById($this->request->getParam('page_id'));
    }

    /**
     * Return Page ID
     *
     * @return int|null
     */
    public function getId()
    {
        try {
            return $this->getPage()->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }

    /**
     * Return Page Url
     *
     * @param int $updateId
     * @return null|string
     */
    public function getUrl($updateId)
    {
        try {
            $oldUpdateId = $this->versionManager->getCurrentVersion()->getId();
            $this->versionManager->setCurrentVersionId($updateId);
            $url = $this->previewProvider->getUrl($this->getPage()->getData());
            $this->versionManager->setCurrentVersionId($oldUpdateId);
            return $url;
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }
}
