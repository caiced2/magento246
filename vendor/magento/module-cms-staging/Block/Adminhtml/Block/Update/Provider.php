<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Block\Adminhtml\Block\Update;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;

/**
 * Class GenericButton
 */
class Provider implements EntityProviderInterface
{
    /**
     * @var BlockRepositoryInterface
     */
    protected $blockRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     * @param BlockRepositoryInterface $blockRepository
     */
    public function __construct(
        RequestInterface $request,
        BlockRepositoryInterface $blockRepository
    ) {
        $this->request = $request;
        $this->blockRepository = $blockRepository;
    }

    /**
     * Return Block ID
     *
     * @return int|null
     */
    public function getId()
    {
        try {
            return $this->blockRepository->getById($this->request->getParam('block_id'))->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }

    /**
     * Return Block Url
     *
     * @param int $updateId
     * @return null|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($updateId)
    {
        return null;
    }
}
