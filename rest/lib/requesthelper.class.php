<?php
/**
 * RequestHelper class
 * 
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date: 01/31/2013 02:22:06 AM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Various utility functions for handling input
 * 
 * @package DeniZEN
 * 
 * @internal
 */
class RequestHelper {
    
    const IS_STRICT       = TRUE;
    
    /**
     * Determine if a given variable contains
     * some variation of the boolean TRUE;
     * 
     * @param string Value to anaylze for TRUE/FALSE
     * 
     * @return boolean
     * 
     * @since Sprint 2
     */
    public static function isTruthy( $input )
    {
        if ( empty( $input ) ) return FALSE;

        $isTrue = FALSE;

        if ( is_string( $input ) ) $input = strtolower( $input );

        $synonyms = array
        (
            '1', 'ok', TRUE, 'true', 'yes', 'on', 1
        ); 

        if ( in_array( $input, $synonyms, self::IS_STRICT ) ) 
        {
            $isTrue = TRUE;
        }

        return $isTrue;
    }
}