<?php

/**
 * Store class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
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
class Store {

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
            $this->model = new core\models\Store();
        } catch (\Exception $e) {
            return new Response(Response::FAIL, $e);
        } catch (\InvalidArgumentException $e) {
            return new Response(Response::FAIL, $e);
        }

        \lib\DebugConsole::end();
    }

    /**
     * Retieve all stores given the retailer id
     *
     * @param integer $retailerId Unique identifier for the retailer
     *
     * @category GET
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>store_name</var>
     *      Common English name for the store
     * @responseparam <var>store_id</var>
     *      Store ID
     */
    protected function getAll($retailerId) {
        \lib\DebugConsole::stampAction();

        try {
            $stores = $this->model->getAll($retailerId);
        } catch (\mysqli_sql_exception $e) {
            return new Response(Response::FAIL, $e);
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() == 'Retailer ID not found') {
                return new Response(Response::FAIL, $e->getMessage(), Response::STATUS_NOT_FOUND
                );
            } else {
                return new Response(Response::FAIL, $e);
            }
        }

        \lib\DebugConsole::end();

         
        return new Response(Response::SUCCESS, $stores);
    }

    /**
     * Retrieve attributes for a store
     *
     * @param integer $storeId Unique identifier for the store
     *
     * @category GET
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>store_name</var>
     *      Common English name for the store
     * @responseparam <var>store_number</var>
     *      Store location number
     * @responseparam <var>retailer_id</var>
     *      Retailer Id where the store belongs to
     * @responseparam <var>store_address</var>
     *      Address of the store
     * @responseparam <var>store_city</var>
     *      City of the store
     * @responseparam <var>store_postal_code</var>
     *      Postal Code of the store
     * @responseparam <var>store_state_province</var>
     *      State or Province of the store
     * @responseparam <var>store_country</var>
     *      Country of the store
     */
    protected function get($storeId) {
        \lib\DebugConsole::stampAction();

        try {
            $store = $this->model->get($storeId);
        } catch (\mysqli_sql_exception $e) {
            return new Response(Response::FAIL, $e);
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() == 'Store ID not found') {
                return new Response(Response::FAIL, $e->getMessage(), Response::STATUS_NOT_FOUND
                );
            } else {
                return new Response(Response::FAIL, $e);
            }
        }

        \lib\DebugConsole::end();

         
        return new Response(Response::SUCCESS, $store);
    }
}