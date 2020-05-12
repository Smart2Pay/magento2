<?php
namespace Smart2Pay\GlobalPay\Api\Data;

interface LoggerInterface
{
    const LOG_ID = 'log_id';
    const LOG_TYPE = 'log_type';
    const LOG_MESSAGE = 'log_message';
    const LOG_SOURCE_FILE = 'log_source_file';
    const LOG_SOURCE_FILE_LINE = 'log_source_file_line';
    const LOG_CREATED = 'log_created';

    /**
     * Get log ID
     *
     * @return int|null
     */
    public function getLogId();

    /**
     * Get log type
     *
     * @return string|null
     */
    public function getType();

    /**
     * Get log message
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Get file
     *
     * @return string|null
     */
    public function getFile();

    /**
     * Get file line
     *
     * @return string|null
     */
    public function getFileLine();

    /**
     * Get log creation date and time
     *
     * @return string|null
     */
    public function getCreated();

    /**
     * Set ID
     *
     * @param int $log_id
     * @return \Smart2Pay\GlobalPay\Api\Data\LoggerInterface
     */
    public function setLogID($log_id);

    /**
     * Set log type
     *
     * @param string $type
     * @return \Smart2Pay\GlobalPay\Api\Data\LoggerInterface
     */
    public function setType($type);

    /**
     * Set log message
     *
     * @param string $message
     * @return \Smart2Pay\GlobalPay\Api\Data\LoggerInterface
     */
    public function setMessage($message);

    /**
     * Set file
     *
     * @param string $name
     * @return \Smart2Pay\GlobalPay\Api\Data\LoggerInterface
     */
    public function setFile($file);

    /**
     * Set log name
     *
     * @param string $line
     * @return \Smart2Pay\GlobalPay\Api\Data\LoggerInterface
     */
    public function setFileLine($line);

    /**
     * Set log name
     *
     * @param string $creation
     * @return \Smart2Pay\GlobalPay\Api\Data\LoggerInterface
     */
    public function setCreated($creation);
}
