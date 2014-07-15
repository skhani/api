<?php
/**
 * REST Response Class
 * 
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/23/2013 04:03:33 PM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

//use core\lib\utilities\DebugConsole;

/**
 * Structured REST response
 * 
 * @package DeniZEN
 */
class Response 
{
    const SUCCESS                       = TRUE;
    const FAIL                          = FALSE;
    
    const STATUS_CONTINUE               = 100;
    const STATUS_SWITCH_PROTOCOL        = 101;
    const STATUS_PROCESSING             = 102;
    
    const STATUS_OK                     = 200;
    const STATUS_CREATED                = 201;
    const STATUS_ACCEPTED               = 202;
    const STATUS_NON_AUTHORITY_INFO     = 203;
    const STATUS_NO_CONTENT             = 204;
    const STATUS_RESET                  = 205;
    const STATUS_PARTIAL                = 206;
    const STATUS_MULTI_STATUS           = 207;
    
    const STATUS_MULTI_CHOICE           = 300;
    const STATUS_MOVED                  = 301;
    const STATUS_FOUND                  = 302;
    const STATUS_SEE_OTHER              = 303;
    const STATUS_NOT_MODIFIED           = 304;
    const STATUS_USE_PROXY              = 305;
    const STATUS_TEMP_REDIRECT          = 307;
    const STATUS_PERM_REDIRECT          = 308;
    
    const STATUS_BAD_REQUEST            = 400;
    const STATUS_UNAUTHORIZED           = 401;
    const STATUS_PAYMENT_REQUIRED       = 402;
    const STATUS_FORBIDDEN              = 403;
    const STATUS_NOT_FOUND              = 404;
    const STATUS_METHOD_NOT_ALLOWED     = 405;
    const STATUS_NOT_ACCEPTABLE         = 406;
    const STATUS_PROXY_AUTH             = 407;    
    const STATUS_REQUEST_TIMEOUT        = 408;
    const STATUS_CONFLICT               = 409;
    const STATUS_GONE                   = 410;
    const STATUS_LENGTH_REQUIRED        = 411;
    const STATUS_PRECONDITION_FAILED    = 412;
    const STATUS_REQUEST_TOO_LARGE      = 413;
    const STATUS_REQUEST_URI_TOO_LONG   = 414;
    const STATUS_UNSUPPORTED_TYPE       = 415;
    const STATUS_RANGE_NOT_SATISFIABLE  = 416;
    const STATUS_EXPECTATION_FAILED     = 417;
    const STATUS_TOO_MANY_REQUESTS      = 429;
    const STATUS_HEADER_TOO_LARGE       = 431;
    
    const STATUS_ERROR                  = 500;
    
    const DELIMITER         = '||';
    
    const KEY_MESSAGE       = 'message';
    
    const ENUM_CODE         = 0;
    const ENUM_MESSAGE      = 1;
    
    const DATATYPE_CODE             = 'status_code';
    const DATATYPE_ARRAY            = 'array';
    const DATATYPE_STRING           = 'string';
    const DATATYPE_INVALID_ARG      = 'InvalidArgumentException';
    const DATATYPE_EXCEPTION        = 'Exception';
	const DATATYPE_EXCEPTION_LDAP   = 'LdapException';
    
    public $is_success;
    
    public $data;
    
