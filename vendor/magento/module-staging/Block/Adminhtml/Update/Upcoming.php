<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update;

use Magento\Framework\AuthorizationInterface;

class Upcoming extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Entity\EntityProviderInterface
     */
    protected $entityProvider;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * Acl resource and action for block drawing in different Staging modules
     *
     * @var string
     */
    protected $aclResourceAction;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Entity\EntityProviderInterface $entityProvider
     * @param AuthorizationInterface $authorization
     * @param string $aclResourceAction
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface $entityProvider,
        AuthorizationInterface $authorization,
        $aclResourceAction = '',
        array $data = []
    ) {
        $this->entityProvider = $entityProvider;
        $this->authorization = $authorization;
        $this->aclResourceAction = $aclResourceAction;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function toHtml()
    {
        if (!$this->entityProvider->getId() || !$this->isOperationAllowed()) {
            return '';
        }
        return $this->getChildHtml();
    }

    /**
     * @return bool
     */
    private function isOperationAllowed()
    {
        if ($this->aclResourceAction) {
            return $this->authorization->isAllowed($this->aclResourceAction);
        }

        return true;
    }
}
