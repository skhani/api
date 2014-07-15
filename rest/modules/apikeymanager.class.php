<?php
/**
 * API key manager class
 * 
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/31/2013 02:22:06 AM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 * 
 * @internal
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Manage API keys
 * 
 * @package DeniZEN_Models
 * 
 * @class AuthApiGroup(groupRequired=admin)
 * 
 * @ignore Class not yet implemented
 */
class ApiKeyManager
{
    private $model;
    
    /**
     * Create an instance of the user model
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
     * @return Response
     */
    protected function index() {}
	
    
    /**
     * Retrieve all listed information for an API key 
     * based on an attribute
     * 
     * @param string $attribute
     * @param string $query
     * 
     * @return Response
     */
    protected function get( $attribute, $query )
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
        $attribute = strtolower( $attribute );
        
        $attributeActions = array 
        ( 
            'displayname', 
            'publickey', 'cn', 
            'email', 'mail', 
            'uuid', 'nsuniqueid' 
        );
        
        if ( !in_array( $attribute, $attributeActions ) )
        {
           \lib\DebugConsole::warn( "Invalid attribute = $attribute" );
           \lib\DebugConsole::end();
            
            return new Response ( Response::FAIL, NULL, 
                    Response::STATUS_BAD_REQUEST );
        }
        
        try
        {
            $apiKey = $this->model->get( $attribute, $query );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS )
            {
               \lib\DebugConsole::end();
                
                return new Response( Response::FAIL, $e->getMessage(), 
                        $e->getCode() );
            }
        }
        
       \lib\DebugConsole::end();
        
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
     * Create a new member profile
     * 
     * @return Response
     */
    protected function post()
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
        $inputParameters = array_change_key_case( $this->restler->request_data );
        
       \lib\DebugConsole::log( $inputParameters, "Submitted User" );
        
		$permittedAttributes = array_flip( $this->model->permittedAttributes() );
		
		$inputParameters = 
				array_intersect_key( $inputParameters, $permittedAttributes );
		
		try
        {
            $newProfile = $this->model->create( $inputParameters );
        }
        catch ( \Exception $e )
        {
           \lib\DebugConsole::end();
			
			return 
				new Response( Response::FAIL, $e->getMessage(), $e->getCode() );
        }		
        catch ( LdapException $e )
        {
           \lib\DebugConsole::end();
			
			return 
				new Response( Response::FAIL, $e->getMessage(), $e->getCode() );
        }
				
       \lib\DebugConsole::end();
        
        return new Response( Response::SUCCESS, $newProfile );        
    }
    
	
    /**
     * Restore a member profile
     * 
     * @param   string      $userName
     * 
     * @return  Response
     */
    protected function putUndelete( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
        try
        {
            $isSuccess = $this->model->unDelete( $userName );
        }
        catch ( \core\errorhandling\LdapException $e )
        {
            if ( $e->getCode() == 
                    \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, NULL, 
                        Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL, 
                        $e->getCode . ' - ' . $e->getMessage
                        );
            }
        }
        
       \lib\DebugConsole::end();
        
        return new Response( $isSuccess );        
    }
	
    
    /**
     * Modify the attributes of an existing member profile
     * 
     * @param   string      $userName
     * @param   array       $request_data
     * 
     * @return  Response
     */
    protected function put( $userName, $request_data )
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
        
        try
        {
            $isSuccess = $this->model->update( $userName, $request_data );
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
     * Remove a member profile
     * 
     * @param   string      $userName
     * 
     * @return  Response
     * 
     * @throws \core\errorhandling\LdapException
     * @throws \Exception
     */
    protected function delete( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );
        
        try
        {
            $isSuccess = $this->model->delete( $userName );
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