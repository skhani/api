<?php
/**
 * Profile Group class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/31/2013 02:22:06 AM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Profile Group
 *
 * @package DeniZEN_Models
 * @internal
 */
class ProfileGroup
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
            $this->model = new core\models\ProfileGroup();
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
     * Retrieve list of Profile Groups
     *
     * @category GET
     *
     * @return Response
     */
    protected function index()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $groups = $this->model->getAll();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $groups );

    }
}