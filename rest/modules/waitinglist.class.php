<?php
/**
 * WaitingList class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Waiting List
 *
 * @package DeniZEN_Utilities
 * @since 2013-08-22
 */
class WaitingList
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
            $this->model = new core\models\WaitingList();
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
     * Add a participant to a waiting list
     *
     * @param string Unique identifier for the waiting list
     * @param string Email address for the participant
     *
     * @requestbody <var>attributes</var> {array}
     *      Optional list of attributes that describe the participant
     *      (EX: name, age, city, referrer, etc.)
     *
     * @return \Response
     */
    protected function postParticipant( $listId, $email )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $attributes = array ();

        if ( !empty( $this->restler->request_data[ 'attributes' ] ) )
        {
            $attributes = $this->restler->request_data[ 'attributes' ];
        }

        try
        {
            $result =
                    $this->model->push(
                            $listId,
                            $email,
                            $attributes
                            );
        }
        catch ( InvalidArgumentException $e )
        {

            return new Response(
                            Response::FAIL,
                            $e->getMessage(),
                            $e->getCode()
                            );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL, $e->getMessage() );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS );
    }
}