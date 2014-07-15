<?php

/**
 * Organiztion class file
 *
 * @author Shahin Mohammadkhani <skhani@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version 4/16/2014 Shahin Mohammadkhani
 */
//use core\lib\utilities\DebugConsole;
use core\errorhandling;


/**
 * Organization
 *
 * @package DeniZEN_Models
 */
class Organization {

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
            $this->model = new core\models\Organization();
        } catch ( \Exception $e ) {
            return new Response(Response::FAIL, $e);
        } catch ( \InvalidArgumentException $e ) {
            return new Response(Response::FAIL, $e);
        }

        \lib\DebugConsole::end();
    }


    /**
     * Temprorary: This class identifies an organization by its orgId.
     * Returnt the organization info given it's Organization Id
     *
     * @category POST
     *
     * @requestbody <var>org_id</var> {string}
     *      Organization Unique Identifier
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>org_id</var>
     *      Unique Identifier of the organization
     * @responseparam <var>course_completion_uri</var>
     *      Uri endpoint to notify the organization of course completion
     * @responseparam <var>course_deeplink_uri</var>
     *      Uri endpoint to deeplink into the organization
     * @responseparam <var>ip_white_list</var>
     *      Comma delimited string with allowed IPs
     * @responseparam <var>hmac</var>
     *      Secret key
     * @responseparam <var>is_deleted</var>
     *      Boolean of whether the organization is deleted or not
     * @responseparam <var>course_subscriptions</var>
     *      List of categories the organization is subscribed to
     * @responseparam <var>api_key</var>
     *      Public key
     * @responseparam <var>is_active</var>
     *      Whether the organization is active and authorized to use the secret
     *      and public key
     * @return \Response
     */
    protected function postIdentify() {
        \lib\DebugConsole::stampAction();
        $this->restler->request_data =
                array_change_key_case($this->restler->request_data);

        if( empty($this->restler->request_data['org_id']) ) {
            return new Response(
                    Response::FAIL, 'Missing or empty parameter - org_id',
                    Response::STATUS_BAD_REQUEST
            );
        }


        try {
            $organization = $this->model->getOrganization($this->restler->request_data['org_id']);
        } catch ( \mysqli_sql_exception $e ) {
            return new Response(Response::FAIL, $e);
        } catch ( \InvalidArgumentException $e ) {
            if( $e->getMessage() == 'Organization not found' ) {
                return new Response(Response::FAIL, $e->getMessage(),
                        Response::STATUS_NOT_FOUND
                );
            } else {
                return new Response(Response::FAIL, $e);
            }
        }

        \lib\DebugConsole::end();


        return new Response(Response::SUCCESS, $organization);
    }


}
