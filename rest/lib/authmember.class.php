<?php
/**
 * User-level authentication interface
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/23/2013 04:03:33 PM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

/**
 * Load the DeniZEN CORE
 */
use core\lib\connectors\Ldap;
use core\models\Profile;
//use core\lib\utilities\DebugConsole;
use core\lib\utilities\Tracker;

/**
 * User-level authentication interface
 *
 * @package DeniZEN_Authentication
 *
 * @api
 */
class AuthMember implements iAuthenticate
{
    /**
	 * Request authorization states
	 */
	const REQUEST_NOT_AUTHORIZED			= FALSE;
    const REQUEST_AUTHORIZED				= TRUE;

    const AUTH_REQUIRED                 = 'true';

    /**
	 * Status conditions to indicate input parameter requirements
	 */
	const INPUT_REQUIRED					= TRUE;
	const INPUT_BLOCKED                 = FALSE;

    const KEY_HASH                      = KEY_USERHASH;
    const KEY_USERNAME                  = KEY_USERNAME;
    const KEY_SESSION_ID                = KEY_SESSION_ID;


	/**
	 * Defaults for session hash
	 */
	const SESSION_HASH_SALT				= SESSION_HASH_SALT;
    const SESSION_HASH_SEGMENT_LENGTH	= SESSION_HASH_SEGMENT_LENGTH;

	/**
	 * Number of digits to retain when generating a portion of the
	 * session identifier
	 */
	private $sessionHashSegmentLength;

	/**
	 * Cryptographic salt for session identifiers
	 */
	private $sessionHashSalt;

	/**
	 * Indicate when a given action requires user-level authentication
	 */
	public $isAuthRequired = NULL;


	/**
	 * Instantiate the user authorization interface
     *
     * @internal
	 */
    function __construct()
	{
		/**
		 * Build the hash configuration
		 */
		$this->sessionHashSegmentLength = self::SESSION_HASH_SEGMENT_LENGTH;
        $this->sessionHashSalt = self::SESSION_HASH_SALT;
	}


    /**
     * Determine if a request is authorized
     *
     * @return boolean
     *
     * @internal
     */
    function __isAuthenticated()
	{
       \lib\DebugConsole::stampFunctionCall();

        /**
		 * Determine if authorization is required
		 */
		if ( strtolower( $this->isAuthRequired ) != self::AUTH_REQUIRED )
		{
           \lib\DebugConsole::info( "User-level auth bypassed" );
           \lib\DebugConsole::end();

			return self::REQUEST_AUTHORIZED;
		}
        else
        {
           \lib\DebugConsole::info( "User-level auth required" );
        }

		/**
         * Deny access if any hash is passed via URI
         */
        if ( stristr( $_SERVER[ 'REQUEST_URI' ], 'hash=' ) )
		{
           \lib\DebugConsole::warn( "Submitting hashes via URI not permitted" );
           \lib\DebugConsole::end();

            return self::REQUEST_NOT_AUTHORIZED;
        }

        $sessionId = NULL;
        if ( !empty( $this->restler->request_data[ self::KEY_SESSION_ID ] ) )
        {
            $sessionId = $this->restler->request_data[ self::KEY_SESSION_ID ];
        }

        if ( empty( $sessionId ) )
        {
            if ( empty( $this->restler->request_data[ self::KEY_USERNAME ] ) or
                    empty( $this->restler->request_data[ self::KEY_HASH ] ) )
            {
               \lib\DebugConsole::warn(
                        "Missing parameter(s): session or user credentials" );
               \lib\DebugConsole::end();

                return self::REQUEST_NOT_AUTHORIZED;
            }
            else
            {
                /**
                 * @todo Add feature to create a session on-the-fly when
                 * proper credentials are submitted in the payload
                 */
            }
        }

		\lib\DebugConsole::log( $sessionId, "Submitted session" );

		session_id( $sessionId );
		session_start();

		\lib\DebugConsole::log( "Validating session id..." );

        /**
         * Deny access if the session is missing a valid start-timestamp
         */
        if ( empty( $_SESSION[ 'session_start_timestamp' ] ) )
        {
           \lib\DebugConsole::warn(
                    "Requested session is not valid (missing timestamp)" );
           \lib\DebugConsole::end();

            return self::REQUEST_NOT_AUTHORIZED;
        }

        /**
         * Regenerate the session hash to compare against the submitted hash
         */
		$reHash =
			$this->_generateSessionId( $_SESSION[ 'session_start_timestamp' ] );

        /**
         * Deny access if the submitted hash does not match the regenerated hash
         */
		if ( session_id() != $reHash )
		{
			\lib\DebugConsole::warn( "Session Id Hash mismatch" );
           \lib\DebugConsole::end();

            return self::REQUEST_NOT_AUTHORIZED;
		}

		/**
		 * Validate session properties
		 */
		if ( strtolower( $_SESSION[ 'api_key' ] ) !=
				strtolower( $this->restler->apiKey ) )
		{
			\lib\DebugConsole::warn( "Public API key mismatched" );
			\lib\DebugConsole::log( $_SESSION[ $key ], "session API key" );
			\lib\DebugConsole::log( $this->restler->apiKey, "request API key" );
           \lib\DebugConsole::end();

			return self::REQUEST_NOT_AUTHORIZED;
		}

		\lib\DebugConsole::log( "Session validated" );
       \lib\DebugConsole::end();

        /**
         * Notate the action in the access log
         */
        $cache = new \Cache();
        $cache->log(
                $sessionId . \Cache::SEPARATOR .
                $this->restler->url,
                'session'
        );

		return self::REQUEST_AUTHORIZED;
    }


