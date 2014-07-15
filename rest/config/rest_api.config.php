<?php
/**
 * Configuration for CCS RESTful Service Framework
 *
 * @copyright (c) 2012-2014, Creative Channel Services
 * @author Danny Knapp <dknapp@creativechannel.com>
 *
 * @version %Date: 01/23/2013 04:03:33 PM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 *
 * @package DeniZEN
 *
 * @category Configuration
 *
 * @internal
 */

namespace config;

include '/home/www/core/config/environment.php';

/**
 * Debug settings
 */
define( 'APPLICATION_ENV_VARNAME', 'APPLICATION_ENV' );

define( 'DEBUG_ENVIRONMENT_LIST', 'development, dev, stage, staging' );

/**
 * Base64 encoded secret for enabling debug console remotely
 */
define( 'DEBUG_SECRET', 'dGgxcyA0cHAgbjMzZHogbDB2Mw' );



/**
 * Default session hash salt
 */
define( 'SESSION_HASH_SALT', 'afgavaAGArsraaerEADfa' );
define( 'SESSION_HASH_SEGMENT_LENGTH', 8 );

/**
 * Input parameters for user's credentials
 */
define( 'KEY_USERNAME',     'username' );
define( 'KEY_USERHASH',     'userhash' );
define( 'KEY_SESSION_ID',   'session' );

/**
 * Permissible time discrepancy between submitted timestamp and
 * current server time (in seconds)
 */
define( 'MAX_SECONDS_TIMESTAMP_SKEW', 900 );
define( 'MAX_SECONDS_GATEWAY_DEBOUNCE', 1 );

/**
 * Lifetime for caching unique request nonce ids
 */
define( 'NONCE_LIFETIME', 1200 );

/**
 * Floor and ceiling for nonce length
 */
define( 'NONCE_LENGTH_MINIMUM', 8 );
define( 'NONCE_LENGTH_MAXIMUM', 36 );

/**
 * Identifier for cached nonces list
 */
define( 'NONCE_KEY_PREFIX', 'nonces_' );

/**
 * Identifier for submitted nonce
 */
define( 'NONCE_REQUEST_MEMBER_KEY', 'nonce' );

define( 'REQUEST_KEY_TIMESTAMP', 'stamp' );



/**
 * Cache settings
 */
define( 'REST_CACHE_PROTOCOL',   'tcp' );
define( 'REST_CACHE_HOSTS',
        "redis-" . ENVIRONMENT . ".vpc.ccs-internal.net" );
define( 'REST_CACHE_PORT', 6379 );
define( 'REST_CACHE_DB', 0 );