    /**
     *  Format output restful response with optional pagination paramerter if needed.
     * @param boolean $isSuccess    Pass/fail status for the request
     * @param array   $data         Payload to return to user-agent
     * @param integer $statusCode   HTTP status code
     * @param type $paginate
     */
    function __construct( $isSuccess, $data = NULL, $statusCode = self::STATUS_OK, $paginate = NULL ) 
    {        
        $this->is_success = $isSuccess;

        if ( $this->is_success == self::SUCCESS )
        {
           \lib\DebugConsole::info( 'Success', 'Response' );
            
            $this->data = $this->_formatOutput( $data );
            
            if($paginate !== null){
                $this->pagination = $this->_formatOutput($paginate);
            }
            
            header("HTTP/1.1 $statusCode");
        }
        else 
        {
           \lib\DebugConsole::warn( 'Fail', 'Response' );
            
            $errorCode = self::STATUS_ERROR;
            if ( $statusCode != self::STATUS_OK )
            {
                $errorCode = $statusCode;
            }
            $this->_handleError( $errorCode, $data );
        }
    }
    
    
    /**
     * Format an error response
     *
     * @param integer   $statusCode     HTTP status (error code)
     * @param mixed     $data           Payload for the error
     * @param string    $errorType      Specified data-type for the error
     */
    private function _handleError( $statusCode, $data = NULL, $errorType = NULL )
    {
        /**
         * If not specified, attempt to determine data-type of passed error
         */
        if ( is_null( $errorType ) )
        {
            if ( is_object( $data ) )
            {
                $errorType = get_class( $data );                
            }
            elseif ( is_array( $data ) )
            {
                $errorType = self::DATATYPE_ARRAY;
            }
            elseif (is_string( $data ) )
            {
                $errorType = self::DATATYPE_STRING;
            }
            elseif ( is_int( $data ) )
            {
                $errorType = self::DATATYPE_CODE;
            }
        }
        
        /**
         * Parse error message from payload
         */
        switch ( $errorType )
        {
            case self::DATATYPE_ARRAY :
                $message = NULL;
                
                if ( !empty ( $data[ self::KEY_MESSAGE ] ) )
                {
                    $message = $data[ self::KEY_MESSAGE ];
                }
                break;
            
			case self::DATATYPE_EXCEPTION_LDAP:
                if ( $data->getCode() ==  
                        \core\errorhandling\LdapException::NO_RESULTS)
                {
                    $message = NULL;
                    $statusCode = self::STATUS_NOT_FOUND;
                }
                else
                {
                    $message = $data->getMessage();
                    $code = $data->getCode();
                    if ( $code > 500 AND $code != $statusCode )
                    {
                        $statusCode = $code;
                    }
                    else
                    {
                        $statusCode = self::STATUS_ERROR;
                    }
                    break;
                }
                break;
            
            case self::DATATYPE_EXCEPTION :
                $message = $data->getMessage();
                $code = $data->getCode();
                if ( $code > 500 AND $code != $statusCode )
                {
                    $statusCode = $code;
                }
				else
				{
					$statusCode = self::STATUS_ERROR;
				}
                break;
            
            case self::DATATYPE_INVALID_ARG :
                $message = $data->getMessage();
                $statusCode = $data->getCode();
                if ( $statusCode === 0 ) $statusCode = self::STATUS_BAD_REQUEST;
                break;
            
            case self::DATATYPE_STRING :
                if (stristr( $data, self::DELIMITER ) )
                {
                    $dataSegments = explode( self::DELIMITER, $data );
                    $statusCode = intval( $dataSegments[ self::ENUM_CODE ] );
                    $message = $dataSegments[ self::ENUM_MESSAGE ];
                }
                else
                {
                    $message = $data;
                }
                break;
                
            case self::DATATYPE_CODE :
                $message = NULL;
                $statusCode = $data;
                break;
            
            default :
                $message = NULL;
        }
        
        throw new RestException( $statusCode, $message );
    }
    
    
    /**
     * Format payload for respone to user-agent
     * 
     * @param   mixed $data
     * @return  array
     * 
     * @internal
     */
    private function _formatOutput( $data ) 
    {
        /**
         * Determine and handle data-type of payload
         */
        if (is_object( $data ) )
        {
            $data = (array)$data;
        }
        
        if ( is_array( $data) )
        {
            $data = array_change_key_case( $data );
        }
        elseif ( is_string( $data ) )
        {
            $data = array ( self::KEY_MESSAGE => $data );
        }
        
        return $data;        
    }
}
