<?php
/**
 * Agent for CCS MobJob Management Application
 *
 * Do not distribute -- for use only by Creative Channel Services Development
 * Department staff
 *
 * @package DeniZEN_Agents
 * @category Mobjob-Management-Gateway
 *
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/30/2013 10:49:11 AM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 * @author Danny Knapp <dknapp@creativechannel.com>
 *
 * @internal
 * @ignore
 */

/**
 * Line feed character(s) for output
 */
define( 'LF', "\n" );

/**
 * Number of characters for nonce hash
 */
define( 'NONCE_PREFIX_LENGTH',	20 );

/**
 * Identifier for gateway
 */
define( 'CCS_GATEWAY_ID',               'm0Bj0B'                            );

/**
 * Query parameter for overriding the agent's HTTP request method (useful when
 * an agent doesn't support a particular method)
 */
define( 'METHOD_PARAMETER',             'override_method'                   );

/**
 * Prefix for query parameters to be converted to POST data fields when the
 * override method is POST/PUT
 *
 * For example: the query parameter 'postdata_username' would be sent as a POST
 * data field called 'username'
 */
define( 'POST_DATA_FIELD_PREFIX',       'postdata'                          );

/**
 * Query parameter for building the API query without executing the request
 * (useful for debugging this script and downstream requests)
 */
define( 'NO_EXEC_PARAMETER',            'no_exec'                           );

/**
 * Redis key prefix for access log
 */
define( 'LOG_KEY_PREFIX',               'DENIZEN:ACCESS:REST:GW:MOBJOB:'    );

/**
 * Redis key segment for invalid requests (missing an agent key)
 */
define( 'LOG_KEY_BAD_REQUESTS',        'INVALID'                            );

/**
 * Field delimiter for access log entries
 */
define( 'LOG_DELIMITER',                '|'                                 );

/**
 *  Base service location
 */
define( 'BASE_URI',			'https://api.creativechannel.com/rest/v2'       );

/**
 * Definition for sorting user functions
 */
define( 'CATEGORIZED',                  TRUE                                );

/**
 * MobJob Campaign Manager API key
 */
define( 'CAMPAIGN_MANAGER_PUBLIC_KEY', 'NTIzYjI3YzQyZjJhYTAuNTQ0MDM1MTY='   );
define( 'CAMPAIGN_MANAGER_PRIVATE_KEY',
            'YjgyNjlkOGQ1NmE4NjE1NjBkOTAxZDAzZWEwNjIwOTQ3YmU2NWJmOQ=='      );

/**
 * MobJob Jobster client API key
 */
define( 'JOBSTER_PUBLIC_KEY', 'NTI0MTIxMDU3MTNhMzQuNjQ3OTU3MTY='            );
define( 'JOBSTER_PRIVATE_KEY',
            'MTM5MTc0NmFkOGZiODJhYjc0ZjIzZTIwNzhlNGE1YTM4ODg4MDVlNg=='      );

/**
 * Legacy API key
 */
define( 'PARSE_PUBLIC_KEY', 'NTIzYjI3YzQyZjJhYTAuNTQ0MDM1MTY='              );
define( 'PARSE_PRIVATE_KEY',
            'YjgyNjlkOGQ1NmE4NjE1NjBkOTAxZDAzZWEwNjIwOTQ3YmU2NWJmOQ=='      );

/**
 * Network log configuration
 */
define( 'LOG_PROTOCOL',                 'tcp'                               );
define( 'LOG_HOST',                     'redis-prod.vpc.ccs-internal.net'   );
define( 'LOG_PORT',                     6379                                );
define( 'LOG_DB',                       5                                   );
define( 'LOG_CONNECTOR',
            'Predis\Connection\PhpiredisConnection'                         );

define( 'STATUS_INVALID_AGENT',         400                                 );
define( 'STATUS_CURL_ERROR',            500                                 );

/**
 * Set up the network-based log via Redis
 */
require 'core/lib/vendor/predis/autoload.php';
\Predis\Autoloader::register();

$logOptions = array
(
    'scheme'    => LOG_PROTOCOL,
    'host'      => LOG_HOST,
    'port'      => LOG_PORT,
    'database'  => LOG_DB,
);

