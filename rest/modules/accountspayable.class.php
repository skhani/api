<?php
/**
 * AccountsPayable class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */

use core\errorhandling;

/**
 * AccountsPayable class for various financial functions
 *
 * @package DeniZEN_Utilities
 * @since 2013-12-09
 */
class AccountsPayable
{
    private $model;

    /**
     * Create an instance of the model
     *
     * @internal
     */
    function __construct()
    {
       \lib\DebugConsole::stampFunctionCall();

        try
        {
           \lib\DebugConsole::log( "Instantiating DeniZEN CORE model" );
            $this->model = new core\models\AccountsPayable();
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
     * This function doesn't pertain
     *
     * @ignore
     */
    protected function index() {}


    /**
     * Retrieve a list of payment services
     *
     * @category GET
     *
     * @return \Response
     */
    public function getServices()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $services = $this->model->getServices();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $services );
    }


    /**
     * Retrieve a list of payment services and friendly names
     *
     * @category GET
     *
     * @return \Response
     */
    public function getServiceFriendlyNames()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $services = $this->model->getServiceFriendlyNames();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $services );
    }


    /**
     * Load the list of payment services into the cache
     *
     * @category PUT
     * @internal
     *
     * @class AuthApiGroup(groupRequired=admin)
     *
     * @return \Response
     * @responseparam
     */
    protected function putServices()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $services = $this->model->loadServices();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $services );
    }
}