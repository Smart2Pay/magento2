<?php
namespace Smart2Pay\GlobalPay\Model\ResourceModel;

class Logger extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string|null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 's2p_gp_logs', 'log_id' );
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
        try
        {
            if( !($conn = $this->getConnection()) )
                return false;

            if( empty( $file ) or empty( $line ) )
            {
                $backtrace = debug_backtrace();
                $file = $backtrace[0]['file'];
                $line = $backtrace[0]['line'];
            }

            $insert_arr = array();
            $insert_arr['log_message'] = $message;
            $insert_arr['log_type'] = $type;
            $insert_arr['transaction_id'] = $transaction_id;
            $insert_arr['log_source_file'] = $file;
            $insert_arr['log_source_file_line'] = $line;
            $insert_arr['log_created'] = date( 'Y-m-d H:i:s' );

            $conn->insert( 's2p_gp_logs', $insert_arr );

        } catch( \Zend_Db_Adapter_Exception $e )
        {
            \Zend_Debug::dump( $e->getMessage() );
            return false;
        }

        return true;
    }
}
