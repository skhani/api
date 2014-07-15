<?php
/**
 * DebugConsole class
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/23/2013 01:02:06 PM % %Author: Danny Knapp %
 */

namespace lib;
use \core\lib\utilities\CoreLog;

/**
 * Load vendor-supplied library
 */
require 'lib/vendor/firephp/lib/FirePHPCore/FirePHP.class.php';

/**
 * Define whether or not to automatically initialize FirePHP
 */
define( 'AUTO_INIT', TRUE );

/**
 * Index position for the name of the previous function in a trace array
 */
define( 'PREVIOUS_FUNCTION_ID', 2 );

/**
 * Debug Logging Utility
 *
 * @package Core
 * @subpackage Logs
 * @internal
 */
class DebugConsole
{
    /**
     * Logical hierarchy for classifying log messages
     */
    const LOG                           = "log";
    const INFO                          = "info";
    const WARN                          = "warn";
    const ERROR                         = "error";

    const API_ACTION                    = "API Action";
    const FUNCTION_CALL                 = "called";

    const APPLICATION_ENV_VARNAME       = APPLICATION_ENV_VARNAME;
    const DEBUG_ENVIRONMENT_LIST        = DEBUG_ENVIRONMENT_LIST;

    /**
     * FirePHP optional features
     *
     * @var array
     */
    static $options = array
    (
        'maxObjectDepth'        => 5,
        'maxArrayDepth'         => 5,
        'maxDepth'              => 10,
        'useNativeJsonEncode'   => TRUE,
        'includeLineNumbers'    => TRUE,
    );

    private static $_debugSecret = DEBUG_SECRET;


    /**
     * Create an instance of the debug console
     */
    function __construct()
    {
        self::init();
    }


    static function init( $extraInfo = array () )
    {
        /**
         * Determine if debug console is enabled
         */
        \FirePHP::getInstance( AUTO_INIT )
                ->setEnabled( self::isEnabled() );

        /**
         * If debug console is disabled, skip additional configuration
         */
        if ( !\FirePHP::getInstance()->getEnabled() ) return;

        \FirePHP::getInstance()->group( 'Initializing DebugConsole' );
        \FirePHP::getInstance()->setOptions( self::$options );
        self::setErrorHandler();
        \FirePHP::getInstance()->registerAssertionHandler();
        \FirePHP::getInstance()->registerExceptionHandler();

        if ( count( $extraInfo ) > 0 )
        {
            \FirePHP::getInstance()->info( $extraInfo );
        }

        \FirePHP::getInstance()->groupEnd();
    }

    static function setErrorHandler()
    {
        set_error_handler( array( '\lib\DebugConsole', 'errorHandler' ) );
    }


    /**
     * FirePHP's error handler
     *
     * Throws exception for each php error that will occur.
     *
     * @param integer $errorLevel
     * @param string $message
     * @param string $file
     * @param integer $line
     * @param array $context
     */
    static function errorHandler( $errorNumber, $message, $file, $line, $context )
    {
        switch ( $errorNumber )
        {
            case E_NOTICE:
            case E_USER_NOTICE:

                if ( stristr( $message, CoreLog::BANNER ) )
                {
                    if ( stristr( $message, CoreLog::LABEL_END ) )
                    {
                        \FirePHP::getInstance()->groupEnd();
                    }
                    else
                    {
                        $strippedMessage =
                                str_replace( CoreLog::BANNER, '', $message );
                        $strippedMessage =
                                str_replace( CoreLog::LABEL_START,
                                        '',
                                        $strippedMessage
                                        );

                        \FirePHP::getInstance()->group( $strippedMessage,
                                array ( 'Collapsed' => TRUE, 'Color' => '#0000FF' )
                                );
                    }

                }
                else
                {
                    \FirePHP::getInstance()->info( $message );
                }
                break;

            case E_WARNING:
            case E_USER_WARNING:
                \FirePHP::getInstance()->warn( $message );
                break;

            case E_ERROR:
            case E_USER_ERROR:
                \FirePHP::getInstance()->error( $message );
                break;

            default:
                \FirePHP::getInstance()->info( $message, $errorNumber );
        }
    }


    /**
     * Determine whether to display console output
     *
     * @return boolean
     */
    static function isEnabled()
    {
        /**
         * If the current application instance has already been enabled,
         * short-cut the procedure
         */
        if ( defined( 'FIREPHP_ENABLED' ) )
        {
            return FIREPHP_ENABLED;
        }

        /**
         * Enable console output for if specified in the host environment
         */
        if ( function_exists( 'apache_getenv' ) )
        {
            $values = explode( ', ', self::DEBUG_ENVIRONMENT_LIST );
            $environment = apache_getenv( self::APPLICATION_ENV_VARNAME );
            if ( in_array( $environment, $values ) )
            {
                return self::_setEnabled( TRUE );
            }
        }

        if ( !empty( $_REQUEST[ 'debug' ] ) and !empty( $_REQUEST[ 'nonce' ] ) )
        {
            $debugSecret = base64_decode( self::$_debugSecret );
            $debugHash = sha1( $_REQUEST[ 'nonce' ] . $debugSecret );

            if ( $_REQUEST[ 'debug' ] == $debugHash )
            {
                return self::_setEnabled( TRUE );
            }
        }



        return self::_setEnabled( FALSE );
    }


