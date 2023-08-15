<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Setup;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\VersionManagerFactory;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CmsSetup
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var UpdateInterfaceFactory
     */
    protected $updateFactory;

    /**
     * @var PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ModuleDataSetupInterface
     */
    protected $setup;

    /**
     * @var array
     */
    protected $pages = [];

    /**
     * @param VersionManagerFactory $versionManagerFactory
     * @param UpdateRepositoryInterface $updateRepository
     * @param UpdateInterfaceFactory $updateFactory
     * @param PageRepositoryInterface $pageRepository
     * @param State $state
     * @param LoggerInterface $logger
     */
    public function __construct(
        VersionManagerFactory $versionManagerFactory,
        UpdateRepositoryInterface $updateRepository,
        UpdateInterfaceFactory $updateFactory,
        PageRepositoryInterface $pageRepository,
        State $state,
        LoggerInterface $logger
    ) {
        $this->versionManager = $versionManagerFactory->create();
        $this->updateRepository = $updateRepository;
        $this->updateFactory = $updateFactory;
        $this->pageRepository = $pageRepository;
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    public function execute(ModuleDataSetupInterface $setup)
    {
        $this->setup = $setup;

        $this->state->emulateAreaCode(
            FrontNameResolver::AREA_CODE,
            [$this, 'process']
        );
    }

    /**
     * Apply update operations
     *
     * @return void
     */
    public function process()
    {
        try {
            $this->initList();
            $this->generateUpdates();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * Load CMS Page data
     *
     * @return array
     */
    protected function loadCmsPages()
    {
        $select = $this->setup->getConnection()->select()->from(
            ['cp' => $this->setup->getTable('cms_page')]
        );

        $select->where('cp.custom_theme_from IS NOT NULL')
            ->orWhere('cp.custom_theme_to IS NOT NULL');

        return $this->setup->getConnection()->fetchAll($select);
    }

    /**
     * Initialize CMS Page data
     *
     * @return void
     */
    protected function initList()
    {
        $pages = $this->loadCmsPages();

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $timestampNow = $date->getTimestamp();

        foreach ($pages as $page) {
            $timestampEnd = $this->getTimestamp($page, 'custom_theme_to');
            if ($timestampEnd && $timestampEnd <= $timestampNow) { //if schedule update was finished
                continue;
            }

            $timestampStart = $this->getTimestamp($page, 'custom_theme_from');

            $page['timestamp_start'] = $timestampStart ?: $timestampNow;
            $page['timestamp_end']   = $timestampEnd;

            if ($page['timestamp_start'] <= $timestampNow) {
                $page['timestamp_start'] = $timestampNow + 60;
            }

            if (isset($page['timestamp_end'])
                && $page['timestamp_end'] <= $page['timestamp_start']
            ) {
                continue;
            }

            $this->pages[$page['page_id']] = $page;
        }
    }

    /**
     * Retrieve timestamp
     *
     * @param array $page
     * @param string $identifier
     * @return int|null
     */
    protected function getTimestamp(array $page, $identifier)
    {
        if (isset($page[$identifier]) && !empty($page[$identifier])) {
            $date = new \DateTime($page[$identifier], new \DateTimeZone('UTC'));
            return $date->getTimestamp();
        }
        return null;
    }

    /**
     * Generate updates
     *
     * @return void
     */
    protected function generateUpdates()
    {
        $originVersionId = $this->versionManager->getVersion()->getId();

        foreach ($this->pages as $data) {
            try {
                $this->versionManager->setCurrentVersionId($originVersionId);

                $originPage = $this->pageRepository->getById($data['page_id']);
                $page = clone $originPage;

                $this->resetOriginPage($originPage);
                $this->updatePage($page);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        $this->versionManager->setCurrentVersionId($originVersionId);
    }

    /**
     * Update CMS Page
     *
     * @param PageInterface $page
     * @return void
     */
    protected function updatePage(PageInterface $page)
    {
        if (!$page->getCustomLayoutUpdateXml()
            && !$page->getCustomRootTemplate()
            && !$page->getCustomTheme()
        ) {
            return;
        }

        if ($page->getCustomLayoutUpdateXml()) {
            $page->setLayoutUpdateXml($page->getCustomLayoutUpdateXml());
            $page->setCustomLayoutUpdateXml(null);
        }
        if ($page->getCustomRootTemplate()) {
            $page->setPageLayout($page->getCustomRootTemplate());
            $page->setCustomRootTemplate(null);
        }
        if (!$page->getCustomTheme()) {
            $page->setCustomThemeFrom(null);
            $page->setCustomThemeTo(null);
        }

        $versionId = $this->createUpdate($page)->getId();

        $this->versionManager->setCurrentVersionId($versionId);

        $page->setData('row_id', false);
        $page->setData('created_in', $versionId);

        $this->pageRepository->save($page);
    }

    /**
     * Reset origin CMS Page
     *
     * @param PageInterface $originPage
     * @return void
     */
    protected function resetOriginPage(PageInterface $originPage)
    {
        $originPage->setCustomTheme(null);
        $originPage->setCustomThemeFrom(null);
        $originPage->setCustomThemeTo(null);
        $originPage->setCustomRootTemplate(null);
        $originPage->setCustomLayoutUpdateXml(null);

        $this->pageRepository->save($originPage);
    }

    /**
     * Create update
     *
     * @param PageInterface $page
     * @return UpdateInterface
     */
    protected function createUpdate(PageInterface $page)
    {
        $timestampStart = $this->pages[$page->getId()]['timestamp_start'];
        $timestampEnd = isset($this->pages[$page->getId()]['timestamp_end'])
            ? $this->pages[$page->getId()]['timestamp_end']
            : null;

        /** @var UpdateInterface $update */
        $update = $this->updateFactory->create();
        $update->setName($page->getTitle());

        $date = new \DateTime('@' . $timestampStart, new \DateTimeZone('UTC'));
        $update->setStartTime($date->format('Y-m-d H:i:s'));

        if ($timestampEnd) {
            $date = new \DateTime('@' . $timestampEnd, new \DateTimeZone('UTC'));
            $update->setEndTime($date->format('Y-m-d 23:59:59'));
        }

        $update->setIsCampaign(false);
        $this->updateRepository->save($update);
        return $update;
    }
}
