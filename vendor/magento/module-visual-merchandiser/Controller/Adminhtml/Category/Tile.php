<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Controller\Adminhtml\Category;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Tile extends AbstractGrid implements HttpPostActionInterface
{
    /**
     * @var string
     */
    protected $blockClass = \Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\Tile::class;

    /**
     * @var string
     */
    protected $blockName = 'tile';
}
