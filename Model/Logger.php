<?php

namespace Smart2Pay\GlobalPay\Model;

use Smart2Pay\GlobalPay\Api\Data\LoggerInterface;

/**
 * Class Country
 * @method \Smart2Pay\GlobalPay\Model\ResourceModel\Logger _getResource()
 * @package Smart2Pay\GlobalPay\Model
 */
class Logger extends \Magento\Framework\Model\AbstractModel implements LoggerInterface
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'smart2pay_globalpay_logger';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 'Smart2Pay\GlobalPay\Model\ResourceModel\Logger' );
    }

    /**
     * Log a message
     *
     * @param string $message Message to be logged
     * @param string $type Type of log (info, debug, etc)
     * @param string $transaction_id Transaction related to this log
     * @param string $file File which triggered logging
     * @param string $line What line triggered loggin in the file
     *
     * @return bool Returns true if succes, false if failed
     */
    public function write( $message, $type = 'info', $transaction_id = '', $file = '', $line = '' )
    {
        if( empty( $file ) or empty( $line ) )
        {
            if( ($backtrace = debug_backtrace())
            and !empty( $backtrace[0] )
            and is_array( $backtrace[0] ) )
            {
                $file = $backtrace[0]['file'];
                $line = $backtrace[0]['line'];
            }
        }

        try
        {
            $this->_getResource()->write( $message, $type, $transaction_id, $file, $line );
        } catch( \Exception $e )
        {
            \Zend_Debug::dump( $e->getMessage() );
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getLogId()
    {
        return $this->getData( self::LOG_ID );
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData( self::LOG_TYPE );
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->getData( self::LOG_MESSAGE );
    }

    /**
     * @inheritDoc
     */
    public function getFile()
    {
        return $this->getData( self::LOG_SOURCE_FILE );
    }

    /**
     * @inheritDoc
     */
    public function getFileLine()
    {
        return $this->getData( self::LOG_SOURCE_FILE_LINE );
    }

    /**
     * @inheritDoc
     */
    public function getCreated()
    {
        return $this->getData( self::LOG_CREATED );
    }

    /**
     * @inheritDoc
     */
    public function setLogID( $log_id )
    {
        return $this->setData( self::LOG_ID, $log_id );
    }

    /**
     * @inheritDoc
     */
    public function setType( $type )
    {
        return $this->setData( self::LOG_TYPE, $type );
    }

    /**
     * @inheritDoc
     */
    public function setMessage( $message )
    {
        return $this->setData( self::LOG_MESSAGE, $message );
    }

    /**
     * @inheritDoc
     */
    public function setFile( $file )
    {
        return $this->setData( self::LOG_SOURCE_FILE, $file );
    }

    /**
     * @inheritDoc
     */
    public function setFileLine( $line )
    {
        return $this->setData( self::LOG_SOURCE_FILE_LINE, $line );
    }

    /**
     * @inheritDoc
     */
    public function setCreated( $creation )
    {
        return $this->setData( self::LOG_CREATED, $creation );
    }
}
