<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Rule;

use Magento\Backend\App\Action\Context;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;

class Hydrator implements HydratorInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var RetrieverInterface
     */
    protected $entityRetriever;

    /**
     * @param Context $context
     * @param RuleFactory $ruleFactory
     * @param RetrieverInterface $entityRetriever
     */
    public function __construct(
        Context $context,
        RuleFactory $ruleFactory,
        RetrieverInterface $entityRetriever
    ) {
        $this->context = $context;
        $this->ruleFactory = $ruleFactory;
        $this->entityRetriever = $entityRetriever;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $data)
    {
        $this->context->getEventManager()->dispatch(
            'adminhtml_controller_catalogrule_prepare_save',
            ['request' => $this->context->getRequest()]
        );

        /** set update status to active */
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        /** @var \Magento\CatalogRule\Model\Rule $model */
        $model = $this->ruleFactory->create();
        $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                $this->context->getMessageManager()->addError($errorMessage);
            }
            return false;
        }

        if (isset($data['rule_id'])) {
            $model = $this->entityRetriever->getEntity($data['rule_id']);
        }

        if (isset($data['rule'])) {
            $data['conditions'] = $data['rule']['conditions'];
            unset($data['rule']);
        }

        return $model->loadPost($data);
    }
}
