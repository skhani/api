<?php
/**
 * Debugging agent for CCS REST API
 *
 * Do not distribute -- for use only by Creative Channel Services Development
 * Department staff
 *
 * @package DeniZEN_Agents
 * @category Debug-Gateway
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
 * Default public and private API keys
 */
define( 'DEFAULT_AGENT_ID',          'DEBUG'                            );

define( 'DEBUG_PUBLIC_KEY',             'NTBmZjMxODEyYTAwZjUuMzc0MDkwOTA=' );
define( 'DEBUG_PRIVATE_KEY',
            'ZTE3MTQxNDM4NTg5YzJhNGM2ZGNiY2JhMGMyNTM5MDU0YTVkNzk5ZQ==' );

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

define( 'CCS_GATEWAY_ID',               'd3bgR' );

define( 'DEBUG_SECRET',                 'dGgxcyA0cHAgbjMzZHogbDB2Mw' );

define( 'POST_DATA_FIELD_PREFIX',       'postdata' );

define( 'METHOD_PARAMETER',             'override_method'   );
define( 'STAMP_PARAMETER',              'force_stamp'       );
define( 'NONCE_PARAMETER',              'force_nonce'       );
define( 'HOST_PARAMETER',               'api_host'          );
define( 'APIKEY_PARAMETER',             'force_key'         );
define( 'VERBOSE_PARAMETER',            'verbose'           );
define( 'NO_EXECUTE_PARAMETER',         'no_exec'           );

define( 'KEY_DELIMITER',                '::'                );
define( 'PUBLIC_KEY',                   0                   );
define( 'PRIVATE_KEY',                  1                   );

/**
 *  Base service location
 */
define( 'BASE_URL',			'https://'              );
define( 'BASE_DOMAIN',		'.vpc.ccs-internal.net' );
define( 'BASE_URI',			'/rest/v2'              );
define( 'DEFAULT_HOST',     'api-dev'               );

define( 'CATEGORIZED',      TRUE                    );

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
 * Set the appropriate API key to proxy the request
 */
$agent = DEFAULT_AGENT_ID;

if ( !empty( $_REQUEST[ 'agentId' ] ) )
{
    $agent = $_REQUEST[ 'agentId' ];
}

$agent = strtoupper( $agent );

if ( !defined( $agent . '_PUBLIC_KEY' ) )
{
    header('HTTP/1.1 400 Bad request: Unrecoginized agent');
    exit();
}

/**
 * Set the API key per the requesting agent
 */
$publicKey = constant( $agent . '_PUBLIC_KEY' );
$privateKey = constant( $agent . '_PRIVATE_KEY' );

if ( !empty( $_REQUEST[ APIKEY_PARAMETER ] ) )
{
	$key = explode( KEY_DELIMITER, $_REQUEST[ APIKEY_PARAMETER ] );
    $publicKey = $key[ PUBLIC_KEY ];
    $privateKey = $key[ PRIVATE_KEY ];
}

$publicKey = base64_decode( $publicKey );
$privateKey = base64_decode( $privateKey );

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

/**
 * For log-in action(s), only use 'POST' method
 */
$loginActions = array ( 'authuser/login' );
if ( in_array( $action, $loginActions ) )
{
    $method = 'POST';
}

/**
 * Get the timestamp for the originating request
 */
if ( !empty( $_REQUEST[ STAMP_PARAMETER ] ) )
{
	$stamp = $_REQUEST[ STAMP_PARAMETER ];
}
else
{
	$stamp = $_SERVER[ 'REQUEST_TIME' ];
}

/**
 * Create a nonce for the request
 */
if ( !empty( $_REQUEST[ NONCE_PARAMETER ] ) )
{
	$nonce = $_REQUEST[ NONCE_PARAMETER ];
}
else
{
	$nonce = sha1( microtime( TRUE ) );
    $nonce = substr( $nonce, 0, NONCE_PREFIX_LENGTH );
}

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

$rawPost = $_POST;

if ( $method == 'PUT' )
{
    parse_str( file_get_contents( "php://input" ), $rawPost );
}

$rawQuery = $_GET;

/**
 * Populate POST data with any override data
 */