/**
 * Use the hiredis wrapper for performance boost
 */
$logConnections = array
(
    'connections' => array
    (
        'tcp'   => LOG_CONNECTOR,
        'unix'  => LOG_CONNECTOR,
    ),
);

/**
 * Instantiate the Redis client
 */
$log = new \Predis\Client( $logOptions, $logConnections );

/**
 * Log format
 * [timestamp]:[requestIp]:[method]:[status]:[statusMessage]:[action]
 */

/**
 * Get the timestamp for the originating request
 */
$stamp = $_SERVER[ 'REQUEST_TIME' ];

/**
 * Log the request time
 */
$logEntry = $stamp;

/**
 * Log the requesting agent's IP address
 */
$httpHeaders = apache_request_headers();
$ip = ( !empty( $httpHeaders[ 'X-Forwarded-For' ] ) ) ?
        $httpHeaders[ 'X-Forwarded-For' ] : $_SERVER[ 'REMOTE_ADDR' ];
$logEntry .= LOG_DELIMITER . $ip;

/**
 * Determine which HTTP method to use for the requested operation
 * (default: assume method used by originating agent)
 */
$method = $_SERVER[ 'REQUEST_METHOD' ];

/**
 * Override the method if specified in the request
 */
$isMethodOverride = FALSE;
if ( !empty( $_REQUEST[ METHOD_PARAMETER ] ) )
{
	$isMethodOverride = TRUE;
    $method = strtoupper( $_REQUEST[ METHOD_PARAMETER ] );
}

$logEntry .= LOG_DELIMITER . $method;

/**
 * Validate agent identifier
 */
if ( empty( $_REQUEST[ 'agentId' ] ) )
{
    $logEntry .= LOG_DELIMITER . STATUS_INVALID_AGENT;
    $log->rpush( LOG_KEY_PREFIX . LOG_KEY_BAD_REQUESTS, $logEntry );
    header('HTTP/1.1 400 Bad request: Missing agent');
    exit();
}
elseif ( !defined( strtoupper( $_REQUEST[ 'agentId' ] ) . '_PUBLIC_KEY' ) )
{
    $logEntry .= LOG_DELIMITER . STATUS_INVALID_AGENT .
                    LOG_DELIMITER . $_REQUEST[ 'agentId' ];
    $log->rpush( LOG_KEY_PREFIX . LOG_KEY_BAD_REQUESTS, $logEntry );
    header('HTTP/1.1 400 Bad request: Unrecoginized agent');
    exit();
}
else
{
    $agent = strtoupper( $_REQUEST[ 'agentId' ] );
}

/**
 * Set the API key per the requesting agent
 */
$publicKey = constant( $agent . '_PUBLIC_KEY' );
$privateKey = constant( $agent . '_PRIVATE_KEY' );

$publicKey = base64_decode( $publicKey );
$privateKey = base64_decode( $privateKey );

/**
 * Proxied headers
 */
$headersMap = array
(
	'HTTP_X_FIREPHP_VERSION'	=> 'X-FirePHP-Version',
	'HTTP_X_INSIGHT'			=> 'x-insight',
	'HTTP_USER_AGENT'			=> 'User-Agent',
	'HTTP_ACCEPT'				=> 'Accept',
	'HTTP_ACCEPT_LANGUAGE'		=> 'Accept-Language',
	'HTTP_ACCEPT_ENCODING'		=> 'Accept-Encoding',
    'CONTENT_TYPE'              => 'Content-Type',
);

/**
 * Determine which input headers should be passed
 */
$requestHeaders = array_intersect_key( $_SERVER, $headersMap );

$passThruHeaders = array ();

foreach ( $headersMap as $key => $name )
{
	if ( !empty( $requestHeaders[ $key ] ) )
    {
        $passThruHeaders[] = "$name: " . $requestHeaders[ $key ];
    }
}

/**
 * Add a flag to header to indicate request was proxied by this gateway
 */
$headersPassThru[] = "X-CCS-Gateway-Indentifier: " . CCS_GATEWAY_ID;

/**
 * Parse the requested action
 */
$action = $_SERVER[ 'PATH_INFO' ];

/**
 * Remove leading slash from the action
 */
if ( substr( $action, 0, 1 ) === '/' )
{
	$action = substr( $action, 1 );
}

