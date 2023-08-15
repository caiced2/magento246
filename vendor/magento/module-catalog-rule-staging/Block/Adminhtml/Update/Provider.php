<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Block\Adminhtml\Update;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;

class Provider implements EntityProviderInterface
{
    /**
     * @var CatalogRuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     * @param CatalogRuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        RequestInterface $request,
        CatalogRuleRepositoryInterface $ruleRepository
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->request = $request;
    }

    /**
     * Return the current Catalog Rule  Id.
     *
     * @return int|null
     */
    public function getId()
    {
        try {
            $catalogRule = $this->ruleRepository->get($this->request->getParam('id'));
            return $catalogRule->getRuleId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }

    /**
     * Return Rule Url
     *
     * @param int $updateId
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($updateId)
    {
        return null;
    }
}