    /**
     * Persist the status of the debugging console for the application session
     *
     * @param   boolean     $isEnabled
     *
     * @return  boolean
     */
    private static function _setEnabled( $isEnabled )
    {
        define( 'FIREPHP_ENABLED', $isEnabled );
        return $isEnabled;
    }


    /**
     * Output information regarding a function
     *
     * @param string $content
     */
    static function stampFunctionCall( $content = self::FUNCTION_CALL )
    {
        if ( !self::isEnabled() ) return;

        $label = self::getCallingFunctionName();
        \FirePHP::getInstance( AUTO_INIT )->group( $label );
    }


    /**
     * Output information regarding an API action
     *
     * @param \Restler $restler
     */
    static function stampAction( &$restler )
    {
        if ( !self::isEnabled() ) return;

        $label = self::getCallingFunctionName();
        \FirePHP::getInstance( AUTO_INIT )->group( $label );
        \FirePHP::getInstance( AUTO_INIT )->
                info( $restler->request_format, "Output format" );

        \FirePHP::getInstance( AUTO_INIT )->
                info( $restler->request_data, "Request parameters" );
    }

    /**
     * Output information regarding an exception
     *
     * @param \Restler $restler
     */
    static function stampException( &$exception )
    {
        if ( !self::isEnabled() ) return;

        $label = self::getCallingFunctionName();

        $message = $exception->getMessage();
        $code = $exception->getCode();

        \FirePHP::getInstance( AUTO_INIT )->warn( "Throwing " .
                $code . " " . $message
                );
    }


    /**
     * Ouput information regarding a boolean variable
     *
     * @param   boolean     $value
     * @param   string      $description
     * @param   string      $level
     *
     * @return  void
     */
    static function stampBoolean( $value,
            $description = NULL, $level = self::INFO )
    {
        if ( !self::isEnabled() ) return;

        $level = constant( "self::" . strtoupper( $level ) );

        if ( empty( $level ) ) return;

        $label = self::getCallingFunctionName();

        $description = ( !empty( $description ) ? $description : '= ' );

        $content = $description . ( $value ? 'TRUE' : 'FALSE' );

        \FirePHP::getInstance( AUTO_INIT )->$level( $content, $label );
    }


    /**
     * Denote the closing of a logical message group
     */
    static function end()
    {
        if ( !self::isEnabled() ) return;
        $label = self::getCallingFunctionName();

        \FirePHP::getInstance( AUTO_INIT )->groupEnd();
    }


    /**
     * Output text to debug console (standard priority)
     *
     * @param string $message
     * @param string $label
     */
    static function log( $message, $label = NULL )
    {
        if ( !self::isEnabled() ) return;

        if ( empty( $label ) ) $label = self::getCallingFunctionName();
        \FirePHP::getInstance( AUTO_INIT )->info( $message, $label );
    }


    /**
     * Output to debug console with an 'info' priority (for verbose logging)
     *
     * @param string $message
     * @param string $label
     */
    static function info( $message, $label = NULL )
    {
        if ( !self::isEnabled() ) return;

        if ( empty( $label ) ) $label = self::getCallingFunctionName();
        \FirePHP::getInstance( AUTO_INIT )->info( $message, $label );
    }


    /**
     * Output to debug console with a 'warning' priority
     *
     * @param string $message
     * @param string $label
     */
    static function warn( $message, $label = NULL )
    {
        if ( !self::isEnabled() ) return;

        if ( empty( $label ) ) $label = self::getCallingFunctionName();
        \FirePHP::getInstance( AUTO_INIT )->warn( $message, $label );
    }


    /**
     * Output a fatal error to the debug console
     *
     * @param string $message
     * @param string $label
     */
    static function error( $message, $label = NULL )
    {
        if ( !self::isEnabled() ) return;

        if ( empty( $label ) ) $label = self::getCallingFunctionName();
        \FirePHP::getInstance( AUTO_INIT )->error( $message, $label );
    }


    /**
     * Retrieve the name of a calling function
     *
     * @return string
     */
    static function getCallingFunctionName()
    {
        $trace = debug_backtrace();

        $caller = '';

        if ( !empty( $trace[ PREVIOUS_FUNCTION_ID ][ 'class' ] ) )
        {
            $caller .= $trace[ PREVIOUS_FUNCTION_ID ][ 'class' ] . '->';
        }

        if ( !empty( $trace[ PREVIOUS_FUNCTION_ID ][ 'function' ] ) )
        {
            $caller .= $trace[ PREVIOUS_FUNCTION_ID ][ 'function' ];
        }

        return $caller;
    }
}