	/**
	 * Create a hash for as a session ID
	 *
	 * @param integer $timestamp
	 *
	 * @return string
	 */
	private function _generateSessionId( $timestamp )
	{
		/**
         * Build a hash seed from the source-to-target host names
         */
        $hostToHostHash = $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REMOTE_ADDR' ];
		\lib\DebugConsole::log( $hostToHostHash, "Host-to-host string" );

        /**
         * Create a salted hash for the seed
         */
		$hostToHostHash = sha1( $hostToHostHash . self::SESSION_HASH_SALT );
		\lib\DebugConsole::log( $hostToHostHash, "Salted host hash" );

        /**
         * Truncate the hash to a smaller segment
         */
		$hostToHostHash =
			substr( $hostToHostHash, 0, self::SESSION_HASH_SEGMENT_LENGTH );
		\lib\DebugConsole::log( $hostToHostHash, "Truncated host hash" );

        /**
         * Create another segment of the session ID based on the timestamp
         */
		$timestampHash = $timestamp;
		\lib\DebugConsole::log( $timestampHash, "Timestamp" );

        /**
         * Salt and hash the timestamp segment
         */
		$timestampHash = sha1( $timestampHash . SESSION_HASH_SALT );
		\lib\DebugConsole::log( $timestampHash, "Salted timestamp hash" );

		$sessionId = $hostToHostHash . $timestampHash;
		\lib\DebugConsole::log( $sessionId, "Generated session ID" );

		return $sessionId;
	}


    /**
     * Determine if member-submitted credentials are correct
     *
     * @param   string  $username
     * @param   string  $password
     *
     * @return boolean
     */
    private function _isValidCredentials( $username, $password )
    {
		\lib\DebugConsole::stampFunctionCall();
       \lib\DebugConsole::info( "Username = $username" );
       \lib\DebugConsole::info( "Password = $password" );

        if ( empty ( $username ) ) return FALSE;

        $ldap = Ldap::connect( Ldap::DEFAULT_LDAP_URI );

        $parentDn = Profile::MEMBERS_RDN . "," . Ldap::DEFAULT_ROOT_DN;

        $credentials = array
        (
            'username'      => "uid=" . $username . "," . $parentDn,
            'hash'          => $password,
        );

        try
        {
            $isValid = Ldap::bind( $credentials, FALSE, $ldap );
        }
        catch ( core\errorhandling\LdapException $e )
        {
            $isValid = FALSE;
           \lib\DebugConsole::info( $e->getMessage() );
        }

        return $isValid;
    }


    /**
     * Validate a submitted answer to a security question
     *
     * @category POST
     *
     * @param string Log-in identity for the profile
     * @param string Unique identity for the security question
     *
     * @requestbody <var>answerhash</var> {string}
     *      Base64-encoded answer text
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>is_authorized</var> {boolean}
     *      If TRUE, the question was answered correctly
     *
     * @throws RestException
     *
     * @since Sprint 1
     */
    protected function postAuthSecurityQuestion( $userName, $questionId )
    {
		if ( empty( $questionId ) OR empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    NULL, Response::STATUS_NOT_FOUND );
        }

        $parameters = array
        (
            'answerhash' => self::INPUT_REQUIRED,
			'answer' => self::INPUT_BLOCKED,
		);

		foreach ( $parameters as $parameter => $isAcceptable )
		{
			/**
			 * Confirm that the credentials were not passed via URI query
			 */
			if ( !empty( $_GET[ $parameter ] ) )
			{
				\lib\DebugConsole::warn( $parameter, "Illegal parameter in query" );
				return new Response( Response::FAIL, NULL,
                        Response::STATUS_BAD_REQUEST );
			}

			if ( $isAcceptable AND
                    empty( $this->restler->request_data[ $parameter ] ) )
			{
				\lib\DebugConsole::log( $parameter, "Missing required parameter" );
				throw new RestException( Response::STATUS_BAD_REQUEST );
			}
		}

