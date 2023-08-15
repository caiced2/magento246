<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\DataObject;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;

/**
 * Hydrate sales rule model with data
 */
class Hydrator implements HydratorInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RetrieverInterface
     */
    protected $entityRetriever;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @param Context $context
     * @param RetrieverInterface $entityRetriever
     * @param RuleFactory $ruleFactory ;
     */
    public function __construct(
        Context $context,
        RetrieverInterface $entityRetriever,
        RuleFactory $ruleFactory
    ) {
        $this->context = $context;
        $this->entityRetriever = $entityRetriever;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $data)
    {
        $this->context->getEventManager()->dispatch(
            'adminhtml_controller_salesrule_prepare_save',
            ['request' => $this->context->getRequest()]
        );

        /** @var Rule $entity */
        $entity = $this->ruleFactory->create();
        if (isset($data['rule_id'])) {
            $entity = $this->entityRetriever->getEntity($data['rule_id']);
        }

        $validateResult = $entity->validateData(new DataObject($data));
        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                $this->context->getMessageManager()->addError($errorMessage);
            }
            return false;
        }

        if (isset($data['rule'])) {
            if (isset($data['rule']['conditions'])) {
                $data['conditions'] = $data['rule']['conditions'];
            }
            if (isset($data['rule']['actions'])) {
                $data['actions'] = $data['rule']['actions'];
            }
            unset($data['rule']);
        }

        $entity->loadPost($data);
        $entity = $this->datesSyncronizer($entity);
        return $entity;
    }

    /**
     * The From and To dates in staging have to be synced by the plugin
     *
     * @param Rule $entity
     * @return Rule
     */
    private function datesSyncronizer($entity)
    {
        $entity->getFromDate();
        $entity->getToDate();
        return $entity;
    }
}
