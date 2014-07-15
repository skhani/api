<?php
/**
 * API-level authentication interface
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date% %Author%
 */

//use core\lib\utilities\DebugConsole;
use \core\models\ApiKey;

/**
 * API-level Authentication
 *
 * @tutorial
 * {@link https://creativechannel.github.io/api/signature-based-authentication.html
 * HOWTO authenticate API requests}
 *
 * @package DeniZEN_Authentication
 * @api
 */
class AuthApi implements iAuthenticate
{
    /**
	 * API key classes
	 */
	const API_KEY_CLIENT	= 0;
	const API_KEY_PARTNER	= 1;
    const API_KEY_INTERNAL	= 2;

    /**
	 * Status conditions for authorization
	 */
	const NOT_AUTHORIZED    	= FALSE;
    const AUTHORIZED        	= TRUE;

	/**
	 * Permissible time discrepancy between submitted timestamp and
	 * current server time (in seconds)
	 */
	const MAX_SECONDS_TIMESTAMP_SKEW = MAX_SECONDS_TIMESTAMP_SKEW;
	const MAX_SECONDS_GATEWAY_DEBOUNCE = MAX_SECONDS_GATEWAY_DEBOUNCE;

	/**
	 * Lifetime for caching unique request nonce ids
	 */
	const NONCE_LIFETIME = NONCE_LIFETIME;

	/**
	 * Floor and ceiling for nonce length
	 */
	const NONCE_LENGTH_MINIMUM = NONCE_LENGTH_MINIMUM;
	const NONCE_LENGTH_MAXIMUM = NONCE_LENGTH_MAXIMUM;

	/**
	 * Identifier for cached nonces list
	 */
	const NONCE_KEY_PREFIX = NONCE_KEY_PREFIX;

    /**
     * Identifier for submitted nonce
     */
    const REQUEST_MEMBER_KEY = NONCE_REQUEST_MEMBER_KEY;

    /**
	 * Required input variables
	 * @var array
	 */
	protected $requiredParameters = array (
		'stamp', 'nonce', 'signature'
	);

	/**
	 * Unique identity for the nonce cache associated with the agent
	 *
	 * @var string
	 */
	protected $nonceKey;

	/**
	 * Public identity key string from the agent's API key pair
	 *
	 * @var string
	 */
	protected $apiKey;

	/**
	 * Current list of submitted nonces associated with the agent
	 *
	 * @var array
	 */
	protected $nonceList;


	/**
	 * Build an instance and load any existing session information
     *
     * @internal
	 */
    function __construct()
	{
		/**
         * If the request is not submitted from within an existing session,
         * the API key must be supplied
         */
        if ( empty( $this->restler->request_data[ 'session' ] ) )
        {
            array_push( $this->requiredParameters, 'api_key' );
        }

        if ( !empty( $_SESSION[ 'api_key' ] ) )
		{
			$this->apiKey = $_SESSION[ 'api_key' ];
			$this->nonceKey = self::NONCE_KEY_PREFIX . $this->apiKey;
		}
	}