/**
* Strip output format extension (.xml, .json, etc.) from the action string
*/
$formats = array ( '.xml', '.json','.js', '.plist', '.yaml', '.amf', );
$countFormats = count( $formats );
$outputFormat = NULL;

$i = 0;
do {
    if ( stristr( $action, $formats[ $i ] ) )
    {
        $outputFormat = $formats[ $i ];
        $action = str_replace( $formats[ $i ], '', $action );
    }

    $i++;
} while ( $i < $countFormats and is_null( $outputFormat ) );

/**
 * Create a nonce for the request
 */
$nonce = sha1( microtime( TRUE ) );
$nonce = substr( $nonce, 0, NONCE_PREFIX_LENGTH );

/**
 * Create a signature hash for the request
 */
$signature = hash_hmac( 'sha1',
		$privateKey . $method . $stamp . $nonce . strtolower( $action ),
		$privateKey
		);

/**
 * Build the operational parameters for the proxied request
 */
$operationalParameters = array
(
	'api_key'           => $publicKey,
	'stamp'             => $stamp,
	'nonce'             => $nonce,
	'signature'         => $signature,
);

/**
 * Build list of gateway parameters
 */
$definedConstants = get_defined_constants( CATEGORIZED );
$definedConstants = $definedConstants[ 'user' ];
$gatewayParameters = array ();
foreach( $definedConstants as $constantName => $constantValue )
{
    if ( stristr( $constantName, '_PARAMETER' ) )
    {
        $gatewayParameters[ $constantValue ] = 1;
    }
}

switch ( $method )
{
    case 'PUT':
    case 'POST':
        $isIncludePost = TRUE;
        $post = file_get_contents( "php://input" );
        break;

    default:
        $isIncludePost = FALSE;
}

$rawQuery = $_GET;

/**
 * Populate POST data with any override data
 */
if ( $isIncludePost === TRUE AND empty( $post ) )
{
    $post = array ();

    $queryKeys = array_keys( $rawQuery );
    foreach(  $queryKeys as $key )
    {
        if ( stristr( $key, POST_DATA_FIELD_PREFIX ) )
        {
            $keyName = str_replace( POST_DATA_FIELD_PREFIX, '', $key );
            $post[ $keyName ] = $rawQuery[ $key ];
            unset( $rawQuery[ $key ] );
        }
    }
}

if ( $isMethodOverride === TRUE )
{
    /**
     * If originating request contains password, generate a hash
     */
    if ( !empty( $_REQUEST[ 'username' ] ) AND !empty( $_REQUEST[ 'password' ] ) )
    {
        $userHash = sha1( $nonce . $_REQUEST[ 'username' ] . $_REQUEST[ 'password' ] );

        if ( empty( $post ) )
        {
            $post = array ();
        }

        $post[ 'username' ] = $_REQUEST[ 'username' ];
        $post[ 'userhash' ] = $userHash;

        /**
         * Strip any credentials supplied in the originating query
         */
        $credentials = array ( 'username', 'password' );
        foreach ( $credentials as $parameter )
        {
            if ( !empty( $rawQuery[ $parameter ] ) )
            {
                unset( $rawQuery[ $parameter ] );
            }
        }
    }

    if ( $_SERVER[ 'CONTENT_TYPE' ] == 'application/json' )
    {
        $post = json_encode( $post );
    }
    else
    {
        $post = http_build_query( $post );
    }
}

/**
 * Strip gateway parameters from originating request
 */
$queryParameters = array_diff_key( $rawQuery, $gatewayParameters );

/**
 * Build the proxy query
 */
$queryParameters = array_merge( $queryParameters, $operationalParameters );
$query = http_build_query( $queryParameters );

/**
 * Set cURL options per method
 */
