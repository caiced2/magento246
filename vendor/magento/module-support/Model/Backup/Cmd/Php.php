<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Backup\Cmd;

/**
 * Class generates shell commands for backups
 *
 * @api
 * @since 100.0.2
 */
class Php extends \Magento\Framework\DataObject
{
    /**
     * Script Name
     *
     * @var string
     */
    protected $scriptName;

    /**
     * Script Interpreter
     *
     * @var string
     */
    protected $scriptInterpreter;

    /**
     * @var string
     */
    protected $redirectOutput;

    /**
     * Set Script Interpreter
     *
     * @param string $scriptInterpreter
     * @return string
     */
    public function setScriptInterpreter($scriptInterpreter)
    {
        $this->scriptInterpreter = $scriptInterpreter;
    }

    /**
     * Get Script Interpreter
     *
     * @return string
     */
    public function getScriptInterpreter()
    {
        return $this->scriptInterpreter;
    }

    /**
     * Set Script Name
     *
     * @param string $scriptName
     * @return void
     */
    public function setScriptName($scriptName)
    {
        $this->scriptName = $scriptName;
    }

    /**
     * Get Script Name
     *
     * @return string
     */
    public function getScriptName()
    {
        return $this->scriptName;
    }

    /**
     * Set output
     *
     * Redirect output
     *
     * @param string $output
     * @return void
     */
    public function setRedirectOutput($output)
    {
        $this->redirectOutput = $output;
    }

    /**
     * Get Output
     *
     * @return string
     */
    public function getRedirectOutput()
    {
        return $this->redirectOutput;
    }

    /**
     * Generate command with arguments
     *
     * @param bool $argsWithKeys
     * @param string $equalSeparator
     * @return string
     */
    public function generate($argsWithKeys = true, $equalSeparator = '=')
    {
        $data = $this->getData();
        $args = '';
        foreach ($data as $key => $value) {
            if ($argsWithKeys) {
                if ($value) {
                    $args .= sprintf(' --%s%s%s', $key, $equalSeparator, $value);
                } else {
                    $args .= sprintf(' -%s', $key);
                }
            } else {
                $args .= sprintf(' %s', $value);
            }
        }
        $cmd = $this->getScriptInterpreter() . ' ' . BP . '/'
            . $this->getScriptName() . $args;

        if ($this->redirectOutput) {
            $cmd .= ' > ' . $this->redirectOutput;
        }

        return $cmd;
    }
}
