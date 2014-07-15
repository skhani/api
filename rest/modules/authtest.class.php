<?php
/**
 * Authorization methods testing class
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/23/2013 04:03:33 PM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Test public/API/user-level access methods
 *
 * @package DeniZEN_Utilities
 */
class AuthTest
{
    /**
     * Test for routing to index() method
     *
     * @category GET
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>index</var> {string}
     * Reply of 'You hit the index method'
     */
    public function index()
    {
        return new Response( Response::SUCCESS, array ( 'index' => 'You hit the index method' ) );
    }

    /**
     * Test open access
     *
     * @category GET
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>answer</var> {string}
     * Reply of 'Free to the public'
     */
    public function testPublic()
    {
       \lib\DebugConsole::stampAction( $this->restler );
       \lib\DebugConsole::log( $this->restler );

        return new Response( Response::SUCCESS, array ( 'answer' => "Free to the public" ) );
    }


    /**
     * Test API authorization layer
     *
     * @category GET
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>index</var> {string}
     * Reply of 'I have a valid API key'
     */
    protected function testAuthApi()
    {
       \lib\DebugConsole::stampAction( $this->restler );
       \lib\DebugConsole::log( $this->restler );

        return new Response( Response::SUCCESS, array ( 'answer' => "I have a valid API key" ) );
    }


    /**
     * Test user authorization layer
     *
     * @category GET
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>answer</var> {string}
     * Reply of 'I am logged in'
     *
     * @class AuthMember(isAuthRequired=TRUE)
     */
    protected function testAuthMember()
    {
       \lib\DebugConsole::stampAction( $this->restler );
       \lib\DebugConsole::log( $this->restler );



        return new Response( Response::SUCCESS, array ( 'answer' => "I am logged in" ) );
    }


    /**
     * Test API key group authorization layer
     *
     * @category GET
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>index</var> {string}
     * Reply of 'My API key is in the admin group'
     *
     * @class AuthApiGroup(groupRequired=admin)
     *
     * @internal
     */
    protected function testAuthAdmin()
    {
       \lib\DebugConsole::stampAction( $this->restler );
       \lib\DebugConsole::log( $this->restler );



        return new Response( Response::SUCCESS,
                array ( 'answer' => "My API key is in the admin group" ) );
    }


    /**
     * Test API key group authorization layer
     *
     * @category GET
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>index</var> {string}
     * Reply of 'You should not be able to see this message'
     *
     * @class AuthApiGroup(groupRequired=invalid)
     *
     * @internal
     */
    protected function testAuthInvalidGroup()
    {
       \lib\DebugConsole::stampAction( $this->restler );
       \lib\DebugConsole::log( $this->restler );



        return new Response( Response::SUCCESS,
                array
                (
                    'answer' => "You should not be able to see this message"
                )
        );
    }
}