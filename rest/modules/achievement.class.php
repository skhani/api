<?php
/**
 * Achievement class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Application-defined goals
 *
 * An achievement represents a quantitative goal that a profile (user) can
 * accomplish in an application. As the profile interacts within the
 * application, he or she makes progress towards completing the achievement.
 * When the user meets or exceeds the goal, the achievement is considered
 * earned. Each application defines the goal and the mechanics that describe
 * how a profile earns the achievement.
 *
 * In practice, the Profile Manager does not need to know the details of
 * an application; it only knows what progress the profile has made towards an
 * achievement.
 *
 * @package DeniZEN_Models
 * @since Sprint 6
 */
class Achievement
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
            $this->model = new core\models\Achievement();
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
     * Retrieve a list of all known achievements
     *
     * @category GET
     *
     * @requestparam <var>include_unpublished</var> {boolean}
     *      If TRUE, the result set includes achievements notated as unpublished
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>application_uuid</var>
     *      Unique identifier for the associated application
     * @responseparam <var>uuid</var>
     *      Unique identifier for the achievement
     * @responseparam <var>name</var>
     *      Common English name for the achievement
     * @responseparam <var>logo</var>
     *      Global resource path for the achievement badge
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
            $achievements = $this->model->getAll(
                    \core\models\Achievement::APPLICATIONS_ALL,
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

        return new Response( Response::SUCCESS, $achievements );
    }


    /**
     * Create a new achievement
     *
     * @param string Unique identifier for the parent application
     *
     * @requestbody <var>name</var> {string}
     *      Friendly name for the achievement
     *      (en-us, 45 characters maximum)
     * @requestbody <var>description</var> {string}
     *      Summary explanation for the achievement
     *      (en-us, 300 characters maximum)
     * @requestbody <var>is_active</var> {boolean}
     *      Designates whether or not the achievement is available for
     *      assignement to profiles
     * @requestbody <var>sort_id</var> {integer}
     *      The ordinal position for the achievement
     * @requestbody <var>logo_uri</var> {string}
     *      The universal resource for the achievement badge image
     *
     * @return \Response Operational attributes for the achievement. Refer to
     * response parameters
     *
     * @responseparam <var>uuid</var> {string}
     *      The unique identifier for the new achievement
     *
     * @class AuthApiGroup(groupRequired=admin)
     */
    protected function post( $applicationId )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $achievement =
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

        return new Response( Response::SUCCESS, $achievement );
    }


    /**
     * Modify an achievement
     *
     * @param string Unique identifier for the achievement
     * @param mixed Refer to Achievement::post() for attribute list
     *
     * @return \Response
     *
     * @class AuthApiGroup(groupRequired=admin)
     */
    protected function put( $uuid, $request_data )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Determine if the achievement UUID is valid
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
         * Update the achievement attributes
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
     * Retrieve a list of all known achievements for a given application
     *
     * @category GET
     *
     * @param string Unique identifier for the application
     *
     * @requestparam <var>include_unpublished</var> {boolean}
     *      If TRUE, the result set includes achievements notated as unpublished
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>uuid</var> {string}
     *      Unique identifier for the achievement
     * @responseparam <var>name</var> {string}
     *      Friendly name for the achievement (en-us)
     * @responseparam <var>logo</var> {string}
     *      Global resource path for the achievement badge
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
            $achievements = $this->model->getAll(
                    $uuid,
                    $isIncludeUnpublished
                    );
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

        return new Response( Response::SUCCESS, $achievements );
    }



    /**
     * Retrieve the summary for the achievement
     *
     * @param string Unique identifier for the achievement
     *
     * @return \Response
     *
     * @responseparam <var>description</var> {string}
     *      Long description for the achievement
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
     * Retrieve the unique identifier for an achievement
     *
     * @param string Unique identifier for the associated application
     * @param string Friendly name for the achievement
     *
     * @return \Response
     *
     * @responseparam <var>uuid</var> {string}
     *      Unique identifier for the achievement
     */
    protected function getUuid( $applicationId, $friendlyName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $achievement =
                    $this->model->getUuid( $friendlyName, $applicationId );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                $e->getMessage(), $e->getCode()
                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $achievement );
    }


    /**
     * Retrieve all attributes for an achievement
     *
     * @param string Unique identifier for the achievement
     *
     * @return \Response
     * @responseparam <var>achievement_uuid</var> {string}
     *      Unique identifier of the achievement
     * @responseparam <var>name</var> {string}
     *      Friendly name for the achievement
     * @responseparam <var>logo</var> {string}
     *      Resource for the default badge image
     * @responseparam <var>description</var> {string}
     *      Summary text that explains the achievement
     * @responseparam <var>application_uuid</var> {string}
     *      Unique identifier for the associated application
     */
    protected function get( $uuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $achievement = $this->model->get( $uuid );
        }
        catch ( Exception $e )
        {
            return new Response( Response::FAIL,
                $e->getMessage(), $e->getCode()
                );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $achievement );
    }


    /**
     * Mark an achievement as deleted
     *
     * @param string Unique identifier for the achievement
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
            $achievement = $this->model->delete( $uuid );
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