		/**
		 * Authenticate credentials
		 */
        $hash = base64_decode( $this->restler->request_data[ 'answerhash' ] );

        try
        {
            $isCorrect = Profile::authenticateSecurityQuestion( $userName,
                    $questionId,
                    $this->restler->request_data[ 'answerhash' ]
            );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() == \core\errorhandling\LdapException::NO_RESULTS )
            {
               \lib\DebugConsole::end();

                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }
            else
            {
               \lib\DebugConsole::end();

                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }
        catch ( \core\errorhandling\LdapException $e )
        {
            if ( $e->getCode() == \core\errorhandling\LdapException::NO_RESULTS )
            {
               \lib\DebugConsole::end();

                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }
            else
            {
               \lib\DebugConsole::end();

                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }

		\lib\DebugConsole::stampBoolean( $isCorrect );
       \lib\DebugConsole::end();

        if ( $isCorrect )
        {
            return new Response( Response::SUCCESS,
                array ( 'is_authorized' => $isCorrect )
            );
        }
        else
        {
            return new Response( Response::FAIL,
                    NULL, Response::STATUS_UNAUTHORIZED );
        }


    }


    /**
     * Start a user session
     *
     * @protected
     *
     * @category POST
	 *
     * @param string Log-in identity for the profile
     * @param mixed Refer to request body
     *
     * @requestbody <var>userhash</var> {string}
     *      Base64-encoded password for the profile
     *
	 * @return \Response Refer to response parameters
     *
     * @responseparam <var>session</var> {string}
     *      The unique identifier for the proxied user session
     */
    function postLogin( $username, $request_data )
	{
        if ( !\core\models\Profile::isExists( 'username', $username ) )
        {
            return new Response( Response::FAIL,
                    NULL, Response::STATUS_NOT_FOUND );
        }

        $request_data = array_change_key_case( $request_data );

        $loginParameters = array
        (
            'userhash' => self::INPUT_REQUIRED,
			'password' => self::INPUT_BLOCKED,
		);

		foreach ( $loginParameters as $parameter => $isAcceptable )
		{
			/**
			 * Confirm that the credentials were not passed via URI query
			 */
			if ( !empty( $_GET[ $parameter ] ) )
			{
				\lib\DebugConsole::warn( $parameter, "Illegal parameter in query" );
               \lib\DebugConsole::end();

				return new Response( Response::FAIL, NULL,
                        Response::STATUS_BAD_REQUEST );
			}
		}

        if ( empty( $request_data[ 'userhash' ] ) )
        {
           \lib\DebugConsole::warn( $parameter, "Missing required parameter 'userhash'" );
           \lib\DebugConsole::end();

            return new Response( Response::FAIL, NULL,
                    Response::STATUS_BAD_REQUEST );
        }

        /**
         * Generate the server-side session
         */
		$sessionId = $this->_generateSessionId( $_SERVER[ 'REQUEST_TIME' ] );
		session_id( $sessionId );
		session_start();

        /**
         * Retrieve meta-data for the API key
         */
        $apiModel = new \core\models\ApiKey();
        $apiKeyInfo = $apiModel->info( $this->restler->apiKey );
        $sourceId = strtolower( $this->restler->apiKey );
        $_SESSION[ 'sourceId' ] = str_replace( '-', '', $sourceId );
        $_SESSION[ 'sourceName' ] = $apiKeyInfo[ 'displayname' ];
        $_SESSION[ 'sourceApplication' ] = $apiKeyInfo[ 'application' ];

        /**
         * Retrieve the UUID for the profile
         */
        $profileId = \core\models\Profile::getUuid( $username );

        /**
         * If login is called from within an application, validate that the
         * profile is registered to use the specified application
         */
        if ( !empty( $_SESSION[ 'sourceApplication' ] ) )
        {
            if ( !\core\models\Profile::isRegisteredApplication(
                    $profileId[ 'uuid' ], $_SESSION[ 'sourceApplication' ] ) )
            {
                return new Response( Response::FAIL,
                        'Application not registered for specified profile',
                        Response::STATUS_FORBIDDEN
                        );
            }
        }

		/**
		 * Authenticate credentials
		 */
        $hash = base64_decode( $request_data[ 'userhash' ] );

//        $isAuthorized = $this->_isValidCredentials( $username, $hash );
        $isAuthorized = \core\models\Profile::authenticate( $username, $hash );

		\lib\DebugConsole::stampBoolean( $isAuthorized );

        $cache = new \Cache();

		if ( $isAuthorized )
		{
			$_SESSION[ 'session_start_timestamp' ] = $_SERVER[ 'REQUEST_TIME' ];
			$_SESSION[ 'api_key' ] = $this->restler->apiKey;
			$_SESSION[ 'user' ] = array
            (
                'username' => $username,

            );

            /**
             * Notate the action in the session log
             */
            $cache->log(
                    session_id() . \Cache::SEPARATOR .
                    $username . \Cache::SEPARATOR .
                    'success',
                    'profile'
            );

            /**
             * Submit tracker information
             */
            Tracker::log(
                    $_SESSION[ 'sourceId' ],
                    $_SESSION[ 'sourceName' ],
                    Tracker::formatUuid( $profileId[ 'uuid' ] ),
                    Tracker::PROFILE_LOGIN,
                    array ( 'application' => $_SESSION[ 'sourceApplication' ] )
            );

            $response = array (
                'session' => session_id()
            );

            if ( !empty( $_SESSION[ 'sourceApplication' ] ) )
            {
                $response[ 'profile_uuid' ] = $profileId[ 'uuid' ];
            }

			return new Response( Response::SUCCESS, $response );
		}

        /**
         * Determine whether the profile is intentionally locked
         * or invalid credentials were submitted
         */
        $errorMessage = NULL;

        if ( \core\models\Profile::isLocked( $username ) )
        {
            $status = Response::STATUS_FORBIDDEN;
            $errorMessage = 'Profile is locked';
        }
        else
        {
            $status = Response::STATUS_UNAUTHORIZED;
        }

        /**
         * Notate the action in the session log
         */
        $cache->log( session_id() . \Cache::SEPARATOR .
                $username . \Cache::SEPARATOR .
                'fail',
                'profile'
                );

        return new Response( Response::FAIL, $errorMessage, $status );
    }


