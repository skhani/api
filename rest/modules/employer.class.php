<?php

/**
 * Store class file
 *
 * @author Shahin Mohammadkhani <skhani@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */
//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Child Store Manager
 *
 * @package DeniZEN_Models
 * @since Sprint 5
 */
class Employer {

    private $model;

    /**
     * Create an instance of the model
     *
     * @internal
     */
    function __construct() {
        \lib\DebugConsole::stampFunctionCall();

        try {
            \lib\DebugConsole::log("Instantiating DeniZEN CORE model");
            $this->model = new core\models\Employer();
        } catch (\Exception $e) {
            return new Response(Response::FAIL, $e);
        } catch (\InvalidArgumentException $e) {
            return new Response(Response::FAIL, $e);
        }

        \lib\DebugConsole::end();
    }

    /**
     * Retieve all retailers given the country
     *
     * @param integer $retailerId Unique identifier for the retailer
     *
     * @category GET
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>retailer_id</var>
     *      Unique Identifier of the retailer
     * @responseparam <var>retailer_name</var>
     *      Common English name for the retailer
     */
    protected function getRetailers($country) {
        \lib\DebugConsole::stampAction();

        try {
            $retailers = $this->model->getRetailers($country);
        } catch (\mysqli_sql_exception $e) {
            return new Response(Response::FAIL, $e);
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() == 'Retailers not found') {
                return new Response(Response::FAIL, $e->getMessage(), Response::STATUS_NOT_FOUND
                );
            } else {
                return new Response(Response::FAIL, $e);
            }
        }

        \lib\DebugConsole::end();

         
        return new Response(Response::SUCCESS, $retailers);
    }

}