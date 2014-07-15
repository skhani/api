<?php
/**
 * API key class
 * 
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/31/2013 02:22:06 AM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * API key self-management actions
 * 
 * @package DeniZEN_Models
 * @internal
 */
class ApiKey
{
    private $model;
    
    /**
     * Create an instance of the ApiKey model
     */
    function __construct() 
    {
       \lib\DebugConsole::stampFunctionCall();
        
        try
        {
           \lib\DebugConsole::log( "Instantiating DeniZEN CORE model" );
            $this->model = new core\models\ApiKey();
        }
        catch( \Exception $e )
        {
            return new Response( Response::FAIL, $e );
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL, $e );
        }
        
       \lib\DebugConsole::end();
    }
    
    
    /**
     * Retrieve basic API key information 
     * for the requesting agent's key
     * 
     * @category GET
     * 
     * @return Response
     */
    protected function index()
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
       \lib\DebugConsole::log( "Searching for common name" );

        $apiKey = $this->model->info( 
                    $this->restler->request_data[ 'api_key' ] 
                );

        if ( count( $apiKey ) > 0 )
        {
            return new Response( Response::SUCCESS, $apiKey );
        }
        else
        {
            return new Response( Response::FAIL, NULL, 
                    Response::STATUS_NOT_FOUND );
        }
    }
    
    /**
     * Modify the attributes of the agent's API key
     * 
     * @category PUT
     * 
     * @param   array       $request_data
     * 
     * @return  Response
     */
    protected function put( $request_data )
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
        try
        {
            $isSuccess = $this->model->update( 
                    $this->restler->request_data[ 'api_key' ], 
                    $request_data 
                    );
        }
        catch ( \Exception $e )
        {
           \lib\DebugConsole::end();
        
            return new Response( Response::FAIL, $e->getMessage() );
        }
		catch ( LdapException $e )
        {
           \lib\DebugConsole::end();
            
            if ( $e->getCode() == LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, NULL, 
                        Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL, $e->getMessage() );
            }
        }
		
       \lib\DebugConsole::end();

        return new Response( $isSuccess, array () );        
    }
    
    
    /**
     * Deactivate the agent's API key
     * 
     * @return  Response
     * 
     * @category DELETE
     * 
     * @throws \core\errorhandling\LdapException
     * @throws \Exception
     */
    protected function delete()
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
        try
        {
            $isSuccess = $this->model->delete( 
                    $this->restler->request_data[ 'api_key' ]
                    );
        }
        catch ( \Exception $e )
        {
           \lib\DebugConsole::end();
            
            if ( $e->getCode() == \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, NULL, 
                        Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL, $e->getMessage(), 
                        $e->getCode() );
            }
        }
        
       \lib\DebugConsole::end();
        
        return new Response( $isSuccess );        
    }
}