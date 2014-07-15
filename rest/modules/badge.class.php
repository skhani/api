<?php
/**
 * Badge class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */

use core\errorhandling;

/**
 * Application-defined badges
 *
 * A badge represents a designation that a profile (user) can obtain in an
 * application. Each application defines the goal and the mechanics that
 * describe how a profile acquires the badge.
 *
 * @package DeniZEN_Models
 * @since 2013-11-27
 */
class Badge
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
            $this->model = new core\models\Badge();
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
     * Retrieve a list of all known badges
     *
     * @category GET
     *
     * @requestparam <var>include_unpublished</var> {boolean}
     *      If TRUE, the result set includes badges notated as unpublished
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>application_uuid</var>
     *      Unique identifier for the associated application
     * @responseparam <var>uuid</var>
     *      Unique identifier for the badge
     * @responseparam <var>name</var>
     *      Common English name for the badge
     * @responseparam <var>expires_timestamp</var>
     *      POSIX timestamp for the expiration date/time for the badge
     * @responseparam <var>logo</var>
     *      Global resource path for the badge image
     */
    protected function getAll()
    {
       \lib\DebugConsole::stampAction( $this->restler );

       $isIncludeUnpublished = FALSE;
       if ( !empty( $this->restler->request_data[ 'include_unpublished' ] ) )
       {
           $isIncludeUnpublished = RequestHelper::isTruthy(
                   $this->restler->request_data[ 'include_unpublished' ]
                   );
       }

        try
        {
            $badges = $this->model->getAll(
                    \core\models\Badge::APPLICATIONS_ALL,
                    $isIncludeUnpublished
                    );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                $e->getMessage(), $e->getCode()
                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $badges );
    }


    /**
     * Create a new badge
     *
     * @param string Unique identifier for the parent application
     *
     * @requestbody <var>name</var> {string}
     *      Friendly name for the badge
     *      (en-us, 45 characters maximum)
     * @requestbody <var>description</var> {string}
     *      Summary explanation for the badge
     *      (en-us, 300 characters maximum)
     * @requestbody <var>is_active</var> {boolean}
     *      Designates whether or not the badge is available for
     *      assignement to profiles
     * @requestbody <var>expires_timestamp</var> {integer}
     *      POSIX timestamp for the expiration date/time for the badge
     * @requestbody <var>sort_id</var> {integer}
     *      The ordinal position for the badge
     * @requestbody <var>logo_uri</var> {string}
     *      The universal resource for the badge image image
     *
     * @return \Response Operational attributes for the badge. Refer to
     * response parameters
     *
     * @responseparam <var>uuid</var> {string}
     *      The unique identifier for the new badge
     *
     * @class AuthApiGroup(groupRequired=admin)
     */
    protected function post( $applicationId )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $badge =
                    $this->model->create(
                            $applicationId,
                            $this->restler->request_data
                            );
        }
        catch ( InvalidArgumentException $e )
        {
            return new Response(
                            Response::FAIL,
                            $e->getMessage(),
                            Response::STATUS_BAD_REQUEST
                            );
        }
        catch ( mysqli_sql_exception $e )
        {
            $code = $e->getCode();

            if ( $code == \core\lib\connectors\SqlStore::ERROR_DUPLICATE_ENTRY
                    AND stristr( $e->getMessage(), 'duplicate' ) )
            {
                $code = Response::STATUS_BAD_REQUEST;
            }

            if ( $code ==
                    \core\lib\connectors\SqlStore::ERROR_FOREIGN_KEY_CONSTRAINT )
            {
                $code = Response::STATUS_NOT_FOUND;
            }

            return new Response( Response::FAIL,
                                    $e->getMessage(),
                                    $code
                                );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                                    $e->getMessage(),
                                    $e->getCode()
                                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $badge );
    }


    /**
     * Modify a badge
     *
     * @param string Unique identifier for the badge
     * @param mixed Refer to Badge::post() for attribute list
     *
     * @return \Response
     *
     * @class AuthApiGroup(groupRequired=admin)
     */
    protected function put( $uuid, $request_data )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Determine if the badge UUID is valid
         */
        try
        {
            if ( !$this->model->isExists( $uuid ) )
            {
                return new Response(
                            Response::FAIL,
                            NULL,
                            Response::STATUS_NOT_FOUND
                            );
            }
        }
        catch ( Exception $e )
        {
            return new Response(
                            Response::FAIL,
                            $e->getMessage(),
                            $e->getCode()
                            );
        }

        /**
         * Update the badge attributes
         */
        try
        {
            $isUpdated =
                    $this->model->update(
                            $uuid,
                            $request_data
                            );
        }
        catch ( InvalidArgumentException $e )
        {
            return new Response(
                            Response::FAIL,
                            $e->getMessage(),
                            Response::STATUS_BAD_REQUEST
                            );
        }
        catch ( mysqli_sql_exception $e )
        {
            $code = $e->getCode();

            if ( $code == 1062 AND stristr( $e->getMessage(), 'duplicate' ) )
            {
                $code = Response::STATUS_BAD_REQUEST;
            }

            return new Response( Response::FAIL,
                                    $e->getMessage(),
                                    $code
                                );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                                    $e->getMessage(),
                                    $e->getCode()
                                );
        }

       \lib\DebugConsole::end();

        return new Response( $isUpdated );
    }


    /**
     * Retrieve a list of all known badges for a given application
     *
     * @category GET
     *
     * @param string Unique identifier for the application
     *
     * @requestparam <var>include_unpublished</var> {boolean}
     *      If TRUE, the result set includes badges notated as unpublished
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>uuid</var> {string}
     *      Unique identifier for the badge
     * @responseparam <var>name</var> {string}
     *      Friendly name for the badge (en-us)
     * @responseparam <var>expires_timestamp</var>{integer}
     *      POSIX timestamp for the expiration date/time for the badge
     * @responseparam <var>logo</var> {string}
     *      Global resource path for the badge image
     */
    protected function getApplication( $uuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

       $isIncludeUnpublished = FALSE;
       if ( !empty( $this->restler->request_data[ 'include_unpublished' ] ) )
       {
           $isIncludeUnpublished = RequestHelper::isTruthy(
                   $this->restler->request_data[ 'include_unpublished' ]
                   );
       }

        try
        {
            $badges = $this->model->getAll( $uuid, $isIncludeUnpublished );
        }
        catch ( Exception $e )
        {
            if ( $e->getMessage() == "$uuid not found" )
            {
                return new Response( Response::FAIL,
                    $e->getMessage(), Response::STATUS_NOT_FOUND
                    );
            }
            else
            {
                return new Response( Response::FAIL,
                    $e->getMessage(), $e->getCode()
                    );
            }

        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $badges );
    }



    /**
     * Retrieve the summary for the badge
     *
     * @param string Unique identifier for the badge
     *
     * @return \Response
     *
     * @responseparam <var>description</var> {string}
     *      Long description for the badge
     */
    protected function getDescription( $uuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $description = $this->model->getDescription( $uuid );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                $e->getMessage(), $e->getCode()
                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $description );
    }


   /**
     * Retrieve the unique identifier for a badge
     *
     * @param string Unique identifier for the associated application
     * @param string Friendly name for the badge
     *
     * @return \Response
     *
     * @responseparam <var>uuid</var> {string}
     *      Unique identifier for the badge
     */
    protected function getUuid( $applicationId, $friendlyName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $badge =
                    $this->model->getUuid( $friendlyName, $applicationId );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                $e->getMessage(), $e->getCode()
                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $badge );
    }


    /**
     * Retrieve all attributes for a badge
     *
     * @param string Unique identifier for the badge
     *
     * @return \Response
     *
     * @responseparam <var>name</var> {string}
     *      Friendly name for the badge
     * @responseparam <var>logo</var> {string}
     *      Resource for the default badge image
     * @responseparam <var>description</var> {string}
     *      Summary text that explains the badge
     * @responseparam <var>expires_timestamp</var>
     *      POSIX timestamp for the expiration date/time for the badge
     * @responseparam <var>application_uuid</var> {string}
     *      Unique identifier for the associated application
     */
    protected function get( $uuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $badge = $this->model->get( $uuid );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                $e->getMessage(), $e->getCode()
                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $badge );
    }


    /**
     * Mark a badge as deleted
     *
     * @param string Unique identifier for the badge
     *
     * @return \Response
     *
     * @class AuthApiGroup(groupRequired=admin)
     */
    protected function delete( $uuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $badge = $this->model->delete( $uuid );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                $e->getMessage(), $e->getCode()
                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, NULL );
    }
}