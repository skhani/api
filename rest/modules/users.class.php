<?php
/**
 * Users class
 * 
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/23/2013 04:03:33 PM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 * 
 * @internal
 */

/**
 * Load model dependencies
 */
use core\lib\connectors\Ldap;

/**
 * User Manager
 * 
 * @package DeniZEN_Models
 * 
 * @ignore Experimental module
 */
class Users 
{
    /**
     * User object model
     * 
     * @var object
     */
    private $model;
    
    /**
     * Create an instance of the user model
     */
    function __construct() 
    {
        \DebugConsole::log( "instantiating LDAP manager" );
        try
        {
            $this->model = new Ldap();
        }
        catch( \Exception $e )
        {
            return new Response( Response::FAIL, $e );
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL, $e );
        }
    }
     
    
    /**
     * Default action
     */
    protected function index()
    {
       \lib\DebugConsole::log(  
                "action redirects to directory()" );
        
        return $this->directory();
    }
    
    
    /**
     * Retrieve all available attributes
     * 
     * @todo Need to filter by objectclass
     * 
     * @return \Response
     */
    protected function attributes()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $data = $this->model->listUserAttributes();
        }
        catch ( \Exception $e )
        {
            \DebugConsole::stampException( $e );
            return new Response( Response::FAIL, $e, Response::STATUS_ERROR );
        }
        catch ( \LdapException $e )
        {
            \DebugConsole::stampException( $e );
            return new Response( Response::FAIL, $e, Response::STATUS_ERROR );
        }
        
        return new Response( Response::SUCCESS, $data );   
    }
    
    /**
     * Retrieve a list of users' basic information
     * 
     * @param integer $limit
     * @return \Response
     */
    protected function directory( $limit = Ldap::RESULTS_DEFAULT_COUNT )
    {        
       \lib\DebugConsole::stampAction( $this->restler );
        
        if ( !filter_var( $limit, FILTER_VALIDATE_INT ) )
        {
            return new Response( Response::FAIL, 
                    "Invalid limit", Response::STATUS_BAD_REQUEST );
        }        
        
        try
        {
            $data = $this->model->getUsersList( intval( $limit ) );
        }
        catch ( \InvalidArgumentException $e )
        {
            \DebugConsole::stampException( $e );
            return new Response( Response::FAIL, $e );
        }
        catch ( \Exception $e )
        {
            \DebugConsole::stampException( $e );
            return new Response( Response::FAIL, $e, Response::STATUS_ERROR );
        }
        catch ( \LdapException $e )
        {
            \DebugConsole::stampException( $e );
            return new Response( Response::FAIL, $e, Response::STATUS_ERROR );
        }
        
        return new Response( Response::SUCCESS, $data );
    }
}