    /**
     * Start a user session without authenticating credentials
     * (requires a specifically-authorized API key)
     *
     * @protected
     *
     * @category POST
	 *
     * @param string Log-in identity for the profile
     * @param mixed Refer to request body

	 * @return \Response Refer to response parameters
     *
     * @responseparam <var>session</var> {string}
     *      The unique identifier for the proxied user session
     *
     * @class AuthApiGroup(groupRequired=skeletonkeys)
     * @since Sprint 7
     */
    function postForceLogin( $username )
	{
        if ( !\core\models\Profile::isExists( 'username', $username ) )
        {
            return new Response( Response::FAIL,
                    NULL, Response::STATUS_NOT_FOUND );
        }

        $request_data = array_change_key_case( $request_data );

        $loginParameters = array
        (
            'userhash' => self::INPUT_BLOCKED,
			'password' => self::INPUT_BLOCKED,
		);

		foreach ( $loginParameters as $parameter => $isAcceptable )
		{
			/**
			 * Confirm that the credentials were not passed via URI query
			 */
			if ( !empty( $_GET[ $parameter ] ) )
			{
				\lib\DebugConsole::warn( $parameter, "Illegal parameter in query" );
               \lib\DebugConsole::end();

				return new Response( Response::FAIL, NULL,
                        Response::STATUS_BAD_REQUEST );
			}
		}

		$sessionId = $this->_generateSessionId( $_SERVER[ 'REQUEST_TIME' ] );
		session_id( $sessionId );
		session_start();

		$isAuthorized = TRUE;
        $cache = new \Cache();

		if ( $isAuthorized )
		{
			$_SESSION[ 'session_start_timestamp' ] = $_SERVER[ 'REQUEST_TIME' ];
			$_SESSION[ 'api_key' ] = $this->restler->apiKey;
			$_SESSION[ 'user' ] = array
            (
                'username' => $username,

            );

            /**
             * Notate the action in the session log
             */
            $cache->log(
                    session_id() . \Cache::SEPARATOR .
                    $username . \Cache::SEPARATOR .
                    'success',
                    'profile'
            );

			return new Response( Response::SUCCESS,
                    array ( 'session' => session_id() ) );
		}
		else
		{
            /**
             * Notate the action in the session log
             */
            $cache->log(
                    session_id() . \Cache::SEPARATOR .
                    $username . \Cache::SEPARATOR .
                    'fail',
                    'profile'
            );

            return new Response( Response::FAIL,
                    NULL, Response::STATUS_UNAUTHORIZED );
		}
    }


    /**
     * Close a user-level session
     *
     * @protected
     *
     * @category DELETE
     *
	 * @class AuthMember(isAuthRequired=TRUE)
	 *
	 * @return \Response
     */
    function deleteLogout()
	{
        session_destroy();
        return new Response ( Response::SUCCESS, array () );
    }
}