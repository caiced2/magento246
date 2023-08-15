<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Block\Adminhtml\Update;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;

class Provider implements EntityProviderInterface
{
    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        RequestInterface $request,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->request = $request;
    }

    /**
     * Return the current Sales Rule  Id.
     *
     * @return int|null
     */
    public function getId()
    {
        try {
            $rule = $this->ruleRepository->getById($this->request->getParam('id'));
            return $rule->getRuleId();
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