if ( $isMethodOverride )
{
    $queryKeys = array_keys( $rawQuery );
    foreach(  $queryKeys as $key )
    {
        if ( stristr( $key, POST_DATA_FIELD_PREFIX ) )
        {
            $keyName = str_replace( POST_DATA_FIELD_PREFIX, '', $key );
            $rawPost[ $keyName ] = $rawQuery[ $key ];
        }
    }
}

/**
 * Strip operational/gateway parameters from originating request
 */
$queryParameters = array_diff_key( $rawQuery,
        $operationalParameters, $gatewayParameters );

$postParameters = array_diff_key( $rawPost,
        $operationalParameters, $gatewayParameters );

/**
 * If originating request contains password, generate a hash
 */
if ( !empty( $_REQUEST[ 'username' ] ) AND !empty( $_REQUEST[ 'password' ] ) )
{
    $userHash = sha1( $nonce . $_REQUEST[ 'username' ] . $_REQUEST[ 'password' ] );
    $postParameters[ 'username' ] = $_REQUEST[ 'username' ];
    $postParameters[ 'userhash' ] = $userHash;
}

/**
 * Strip any credentials supplied in the originating query
 */
$credentials = array ( 'username', 'password' );
foreach ( $credentials as $parameter )
{
    if ( !empty( $queryParameters[ $parameter ] ) )
    {
        unset( $queryParameters[ $parameter ] );
    }
}

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
		CURLOPT_POSTFIELDS		=> http_build_query( $postParameters ),
	),
	'PUT'		=> array
    (
        CURLOPT_CUSTOMREQUEST   => 'PUT',
		CURLOPT_POSTFIELDS		=> http_build_query( $postParameters ),
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
$hostName =
        ( !empty( $_REQUEST[ HOST_PARAMETER ] )
            ? $_REQUEST[ HOST_PARAMETER ] : DEFAULT_HOST );

$uri = BASE_URL . $hostName . BASE_DOMAIN . BASE_URI;

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
 * @internal Enable debugging console via proxy
 */
$uri .= "&debug=" . sha1( $nonce . base64_decode( DEBUG_SECRET ) );

/**
 * Display intended URI and exit if specified by
 * optional parameter 'no_exec'
 */
if (array_key_exists('no_exec', $_REQUEST)) {                                                                                            
     if (isTruthy($_REQUEST['no_exec'])) {

         print "$method $uri";
         exit();
     }
 }

/**
 * Proxy the request via cURL library
 */
$responseRaw = array ();
$curl = curl_init( $uri );
curl_setopt($curl, CURLOPT_SSLVERSION,3);
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
	echo "<pre>cURL ERROR " . curl_errno( $curl ) . " - " . curl_error( $curl );
	curl_close( $curl );
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

/**
 * If requested, generate prettified content
 */
if ( !empty( $_GET[ 'verbose' ] ) AND isTruthy( $_GET[ 'verbose' ] ) )
{
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Content-Encoding: identity' );
	$responseOutput = htmlentities( $responseContent );

	echo "
		<html>
			<head>
                <link href='//google-code-prettify.googlecode.com/svn/trunk/src/prettify.css' rel='stylesheet' type='text/css' />
				<script src='//cdnjs.cloudflare.com/ajax/libs/prettify/188.0.0/prettify.js' type='text/javascript'></script>
			</head>
			<body onload='prettyPrint();'>
	";

	echo "<h3>Response: " . $info[ 'http_code' ] . "</h3>" . LF;
	echo "<pre class='prettyprint'>" . $responseOutput . "</pre>" . LF;

	echo "<p>" . LF;
	echo "<h3>Request:</h3>";
	echo "$method <a href='$uri'>$uri</a>" . LF;
	echo "</p>" . LF;

	if ( $method == 'POST' )
	{
		echo "<h3>Post:</h3>" . LF;
		echo "<pre class='prettyprint'>";
		var_dump( $postParameters );
		echo "</pre>";
	}

	echo "<h3>Input:</h3>" . LF;
	echo "<pre class='prettyprint'>";
	var_dump( $queryParameters );
	echo "</pre>";

	echo "<h3>Proxy Info:</h3>" . LF;
	echo "<pre class='prettyprint'>";
	var_dump( $_SERVER );
	echo "</pre>";

	echo "</body></html>";
}
else
{
	/**
	 * Output proxied content
	 */
	echo $responseContent;
}


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