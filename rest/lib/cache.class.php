<?php
/**
 * Cache class
 * 
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/23/2013 04:03:33 PM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

//use core\lib\utilities\DebugConsole;

/**
 * Key-value cache
 * 
 * @package DeniZEN
 * @internal
 */
class Cache 
{
    const PROTOCOL    = REST_CACHE_PROTOCOL;    
    const HOSTS       = REST_CACHE_HOSTS;    
    const PORT        = REST_CACHE_PORT;
    const DB          = REST_CACHE_DB;
    const SEPARATOR    = '|';

    private $_client;
    
    public function __construct() 
    {
        $options = array 
        (
            'scheme'    => self::PROTOCOL,
            'host'      => self::HOSTS,
            'port'      => self::PORT,
            'database'  => self::DB,
        );
        
        $this->_client = new \Predis\Client( $options );
        
        $this->_client->select( self::DB );
    }
    
    /**
     * Determine if an item exists in an ordered set
     * 
     * @param string    $key
     * @param string    $member
     * 
     * @return boolean
     */
    public function isExistsZMember( $key, $member )
    {        
       \lib\DebugConsole::stampFunctionCall();
       
       /**
        * Attempt to retrieve the member's score from the ordered set
        */
       $cacheCommand = new \Predis\Command\ZSetScore();
       $cacheCommand->setArguments( array ( $key, $member ) );       
       $results = $this->_client->executeCommand( $cacheCommand );
       
       /**
        * An empty result indicates that the member doesn't exist
        */
       $isExists = ( !empty( $results ) ) ? TRUE : FALSE;
       
      \lib\DebugConsole::stampBoolean( $isExists );
       return $isExists;
    }
    
    
    /**
     * Add an item to an ordered set
     * 
     * @param string    $key
     * @param string    $member
     * @param float     $value
     * @param integer   $expireSeconds
     * @param boolean   $isFailOnExpirationFailure
     * 
     * @return boolean
     */
    public function addZMember( $key, $member, $value, $expireSeconds = NULL, 
            $isFailOnExpirationFailure = FALSE ) 
    {
      \lib\DebugConsole::stampFunctionCall();
       
       $cacheCommand = new \Predis\Command\ZSetAdd();
       $cacheCommand->setArguments( array ( $key, $value, $member ) );       
       $results = $this->_client->executeCommand( $cacheCommand );
       
      \lib\DebugConsole::log( "Members added = $results" );
       
       if ( !empty( $expireSeconds ) ) 
       {
           $expireResults = $this->setKeyExpiration( $key, $expireSeconds );
           
           if ( !$expireResults and $isFailOnExpirationFailure )
           {
               return FALSE;
           }
       }
       else 
       {
          \lib\DebugConsole::log( "No expiration" );
       }
       
       return ( $results > 0 ) ? TRUE : FALSE;
    }
    
    /**
     * Set the expiration (in seconds) for a given key
     * 
     * @param string    $key
     * @param integer   $expireSeconds
     * @return boolean
     */
    public function setKeyExpiration( $key, $expireSeconds ) 
    {
      \lib\DebugConsole::stampFunctionCall();
      \lib\DebugConsole::log( "Setting $key to expire in $expireSeconds seconds" );
       
       $cacheCommand = new \Predis\Command\KeyExpire();
       $cacheCommand->setArguments( array ( $key, $expireSeconds ) );       
       $expireResults = $this->_client->executeCommand( $cacheCommand );                  
      \lib\DebugConsole::stampBoolean( $expireResults );           
       
       return ( $expireResults > 0 ) ? TRUE : FALSE;
    }
    
    
    /**
     * Write a message to the logging queue
     * 
     * @param string $message Text to notate in the log
     * @param string $logId Friendly name of the log ('access', 'error', etc)
     * 
     * @return boolean
     */
    public function log( $message, $logId = 'access' )
    {
      \lib\DebugConsole::stampFunctionCall();
       
       if ( is_array($message ) )
       {
           $message = implode( self::SEPARATOR, $message );
       }
       
       $message .= self::SEPARATOR . time();
       
       $this->_client->rpush( $logId,$message );
    }
}

