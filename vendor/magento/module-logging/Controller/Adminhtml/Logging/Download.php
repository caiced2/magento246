<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Controller\Adminhtml\Logging;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Download extends \Magento\Logging\Controller\Adminhtml\Logging
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Logging::backups';

    /**
     * Download archive file
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $archive = $this->_archiveFactory->create()->loadByBaseName($this->getRequest()->getParam('basename'));
        if ($archive->getFilename()) {
            return $this->_fileFactory->create(
                $archive->getBaseName(),
                $archive->getContents(),
                DirectoryList::VAR_DIR,
                $archive->getMimeType()
            );
        }
    }
}
