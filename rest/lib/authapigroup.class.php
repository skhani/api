<?php
/**
 * API-level group authentication interface
 * 
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/23/2013 04:03:33 PM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

//use core\lib\utilities\DebugConsole;
use \core\models\ApiKey;

/**
 * API-level Group Authentication
 * 
 * @package DeniZEN_Authentication
 * @internal
 */
class AuthApiGroup implements iAuthenticate
{    
    /**
	 * Status conditions for authorization
	 */
	const NOT_AUTHORIZED    	= FALSE;
    const AUTHORIZED        	= TRUE;
    
    /**
     * Prefix for indicating that log messages should be associated
     * to a specific authorization group
     */
    const LOG_PREFIX           = 'group_';

    /**
	 * Public identity key string from the agent's API key pair
	 *
	 * @var string
	 */
	protected $apiKey;

	/**
	 * Indicate when a given action requires API group authentication
	 */
	public $groupRequired = NULL;
        
	/**
	 * Build an instance and load any existing session information
	 */
    function __construct()
	{
		if ( !empty( $_SESSION[ 'api_key' ] ) )
		{
			$this->apiKey = $_SESSION[ 'api_key' ];
		}            
	}


    /**
    * Determine if a given request/session is authorized
    *
    * @return boolean
    */
    function __isAuthenticated()
	{
       \lib\DebugConsole::stampFunctionCall();
        
        /**
		 * Determine if authorization is required
		 */
		if ( empty( $this->groupRequired ) )
		{
           \lib\DebugConsole::info( "API group auth bypassed" );
           \lib\DebugConsole::end();

			return self::AUTHORIZED;
		}
        else
        {
           \lib\DebugConsole::info( "API group auth required" );
        }
        
        $isAuthenticated = FALSE;

        $model = new \core\models\ApiKey();
        $isAuthenticated = $model->isMemberOfGroup(
                $this->restler->request_data[ 'api_key' ],
                $this->groupRequired
                );
        
       \lib\DebugConsole::stampBoolean( $isAuthenticated );        
       \lib\DebugConsole::end();
        
        /**
         * Notate the action in the access log
         */
        $cache = new \Cache();
        $cache->log(
                $this->restler->apiKey . \Cache::SEPARATOR . 
                $this->restler->url . \Cache::SEPARATOR . 
                $isAuthenticated,
                self::LOG_PREFIX . $this->groupRequired
        );
        
        return $isAuthenticated;
    }
}