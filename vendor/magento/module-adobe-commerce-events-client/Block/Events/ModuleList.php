<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Block\Events;

use Magento\AdobeCommerceEventsClient\Event\Collector\CollectorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Renders block with modules and the list of events.
 *
 * @api
 * @since 1.0.0
 */
class ModuleList extends Template
{
    /**
     * @var FullModuleList
     */
    private FullModuleList $fullModuleList;

    /**
     * @var CollectorInterface
     */
    private CollectorInterface $eventCollector;

    /**
     * @var Dir
     */
    private Dir $dir;

    /**
     * @param Context $context
     * @param FullModuleList $fullModuleList
     * @param CollectorInterface $eventCollector
     * @param Dir $dir
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        FullModuleList $fullModuleList,
        CollectorInterface $eventCollector,
        Dir $dir,
        array $data = []
    ) {
        $this->fullModuleList = $fullModuleList;
        $this->eventCollector = $eventCollector;
        $this->dir = $dir;
        parent::__construct($context, $data);
    }

    /**
     * Returns list of modules with collected events.
     *
     * @return array
     */
    public function getModules(): array
    {
        $modules = [];
        foreach ($this->fullModuleList->getAll() as $module) {
            if (strpos($module['name'], CollectorInterface::IGNORED_MODULES) !== false) {
                continue;
            }

            $modulePath = $this->dir->getDir($module['name']);
            $events = $this->eventCollector->collect($modulePath);

            if (!empty($events)) {
                ksort($events);
                $modules[] = [
                    'name' => $module['name'],
                    'events' => $events
                ];
            }
        }
        array_multisort($modules, SORT_ASC, array_column($modules, 'name'));

        return $modules;
    }

    /**
     * Returns module from the request.
     *
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->getRequest()->getParam('module');
    }

    /**
     * Returns event from the request.
     *
     * @return string|null
     */
    public function getEvent(): ?string
    {
        return $this->getRequest()->getParam('event');
    }
}