    /**
     * Determine if a given request/session is authorized
     *
     * @return boolean
     *
     * @internal
     */
    function __isAuthenticated()
	{
       \lib\DebugConsole::stampFunctionCall();

       \lib\DebugConsole::log( $_REQUEST );

        /**
         * Determine if required parameters were supplied
         */
       \lib\DebugConsole::log( $this->requiredParameters, "Required parameters" );

        foreach ( $this->requiredParameters as $parameter )
		{
            if (empty( $_REQUEST[ $parameter ] ) )
			{
               \lib\DebugConsole::log( $parameter, "Missing parameter" );
               \lib\DebugConsole::end();

                return self::NOT_AUTHORIZED;
            }
        }

		/**
		 * Confirm request timestamp is in valid time-span
		 */
		if ( !$this->_isValidRequestTime() )
		{
			\lib\DebugConsole::warn( "Request time skewed" );
           \lib\DebugConsole::end();

			return self::NOT_AUTHORIZED;
		}

		/**
		 * Confirm that the request is unique
		 */
		\lib\DebugConsole::log( "Checking nonce..." );

        if ( !empty( $this->nonceKey ) )
        {
           \lib\DebugConsole::log( $this->nonceKey, "Existing nonceKey" );
        }
		else
		{
			$this->nonceKey = self::NONCE_KEY_PREFIX .
								$this->restler->request_data[ 'api_key' ];
			$_SESSION[ 'nonce_key' ] = $this->nonceKey;

           \lib\DebugConsole::log( $this->nonceKey, "New nonceKey" );
		}

        if ( !$this->_validateNonce() )
        {
           \lib\DebugConsole::warn( 'Rejecting request: existing nonce' );
           \lib\DebugConsole::end();

            return self::NOT_AUTHORIZED;
        }

        /**
         * Validate the request signature hash
         */
        try
        {
            $isValidSignature = ApiKey::validateSignature(
                    $this->restler->request_data['api_key'],
                    $this->restler->request_data['signature'],
                    $this-> _buildSignedRequest()
            );
        }
        catch (\core\errorhandling\LdapException $e)
        {
           \lib\DebugConsole::error( $e->getMessage() );
            return self::NOT_AUTHORIZED;
        }

        if ( $isValidSignature )
        {
			$isAuthenticated = self::AUTHORIZED;

			\lib\DebugConsole::log( "Signature verified" );
			$this->restler->apiKey = $this->restler->request_data['api_key'];
		}
		else
		{
			$isAuthenticated = self::NOT_AUTHORIZED;

           \lib\DebugConsole::log( "Invalid signature" );
			\lib\DebugConsole::log( $this->restler->url,"Action" );

			\lib\DebugConsole::log( $this->restler->request_data[ 'signature' ],
							"Submitted signature" );
		}

       \lib\DebugConsole::end();

        /**
         * Notate the action in the access log
         */
        $cache = new \Cache();
        $cache->log(
                $this->restler->apiKey . \Cache::SEPARATOR .
                $this->restler->url
        );

        $apiModel = new \core\models\ApiKey();
        $apiKeyInfo = $apiModel->info( $this->restler->apiKey );

        $sourceId = strtolower( $this->restler->apiKey );
        $_SESSION[ 'sourceId' ] = str_replace( '-', '', $sourceId );
        $_SESSION[ 'sourceName' ] = $apiKeyInfo[ 'displayname' ];

        $_SESSION[ 'sourceApplication' ] = '';
        if (!empty( $apiKeyInfo[ 'application' ] ) )
        {
            $_SESSION[ 'sourceApplication' ] = $apiKeyInfo[ 'application' ];
        }

        return $isAuthenticated;
    }


    /**
     * Determine if the originating request occurred within an acceptable
     * timespan (to assist in thwarting man-in-the-middle attacks)
     *
     * @return boolean
     */
    private function _isValidRequestTime()
    {
		\lib\DebugConsole::stampFunctionCall();

        $requestTime = $this->restler->request_data[ REQUEST_KEY_TIMESTAMP ];
		$timeSkew = abs( $requestTime - time() );

       \lib\DebugConsole::log( $timeSkew, "Request vs server time skew" );

		$isValid =
            ( $timeSkew > self::MAX_SECONDS_TIMESTAMP_SKEW ) ? FALSE : TRUE;

       \lib\DebugConsole::stampBoolean( $isValid );
       \lib\DebugConsole::end();

        return $isValid;
    }


	/**
	 * Aggregate the request parameters that comprise the clear-text portion
     * of a signed request
	 *
     *  @return string  Concatenated clear-text request string
	 */
	private function _buildSignedRequest()
	{
		$signedValue = strtoupper( $this->restler->request_method );
		$signedValue .= $this->restler->request_data[ 'stamp' ];
		$signedValue .= $this->restler->request_data[ 'nonce' ];
		$signedValue .= strtolower( $this->restler->url );

		return $signedValue;
	}