$curlOptions = array
(
	'DEFAULT'	=> array
    (
		CURLOPT_RETURNTRANSFER	=> 1,
		CURLOPT_HEADER			=> 1,
		CURLOPT_TIMEOUT			=> 4,
		CURLOPT_HTTPHEADER		=> $passThruHeaders,
		CURLOPT_FOLLOWLOCATION	=> 1,
		CURLOPT_VERBOSE			=> 1,
		CURLOPT_FAILONERROR		=> FALSE,
		CURLOPT_AUTOREFERER		=> 1,
		CURLOPT_USERAGENT		=> CCS_GATEWAY_ID,
        CURLOPT_SSL_VERIFYPEER  => FALSE,
        CURLOPT_SSL_VERIFYHOST  => FALSE,
	),
	'GET'		=> array (),
	'POST'		=> array
    (
		CURLOPT_POST			=> 1,
		CURLOPT_FRESH_CONNECT	=> 1,
		CURLOPT_FORBID_REUSE	=> 1,
		CURLOPT_POSTFIELDS		=> $post,
	),
	'PUT'		=> array
    (
        CURLOPT_CUSTOMREQUEST   => 'PUT',
		CURLOPT_POSTFIELDS		=> $post,
        CURLOPT_HTTPHEADER      => array ( 'X-HTTP-Method-Override:PUT' ),
    ),
	'DELETE'	=> array
    (
        CURLOPT_CUSTOMREQUEST   => 'DELETE',
    ),
);

/**
 * Build the proxy URI
 */
$uri = BASE_URI;

/**
 * Append a slash to the base URI (if not included)
 */
if ( substr( $uri, -1 ) != '/' ) $uri .= '/';

/**
 * Append the API action to the URI
 */
$uri .= $action;

/**
 * Append the output format to the URI
 */
$uri .= "$outputFormat";

/**
 * Append the query to the URI
 */
$uri .= "?$query";

/**
 * Display intended URI and exit if specified by
 * optional parameter 'no_exec'
 */
if ( isTruthy( $_REQUEST[ NO_EXEC_PARAMETER ] ) )
{
    print '<pre>' .
            $method . ' ' . $uri;
    if ( !empty( $post ) )
    {
        print LF . $post;
    }
    exit();
}

/**
 * Proxy the request via cURL library
 */
$responseRaw = array ();
$curl = curl_init( $uri  );

/**
 * Apply cURL options
 */
curl_setopt_array( $curl,
		$curlOptions[ 'DEFAULT' ] + $curlOptions[ $method ]
	);

/**
 * Execute request
 */
if ( !$responseRaw = curl_exec( $curl ) )
{
	$curlError = 'cURL ERROR ' .
                    curl_errno( $curl ) . ' - ' . curl_error( $curl );

    echo '<pre>' . $curlError;
	curl_close( $curl );

    $logEntry .= LOG_DELIMITER . STATUS_CURL_ERROR .
                    LOG_DELIMITER . $curlError .
                    LOG_DELIMITER . $action;

    $log->rpush( LOG_KEY_PREFIX . $agent, $logEntry );

	exit( 1 );
}

$info = curl_getinfo( $curl );
curl_close( $curl );

/**
 * Parse the raw response into head/body
 */
$responseHead = substr( $responseRaw, 0, $info[ 'header_size' ] );
$responseContent = substr( $responseRaw, $info[ 'header_size' ] );

/**
 * Forward headers to the originating agent
 */
$headers = explode( "\n", $responseHead );

foreach( $headers as $header )
{
	/**
	 * Strip problematic headers
	 */
	if ( strpos( $header, 'Transfer-Encoding: chunked' ) !== FALSE ) continue;

	/**
	 * Set header in response
	 */
	header( $header );
}

/**
 * Set the HTTP status code
 */
header( 'HTTP/ ' . $info[ 'http_code' ] );
echo $responseContent;

$logEntry .= LOG_DELIMITER . $info[ 'http_code' ] .
                LOG_DELIMITER . LOG_DELIMITER . $action;

$log->rpush( LOG_KEY_PREFIX . $agent, $logEntry );

exit();

/**
 * Determine if a given variable contains
 * some variation of the boolean TRUE;
 *
 * @param string $input
 * @return boolean
 *
 * @internal
 */
function isTruthy( $input )
{
    if ( empty( $input ) ) return FALSE;

    $isTrue = FALSE;

    if ( is_string( $input ) ) $input = strtolower( $input );

    $synonyms = array
    (
        'ok', TRUE, 'true', 'yes', 'on', 1
    );

    if (in_array( $input, $synonyms ) ) $isTrue = TRUE;

    return $isTrue;
}