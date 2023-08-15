<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update\Grid;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Class ActionsDataProvider
 */
class ActionsDataProvider implements ActionDataProviderInterface
{
    /**
     * @var array
     */
    private $actionsList = [];

    /**
     * @param object[] $actionsList
     */
    public function __construct($actionsList = [])
    {
        $this->actionsList = $actionsList;
    }

    /**
     * @param array $item
     * @return array
     * @throws LocalizedException
     */
    public function getActionData($item = [])
    {
        $actionsData = [];
        foreach ($this->actionsList as $action) {
            /* @var ActionDataProviderInterface $action */
            if (!($action instanceof ActionDataProviderInterface)) {
                throw new LocalizedException(
                    new Phrase(
                        'Action class needs to implement ActionDataProviderInterface. '
                        . 'Verify action class and try again.'
                    )
                );
            }
            $actionsData += $action->getActionData($item);
        }

        return $actionsData;
    }
}