	/**
	 * Confirm that a request's nonce (unique identifier) has not been
	 * submitted previously
	 *
	 * @return boolean
	 */
	private function _validateNonce()
	{
       \lib\DebugConsole::stampFunctionCall();

		$nonce = $this->restler->request_data[ 'nonce' ];

        /**
         * Validate nonce meets required minimum length
         */
		$nonceLength = strlen( $nonce );
		if ( $nonceLength < self::NONCE_LENGTH_MINIMUM )
		{
			\lib\DebugConsole::log( "Nonce < " .
				self::NONCE_LENGTH_MINIMUM . " characters" );

           \lib\DebugConsole::end();

			return FALSE;
		}

        /**
         * Validate nonce does not exceed maximum length
         */
		if ( $nonceLength > self::NONCE_LENGTH_MAXIMUM )
		{
			\lib\DebugConsole::log( "Nonce > " .
				self::NONCE_LENGTH_MAXIMUM . " characters");
           \lib\DebugConsole::end();

			return FALSE;
		}

        if ( $this->_isExistsNonce() )
		{
			\lib\DebugConsole::end();

            return FALSE;
		}
		else
		{
			$this->_addNonceToCache();

           \lib\DebugConsole::end();

			return TRUE;
		}
	}


	/**
	 * Determine if a nonce has already been submitted
     *
	 * @return boolean
	 */
	private function _isExistsNonce()
	{
		\lib\DebugConsole::stampFunctionCall();
		\lib\DebugConsole::log( "Checking $this->nonceKey for "  .
                $this->restler->request_data[ self::REQUEST_MEMBER_KEY ] );

        $cache = new \Cache();

		if ( $cache->isExistsZMember( $this->nonceKey,
                $this->restler->request_data[ self::REQUEST_MEMBER_KEY ] )
                )
		{
			\lib\DebugConsole::warn( "Nonce found in cache" );
            $this->_refreshNonceListExpiration();

           \lib\DebugConsole::end();

            return TRUE;
		}
		else
		{
           \lib\DebugConsole::info( "Nonce not cached" );
           \lib\DebugConsole::end();

			return FALSE;
		}
	}


    /**
     * Reset the expiration time for a given nonce list
     *
     * @param integer   $expirationSeconds
     *
     * @return boolean
     */
    private function _refreshNonceListExpiration(
            $expirationSeconds = self::NONCE_LIFETIME )
    {
		\lib\DebugConsole::stampFunctionCall();

        $cache = new \Cache;
        $isSuccess = $cache->setKeyExpiration( $this->nonceKey,
                self::NONCE_LIFETIME );

       \lib\DebugConsole::end();

        return $isSuccess;
    }


    /**
     * Remove old nonces from an agent's nonce list
     *
     * @todo Create function
     *
     * @param string $nonceKey
     * @param integer $ageSeconds
     */
    private function _pruneNonceList( $nonceKey, $ageSeconds ) {}


    /**
     * Purge old nonces from all nonce lists
     *
     * @todo Create function
     *
     * @param integer $ageSeconds
     */
    private function _pruneAllNonceLists( $ageSeconds ) {}


	/**
	 * Add the nonce from the current request to the list of known nonces
	 * @return boolean
	 */
	private function _addNonceToCache()
	{
		\lib\DebugConsole::stampFunctionCall();
        $nonce = $this->restler->request_data[ self::REQUEST_MEMBER_KEY ];

        $cache = new \Cache;
        $isSuccess = $cache->addZMember( $this->nonceKey,
                $nonce, time(), self::NONCE_LIFETIME
                );

       \lib\DebugConsole::end();

        return $isSuccess;
	}


	/**
	 * Get the current server time in
     * {@link http://en.wikipedia.org/wiki/Unix_time POSIX timestamp} format
     *
     * @category GET
     *
	 * @return \Response Refer to response parameters
     *
     * @responseparam <var>timestamp</var> {integer} Current UTC timestamp
	 */
	function currentTimestamp()
	{
		\lib\DebugConsole::stampAction( $this->restler );
       \lib\DebugConsole::end();

        return new \Response( \Response::SUCCESS, array ( 'timestamp' => time() ) );
	}
}