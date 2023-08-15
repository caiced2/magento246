<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Logs;

use Magento\Framework\Exception\FileSystemException;

/**
 * General class for logs reports
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class LogFilesData
{
    /**
     * Logs directory
     */
    public const LOG_DIR = 'log';

    /**#@+
     * Log files
     */
    public const SYSTEM_LOG_FILE = 'system.log';
    public const DEBUG_LOG_FILE = 'debug.log';
    public const EXCEPTION_LOG_FILE = 'exception.log';
    /**#@-*/

    /**#@+
     * Log files data types
     */
    public const LOG_FILES = 'log_files';
    public const SYSTEM_MESSAGES = 'system_messages';
    public const CURRENT_SYSTEM_MESSAGES = 'current_system_messages';
    public const DEBUG_MESSAGES = 'debug_messages';
    public const CURRENT_DEBUG_MESSAGES = 'current_debug_messages';
    public const EXCEPTION_MESSAGES = 'exception_messages';
    public const CURRENT_EXCEPTION_MESSAGES = 'current_exception_messages';
    /**#@-*/

    /**
     * Number of log messages to report
     */
    public const TOP_LOG_MESSAGES_NUMBER_TO_REPORT = 5;

    /**
     * Maximum file size which will be considered to parse log files for entries calculation
     */
    public const MAX_FILE_SIZE_TO_OPEN_FOR_LOG_ENTRIES_CALC = 367001600; // 350MB

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Support\Model\DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * Log files data
     *
     * @var array
     */
    protected $data = [];

    /**
     * System log file messages
     *
     * @var array
     */
    protected $systemLogMessages = [];

    /**
     * Today's system log file messages
     *
     * @var array
     */
    protected $currentSystemLogMessages = [];

    /**
     * Debug log file messages
     *
     * @var array
     */
    protected $debugLogMessages = [];

    /**
     * Today's debug log file messages
     *
     * @var array
     */
    protected $currentDebugLogMessages = [];

    /**
     * Exception log file messages
     *
     * @var array
     */
    protected $exceptionLogMessages = [];

    /**
     * Today's Exception log file messages
     *
     * @var array
     */
    protected $currentExceptionLogMessages = [];

    /**
     * @var bool
     */
    protected $exceptionStarted = false;

    /**
     * @var bool
     */
    protected $exceptionEnded = false;

    /**
     * @var string
     */
    protected $exceptionMessage= '';

    /**
     * Exception stack trace
     *
     * @var string
     */
    protected $exceptionStack = '';

    /**
     * Exception date without time
     *
     * @var string
     */
    protected $exceptionDatePart;

    /**
     * @var string
     */
    protected $exceptionLastDate;

    /**
     * @var string
     */
    protected $currentDate;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Read
     */
    protected $directory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Support\Model\DataFormatter $dataFormatter
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Support\Model\DataFormatter $dataFormatter,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->dataFormatter = $dataFormatter;
        $this->dateTime = $date;
        $this->directory = $filesystem->getDirectoryRead(LogFilesData::LOG_DIR);
        $this->logger = $logger;
    }

    /**
     * Get log files data
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getLogFilesData()
    {
        if ($this->data) {
            return $this->data;
        }

        $filesCount = 0;
        $this->currentDate = $this->dateTime->date('Y-m-d');
        clearstatcache();
        $entry = $this->directory->read();
        foreach ($entry as $file) {
            // Take into account only files with "log" extension
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (!$this->directory->isFile($file) || pathinfo($file, PATHINFO_EXTENSION) != 'log') {
                continue;
            }
            $logEntriesNumber = 0;
            $fileHandler = $this->directory->openFile($file);
            $fileSize = $fileHandler->stat()['size'];
            if (!$fileSize) {
                continue;
            }
            // If file is readable then calculate log entries number
            if ($this->directory->isReadable($file)) {
                // Sometimes file can be very huge, so defend against such case by reading just 350MB of data per file
                $readSize = min($fileSize, self::MAX_FILE_SIZE_TO_OPEN_FOR_LOG_ENTRIES_CALC);
                try {
                    $fileContent = $fileHandler->read($readSize);
                } catch (FileSystemException $e) {
                    continue;
                }
                if ($fileContent === '') {
                    continue;
                }
                // This is regular expression for log file entries like [2015-09-16 15:44:19] main.CRITICAL: ...
                $matched = (int)preg_match_all('~\[([\d-]+)\s([\d:]+)\].*?:\s(.*)~', $fileContent);
                $logEntriesNumber += $matched;
                // Collect system log messages
                if ($file == self::SYSTEM_LOG_FILE
                    && preg_match_all('~\[[\d-]+\s[\d:]+\].*?:\s.*~', $fileContent, $matches)
                ) {
                    $this->getSystemLogMessages($matches);
                    $this->getCurrentSystemLogMessages($matches);
                }
                // Collect debug log messages
                if ($file == self::DEBUG_LOG_FILE
                    && preg_match_all('~\[[\d-]+\s[\d:]+\].*?:\s.*~', $fileContent, $matches)
                ) {
                    $this->getDebugLogMessages($matches);
                    $this->getCurrentDebugLogMessages($matches);
                }
                $fileLines = explode(PHP_EOL, $fileContent);
                // Collect exception log messages
                if ($file == self::EXCEPTION_LOG_FILE) {
                    $this->getExceptionLogs($fileLines);
                }
                // For long files output progress
                $countLines = count($fileLines);
                if ($countLines % 50000 == 0) {
                    $this->logger->info('File "' . $file . '": ' . $countLines . ' lines processed...');
                }
            } else {
                $logEntriesNumber = 'File is not readable';
            }
            if ($fileSize > self::MAX_FILE_SIZE_TO_OPEN_FOR_LOG_ENTRIES_CALC) {
                $logEntriesNumber = 'File is too big. Only '
                    . $this->dataFormatter->formatBytes(self::MAX_FILE_SIZE_TO_OPEN_FOR_LOG_ENTRIES_CALC, 3, 'IEC')
                    . ' of data was read.';
            }
            $modifiedTime = $fileHandler->stat()['mtime'];
            $data = [
                self::LOG_FILES => [
                    $filesCount => [
                        $file,
                        $this->dataFormatter->formatBytes($fileSize, 3, 'IEC'),
                        $logEntriesNumber,
                        $this->dateTime->date('r', $modifiedTime)
                    ]
                ]
            ];
            $fileHandler->close();
            $this->data = array_merge_recursive($this->data, $data);
            $filesCount++;
        }
        $data = [
            self::SYSTEM_MESSAGES => $this->prepareLogMessagesReportData($this->systemLogMessages),
            self::CURRENT_SYSTEM_MESSAGES => $this->prepareLogMessagesReportData($this->currentSystemLogMessages),
            self::DEBUG_MESSAGES => $this->prepareLogMessagesReportData($this->debugLogMessages),
            self::CURRENT_DEBUG_MESSAGES => $this->prepareLogMessagesReportData($this->currentDebugLogMessages),
            self::EXCEPTION_MESSAGES => $this->prepareLogMessagesReportData($this->exceptionLogMessages, true),
            self::CURRENT_EXCEPTION_MESSAGES => $this->prepareLogMessagesReportData(
                $this->currentExceptionLogMessages,
                true
            )
        ];
        $this->data = array_merge($this->data, $data);

        return $this->data;
    }

    /**
     * Retrieve system log messages
     *
     * @param array $matches
     * @return void
     */
    protected function getSystemLogMessages($matches)
    {
        foreach ($matches[0] as $line) {
            preg_match('~\[([\d-]+)\s([\d:]+)\].*?:\s(.*)~im', $line, $matches);
            $lastDate = $matches[1] . ', ' . $matches[2];
            if (!isset($this->systemLogMessages[$matches[3]])) {
                $this->systemLogMessages[$matches[3]] = ['count' => 1, 'last_occurrence_date' => $lastDate];
            } else {
                $this->systemLogMessages[$matches[3]]['count']++;
                $this->systemLogMessages[$matches[3]]['last_occurrence_date'] = $lastDate;
            }
        }
    }

    /**
     * Retrieve today's system log messages
     *
     * @param array $matches
     * @return void
     */
    protected function getCurrentSystemLogMessages($matches)
    {
        foreach ($matches[0] as $line) {
            preg_match('~\[([\d-]+)\s([\d:]+)\].*?:\s(.*)~im', $line, $matches);
            $lastDate = $matches[1] . ', ' . $matches[2];
            if ($matches[1] == $this->currentDate) {
                if (!isset($this->currentSystemLogMessages[$matches[3]])) {
                    $this->currentSystemLogMessages[$matches[3]] = [
                        'count' => 1,
                        'last_occurrence_date' => $lastDate
                    ];
                } else {
                    $this->currentSystemLogMessages[$matches[3]]['count']++;
                    $this->currentSystemLogMessages[$matches[3]]['last_occurrence_date'] = $lastDate;
                }
            }
        }
    }

    /**
     * Retrieve debug log messages
     *
     * @param array $matches
     * @return void
     */
    protected function getDebugLogMessages($matches)
    {
        foreach ($matches[0] as $line) {
            preg_match('~\[([\d-]+)\s([\d:]+)\].*?:\s(.*)~im', $line, $matches);
            $lastDate = $matches[1] . ', ' . $matches[2];
            if (!isset($this->debugLogMessages[$matches[3]])) {
                $this->debugLogMessages[$matches[3]] = ['count' => 1, 'last_occurrence_date' => $lastDate];
            } else {
                $this->debugLogMessages[$matches[3]]['count']++;
                $this->debugLogMessages[$matches[3]]['last_occurrence_date'] = $lastDate;
            }
        }
    }

    /**
     * Retrieve today's debug log messages
     *
     * @param array $matches
     * @return void
     */
    protected function getCurrentDebugLogMessages($matches)
    {
        foreach ($matches[0] as $line) {
            preg_match('~\[([\d-]+)\s([\d:]+)\].*?:\s(.*)~im', $line, $matches);
            $lastDate = $matches[1] . ', ' . $matches[2];
            if ($matches[1] == $this->currentDate) {
                if (!isset($this->currentDebugLogMessages[$matches[3]])) {
                    $this->currentDebugLogMessages[$matches[3]] = [
                        'count' => 1,
                        'last_occurrence_date' => $lastDate
                    ];
                } else {
                    $this->currentDebugLogMessages[$matches[3]]['count']++;
                    $this->currentDebugLogMessages[$matches[3]]['last_occurrence_date'] = $lastDate;
                }
            }
        }
    }

    /**
     * Get exception log messages
     *
     * @param array $lines
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getExceptionLogs($lines)
    {
        foreach ($lines as $line) {
            // Record date
            if ($this->exceptionStarted === false
                && preg_match('~\[([\d-]+)\s([\d:]+)\].*?:\s(.*)~im', $line, $matches)
            ) {
                $this->exceptionStarted = true;
                $this->exceptionLastDate = $matches[1] . ', ' . $matches[2];
                $this->exceptionDatePart = $matches[1];
            }

            // Record message
            if ($this->exceptionStarted === true && $this->exceptionMessage === ''
                && (preg_match('~exception (.+)$~im', $line, $matches)
                    ||
                    preg_match('~\'(.*Exception\' with message\s*(.+))$~im', $line, $matches)
                )
            ) {
                $this->exceptionMessage = $matches[1];
            }

            // Record exception end flag
            if ($this->exceptionEnded === false && $this->exceptionStarted === true
                && preg_match('~^\#[0-9]+\s\{main\}(.*)$~im', $line)
            ) {
                $this->exceptionEnded = true;
            }

            // Record stack trace
            if ($this->exceptionEnded !== true && $this->exceptionMessage !== ''
                && !preg_match('~^(?:Stack trace|Trace)\:\s*$~im', $line)
                && !preg_match('~exception .+$~im', $line)
            ) {
                $this->exceptionStack .= $line . PHP_EOL;
            }

            // Add exception log data
            if ($this->exceptionStarted === true && $this->exceptionEnded === true) {
                $this->getExceptionLogMessages();

                if ($this->exceptionDatePart == $this->currentDate) {
                    $this->getCurrentExceptionLogMessages();
                }

                $this->exceptionStarted = false;
                $this->exceptionEnded = false;
                $this->exceptionMessage = '';
                $this->exceptionStack = '';
            }
        }
    }

    /**
     * Retrieve exception log messages
     *
     * @return void
     */
    protected function getExceptionLogMessages()
    {
        if (!isset($this->exceptionLogMessages[$this->exceptionMessage])) {
            $this->exceptionLogMessages[$this->exceptionMessage] = [
                'count' => 1,
                'last_occurrence_date' => $this->exceptionLastDate,
                'exception_stack' => $this->exceptionStack
            ];
        } else {
            $this->exceptionLogMessages[$this->exceptionMessage]['count']++;
            $this->exceptionLogMessages[$this->exceptionMessage]['last_occurrence_date'] = $this->exceptionLastDate;
            $this->exceptionLogMessages[$this->exceptionMessage]['exception_stack'] = $this->exceptionStack;
        }
    }

    /**
     * Retrieve today's exception log messages
     *
     * @return void
     */
    protected function getCurrentExceptionLogMessages()
    {
        if (!isset($this->currentExceptionLogMessages[$this->exceptionMessage])) {
            $this->currentExceptionLogMessages[$this->exceptionMessage] = [
                'count' => 1,
                'last_occurrence_date' => $this->exceptionLastDate,
                'exception_stack' => $this->exceptionStack
            ];
        } else {
            $this->currentExceptionLogMessages[$this->exceptionMessage]['count']++;
            // @codingStandardsIgnoreStart
            $this->currentExceptionLogMessages[$this->exceptionMessage]['last_occurrence_date'] = $this->exceptionLastDate;
            // @codingStandardsIgnoreEnd
            $this->currentExceptionLogMessages[$this->exceptionMessage]['exception_stack'] = $this->exceptionStack;
        }
    }

    /**
     * Sort and prepare top log messages data for report
     *
     * @param array $messagesData
     * @param bool $exception
     * @return array
     */
    protected function prepareLogMessagesReportData($messagesData, $exception = false)
    {
        $data = [];
        if (empty($messagesData)) {
            return $data;
        }

        $counts = [];
        foreach ($messagesData as $key => $messageData) {
            $counts[$key] = $messageData['count'];
        }

        array_multisort($counts, SORT_DESC, $messagesData);

        $i = 0;
        foreach ($messagesData as $message => $messageData) {
            if ($i == self::TOP_LOG_MESSAGES_NUMBER_TO_REPORT) {
                break;
            }
            if ($exception) {
                $data[] = [
                    $messageData['count'],
                    $message,
                    $messageData['exception_stack'],
                    $messageData['last_occurrence_date']
                ];
            } else {
                $data[] = [
                    $messageData['count'],
                    $message,
                    $messageData['last_occurrence_date']
                ];
            }
            $i++;
        }
        return $data;
    }
}
