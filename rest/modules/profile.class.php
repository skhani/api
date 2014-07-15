<?php
/**
 * Profile class
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2012-2014, Creative Channel Services
 * @version %Date: 01/31/2013 02:22:06 AM % %Author: Danny Knapp <dknapp@creativechannel.com> %
 */

use core\errorhandling;

/**
 * Manage member profiles
 *
 * @package DeniZEN_Models
 * @api
 */
class Profile
{
    private $model;

    /**
     * Create an instance of the user model
     *
     * @return void()
     *
     * @internal
     */
    function __construct()
    {
       \lib\DebugConsole::stampFunctionCall();

        try
        {
           \lib\DebugConsole::log( "Instantiating DeniZEN CORE model" );
            $this->model = new core\models\Profile();
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
     * Determine if a password meets the criteria for the security policy
     *
     * @requestbody <var>userhash</var> {string}
     *      Password to test
     *
     *      <p>**NOTE:** Value must be base64-encoded and must only exist in the
     *      request body
     *
     *
     * Password strength requirements:
     *      <ul>
     *          <li> 8-40 characters
     *          <li> at least one alpha character (e.g. a-z and A-Z)
     *          <li> at least one numeric character (e.g. 0-9)
     *          <li> no more than 3 repetitive characters
     *      </ul>
     *
     * @requestparam <var>is_pass_fail</var> {boolean} Return pass/fail response
     * instead of status code and validation messages
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>is_valid</var> {boolean}
     * Pass/fail validation for the password
     * @responseparam <var>code</var> {integer}
     * Decimal value for the validation flags. This parameter is not
     * included when 'is_pass_fail' is TRUE
     * @responseparam <var>violations</var> {array}
     * Textual messages for each validation flag. This parameter is not
     * included when 'is_pass_fail' is TRUE
     *
     * @category POST
     *
     * @since Sprint 2
     */
	public function postValidatePassword()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $inputData = array_change_key_case( $this->restler->request_data );

        $passwordHash = $inputData[ 'userhash' ];

        $isBooleanResponse = FALSE;

        if (\RequestHelper::isTruthy( $this->restler->request_data[ 'is_pass_fail' ] ) )
        {
            $isBooleanResponse = TRUE;
        }

        try
        {
            $passwordStatus =
                $this->model->validatePassword( $passwordHash,
                        $isBooleanResponse );
        }
        catch (\InvalidArgumentException $e )
        {
            return new Response( Response::FAIL, $e->getMessage() . ": " .
                        $e->getCode() );
        }

        if ( $isBooleanResponse )
        {
           \lib\DebugConsole::end();

            return new Response( Response::SUCCESS,
                    array ( 'is_valid' => $passwordStatus )
            );
        }

        $violations = array ();

        if ( $passwordStatus == \core\models\Profile::PASSWORD_STATUS_OK )
        {
            $isValid = TRUE;
        }
        else
        {
            $isValid = FALSE;
            $flags = \core\models\Profile::getFlags( 'password' );
            foreach( $flags as $flag => $value )
            {
                if ( $passwordStatus & $value )
                {
                    $violations[] = $flag;
                }
            }
        }

        $preparedResponse = array
        (
            'code'      => $passwordStatus,
            'is_valid'  => $isValid,
        );

        if ( count( $violations) > 0 )
        {
            $preparedResponse[ 'violations' ] = $violations;
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $preparedResponse );
    }


    /**
     * Determine if an email address is formatted correctly and that the
     * specified domain has a valid mail route
     *
     * @param string Email address to validate
     *
     * @requestparam <var>is_pass_fail</var> {boolean}
     * If TRUE, the action will return a pass/fail response instead of a status
     * code and validation messages
     *
     * @requestparam <var>is_only_validate_format</var> {boolean}
     * If TRUE, the email address is only confirmed to be a valid RFC822 mail
     * address, which is more efficient for evaluating user input near real-time
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>is_valid</var> {boolean}
     * Pass/fail validation for the email address
     * @responseparam <var>code</var> {integer}
     * Decimal value for the validation flags. This parameter is not
     * included when 'is_pass_fail' is TRUE
     * @responseparam <var>violations</var> {array}
     * Textual messages for each validation flag. This parameter is not
     * included when 'is_pass_fail' is TRUE
     *
     * @category POST
     *
     * @since Sprint 2
     */
	public function postValidateEmail( $email )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $request_data = array_change_key_case( $this->restler->request_data );

        $isBooleanResponse = FALSE;

        if ( \RequestHelper::isTruthy(
                $request_data[ 'is_pass_fail' ] ) )
        {
            $isBooleanResponse = TRUE;
        }

        $isOnlyValidateFormat = FALSE;
        if ( \RequestHelper::isTruthy(
                $request_data[ 'is_only_validate_format' ] ) )
        {
            $isOnlyValidateFormat = TRUE;
        }

        $emailStatus =
            $this->model->validateEmailAddress( $email,
                    $isBooleanResponse, $isOnlyValidateFormat );

        if ( $isBooleanResponse )
        {
           \lib\DebugConsole::end();

            return new Response( Response::SUCCESS,
                    array ( 'is_valid' => $emailStatus )
            );
        }

        $violations = array ();

        if ( $emailStatus == \core\models\Profile::EMAIL_STATUS_OK )
        {
            $isValid = TRUE;
        }
        else
        {
            $isValid = FALSE;
            $flags = \core\models\Profile::getFlags( 'email' );
            foreach( $flags as $flag => $value )
            {
                if ( $emailStatus & $value )
                {
                    $violations[] = $flag;
                }
            }
        }

        $preparedResponse = array
        (
            'code'      => $emailStatus,
            'is_valid'  => $isValid,
        );

        if ( count( $violations) > 0 )
        {
            $preparedResponse[ 'violations' ] = $violations;
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $preparedResponse );
    }


    /**
     * Retrieve the validation bit flags for an attribute
     *
     * @param string Name of the target attribute (email, password)
     *
     * @return \Response List of validation flags. Refer to response paramaters
     * for more information
     *
     * @responseparam <var>*description*</var> {integer}
     * Decimal value for the flag. The parameter keyname provides a friendly
     * description of the flag
     *
     * @category GET
     * @since Sprint 2
     */
    protected function getValidationFlags( $flagId )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $flags = \core\models\Profile::getFlags( $flagId );
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

        return new Response( Response::SUCCESS, $flags );
    }


    /**
     * Determine if a profile is currently involved in a merge operation
     *
     * @param string Log-in identity for the profile
     *
     * @return \Response
     * @responseparam <var>isMergeProcessing</var> {boolean}
     *
     * @category GET
     * @since Sprint 9
     */
    protected function getMergeStatus( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $profileUser = $this->model->getUuid( $userName );
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
            );
        }

        if ( empty( $profileUser ) )
        {
            return new Response( Response::FAIL,
                    NULL,
                    Response::STATUS_NOT_FOUND
            );
        }

        try
        {
            $mergeStatus =
                    $this->model->getMergeStatus( $profileUser[ 'uuid' ] );
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
            );
        }

        return new Response( Response::SUCCESS,
                array ( 'is_merging' => $mergeStatus )
        );
    }



    /**
     * Retrieve security question(s) content
     *
     * @requestparam <var>max_result_count</var> {integer} *(optional)*
     *      Number of questions to return
     *
     * @requestparam <var>exclude_ids</var> {string} *(optional)*
     *      Comma-separated list of question ids to exclude from results
     *
     * @param string Log-in identity for the profile
     * @param mixed Refer to request parameters
     *
     * @return \Response
     *      List of security questions. Refer to response parameters
     *
     * @responseparam <var>id</var> {string}
     * Operational identity for the security question
     * @responseparam <var>question</var> {string}
     * Text content for the question
     *
     * @category GET
     * @since Sprint 1
     */
    protected function getSecurityQuestion( $userName, $request_data )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $questionCount = -1;
        if ( !empty( $request_data[ 'max_result_count' ] ) )
        {
            $questionCount = intval( $request_data[ 'max_result_count' ] );
        }

        $excludeIds = array ();
        if ( !empty( $request_data[ 'exclude_ids' ] ) )
        {
            $excludeIds = explode( ',', $request_data[ 'exclude_ids' ] );
        }

        try
        {
            $questions = $this->model->getSecurityQuestion( $userName,
                    $questionCount, $excludeIds );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() == \core\errorhandling\LdapException::NO_RESULTS )
            {
               \lib\DebugConsole::end();

                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }
            else
            {
               \lib\DebugConsole::end();

                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }
        catch ( \core\errorhandling\LdapException $e )
        {
            if ( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS )
            {
               \lib\DebugConsole::end();

                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }


       \lib\DebugConsole::end();

        if ( empty( $questions ) ) $questions = NULL;

        return new Response( Response::SUCCESS, $questions );
    }


    /**
     * Retrieve the number of security questions
     * associated with a member profile
     *
     * @param string Log-in identity for the profile
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>count</var> {integer}
     *      Number of security questions
     *
     * @category GET
     * @since Sprint 1
     */
    protected function securityQuestionCount( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $questions = $this->model->getSecurityQuestionCount( $userName );
        }
        catch ( \Exception $e )
        {
           \lib\DebugConsole::end();

            if ( $e->getCode() == \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL,
                        NULL, Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS,
                array ( 'count' => $questions ) );
    }

    /**
     * Retrieve basic profile information for a given public profile name
     *
     * @param string Public identity for the profile
     *
     * @return Response List of populated public attributes and values
     * (e.g. displayname, city, state, etc.). The explicit list of publicly
     * available attributes is defined by the operational security policy. Refer
     * to security policy documentation for more information
     *
     * @category GET
     */
    public function index( $publicName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( !empty ( $publicName ) )
        {
           \lib\DebugConsole::log( "Searching for canonical name" );

            $profile = $this->model->getPublicInfo( $publicName );

            if ( count( $profile ) > 0 )
            {
                return new Response( Response::SUCCESS, $profile );
            }
            else
            {
                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }
        }
        else
        {
           \lib\DebugConsole::warn(
                    "Missing parameter: 'canonical name'" );

            return new Response ( Response::FAIL, NULL,
                    Response::STATUS_BAD_REQUEST );
        }
    }


    /**
     * Retrieve the CCS UUID for a member profile
     *
     * @param string Log-in identity for the profile
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>uuid</var> Unique Universal IDentifier
     *
     * @category GET
     */
    protected function getUuid( $username )
    {
       \lib\DebugConsole::stampAction( $this->restler );

       \lib\DebugConsole::end();

        try
        {
            $uuid = $this->model->getUuid( $username );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }

        if ( !empty( $uuid ) )
        {
            return new Response( Response::SUCCESS, $uuid );
        }
        else
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }
    }


    /**
     * Generate a profile-specific hex hash for a given content string
     *
     * @param string Log-in identity for the profile
     *
     * @requestparam <var>seed</var> {string}
     *      Content to use as a base for the hash
     * @requestparam <var>is_encode_output</var> {boolean} [optional]
     *      If TRUE, the hash is returned as a Base64-encoded string
     * @requestparam <var>length</var> {integer} [optional]
     *      If supplied, the returned hash is truncated to the length specified
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>hash</var> {string}
     *      SHA1-based secret hash of the seed value
     *
     * @category GET
     *
     * @todo Add key-based security to this feature
     *
     * @internal
     */
    protected function getSharedHash( $username )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Validate the seed content
         */
        if ( empty( $this->restler->request_data[ 'seed' ] ) )
        {
            return new Response( Response::FAIL,
                    'Required parameter -seed- cannot be empty',
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Validate the profile
         */
        if ( !$this->model->isExists( 'username', $username ) )
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }

        /**
         * Retrieve the hash from the Core model
         */
        try
        {
            $result =
                    $this->model->getHash( $username,
                            $this->restler->request_data[ 'seed' ]
                            );
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL,
                        $e->getMessage() . " (" . $e->getCode() . ")" );
        }

        /**
         * Trim the hash to the requested length
         */
        if ( !empty ( $this->restler->request_data[ 'length' ] )
                AND $this->restler->request_data[ 'length' ] > 0 )
        {
            $result = substr( $result, 0,
                    $this->restler->request_data[ 'length' ] );
        }

        /**
         * If requested, Base64-encode the result
         */
        if ( !empty ( $this->restler->request_data[ 'is_encode_output' ] )
                AND $this->restler->request_data[ 'is_encode_output' ] === 'true'
                OR $this->restler->request_data[ 'is_encode_output' ] === TRUE )
        {
            $result = base64_encode( $result );
        }

        return new Response( Response::SUCCESS, array ( 'hash' => $result ) );
    }


    /**
     * Generate a profile-specific hash for a given content string salted
     * for a given inline application
     *
     * @param string Log-in identity for the profile
     *
     * @requestparam <var>seed</var> {string}
     *      Content to use as a base for the hash
     * @requestparam <var>is_encode_output</var> {boolean} [optional]
     *      If TRUE, the hash is returned as a Base64-encoded string
     * @requestparam <var>length</var> {integer} [optional]
     *      If supplied, the returned hash is truncated to the length specified
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>hash</var> {string}
     *      SHA1-based secret hash of the seed value
     *
     * @category GET
     */
    protected function getHash( $username )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Validate the content seed
         */
        if ( empty( $this->restler->request_data[ 'seed' ] ) )
        {
            return new Response( Response::FAIL,
                    'Required parameter -seed- cannot be empty',
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Validate the profile
         */
        if ( !$this->model->isExists( 'username', $username ) )
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }

        /**
         * Retrieve the hash from the Core model
         */
        try
        {
            $result =
                    $this->model->getHash( $username,
                            $this->restler->request_data[ 'seed' ] .
                            $_SESSION[ 'sourceApplication' ]
                            );
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL,
                        $e->getMessage() . " (" . $e->getCode() . ")" );
        }

        /**
         * Trim the hash to the requested length
         */
        if ( !empty ( $this->restler->request_data[ 'length' ] )
                AND $this->restler->request_data[ 'length' ] > 0 )
        {
            $result = substr( $result, 0,
                    $this->restler->request_data[ 'length' ] );
        }

        /**
         * If requested, Base64-encode the result
         */
        if ( !empty ( $this->restler->request_data[ 'is_encode_output' ] )
                AND $this->restler->request_data[ 'is_encode_output' ] == TRUE )
        {
            $result = base64_encode( $result );
        }

        return new Response( Response::SUCCESS, array ( 'hash' => $result ) );
    }


    /**
     * Retrieve the timestamp that indicates when the profile user
     * acknowledged agreement with the end-user license agreement
     *
     * @param string Log-in identity for the profile
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>timestamp</var> POSIX timestamp for the event
     *
     * @category GET
     */
    protected function getLicenseTimestamp( $username )
    {
       \lib\DebugConsole::stampAction( $this->restler );

       \lib\DebugConsole::end();

        try
        {
            $profileId = $this->model->getUuid( $username );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }

        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }

        try
        {
            $result = $this->model->getLicenseTimestamp( $profileId[ 'uuid' ] );
        }
        catch ( \Exception $e )
        {
            if ( $e->getMessage() == 'UUID not found' )
            {
                return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL,
                        $e->getMessage() . " (" . $e->getCode() . ")" );
            }
        }

        return new Response( Response::SUCCESS, $result);
    }


    /**
     * Remove attribute(s) from a profile
     *
     * @category DELETE
     *
     * @param string Log-in identity for the profile
     * @param string Comma-separated list of attributes to delete
     *
     * @return  Response
     */
    protected function deleteAttributes( $userName, $attributes )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $attributes = explode( ',', $attributes );

        try
        {
            $isSuccess =
                    $this->model->deleteAttributes(
                            $userName,
                            $attributes
                            );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Revoke an application registration for a profile
     *
     * @category DELETE
     *
     * @param string Log-in identity for the profile
     * @param string Unique identifier for the application
     *
     * @return  Response
     */
    protected function deleteApplication( $userName, $appUuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $profileId = $this->model->getUuid( $userName );

            if ( empty( $profileId ) )
            {
                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }

            $isSuccess =
                    $this->model->revokeApplication(
                            $profileId[ 'uuid' ],
                            $appUuid
                            );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Register an application to a profile
     *
     * @category POST
     *
     * @param string Log-in identity for the profile
     * @param string Unique identifier for the application
     *
     * @return  Response
     */
    protected function postApplication( $userName, $appUuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $appUuid ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'uuid' (unique application identifier)",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        try
        {
            $profileId = $this->model->getUuid( $userName );

            if ( empty( $profileId ) )
            {
                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }

            $isSuccess =
                    $this->model->registerApplication(
                            $profileId[ 'uuid' ],
                            $appUuid
                            );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }



    /**
     * Add extended attributes to the publicly displayed information for the
     * profile
     *
     * @category POST
     *
     * @param string Log-in identity for the profile
     * @param string Comma-separated list of attributes to add
     *
     * @return  Response
     */
    protected function postPublicAttributes( $userName, $attributes )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $attributes ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'attributes'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        try
        {
            $profileId = $this->model->getUuid( $userName );

            if ( empty( $profileId ) )
            {
                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }

            $isSuccess =
                    $this->model->addPublicAttributes(
                            $profileId[ 'uuid' ],
                            explode( ',', $attributes )
                            );
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Retrieve the list of extended attributes that are included in the
     * publicly displayed information for the profile
     *
     * @category GET
     *
     * @param string Log-in identity for the profile
     *
     * @return  Response
     */
    protected function getPublicAttributes( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        try
        {
            $profileId = $this->model->getUuid( $userName );

            if ( empty( $profileId ) )
            {
                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }

            $publicAttributes =
                    $this->model->getPublicAttributes( $profileId[ 'uuid' ] );
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS,
                array ( 'public_attributes' => $publicAttributes )

        );
    }


    /**
     * Remove extended attributes from the publicly displayed information for
     * the profile
     *
     * @category DELETE
     *
     * @param string Log-in identity for the profile
     * @param string Comma-separated list of attributes to remove
     *
     * @return  Response
     */
    protected function deletePublicAttributes( $userName, $attributes )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $attributes ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'attributes'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        try
        {
            $profileId = $this->model->getUuid( $userName );

            if ( empty( $profileId ) )
            {
                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }

            $isSuccess =
                    $this->model->deletePublicAttributes(
                            $profileId[ 'uuid' ],
                            explode( ',', $attributes )
                            );
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Issue a request to absorb all attributes and meta-data from another
     * profile and then deactivate the absorbed profile
     *
     * @param string Log-in identity for the profile to absorb (target)
     *
     * @requestbody <var>target_userhash</var> {string}
     *      Base64-encoded password for the target profile
     *
     *
	 * @class AuthMember(isAuthRequired=TRUE)
     */
    protected function putRequestMerge( $targetUserName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Validate submitted credentials for the target user
         */
        if ( empty( $targetUserName ) )
        {
            return new Response( Response::FAIL,
                    "Target username cannot be empty",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $this->restler->request_data[ 'target_userhash' ] ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter - target's userhash",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        $target = $this->model->getUuid( $targetUserName );
        if ( empty( $target ) )
        {
            return new Response( Response::FAIL,
                    'Target user not found',
                    Response::STATUS_NOT_FOUND
                    );
        }

        $targetHash =
                base64_decode( $this->restler->request_data[ 'target_userhash' ] );

        /**
         * Validate that the requestor has authentic credentials for the target
         * profile
         */
        if ( !$this->model->isValidCredentials( $targetUserName, $targetHash ) )
        {
            return new Response( Response::FAIL,
                    'Invalid credentials for target profile',
                    Response::STATUS_UNAUTHORIZED
                    );
        }

        /**
         * Validate source profile information
         */
        if ( empty( $_SESSION[ 'user' ] )
                OR empty( $_SESSION[ 'user' ][ 'username' ] ) )
        {
            return new Response( Response::FAIL,
                    'Source username not found',
                    Response::STATUS_NOT_FOUND
                    );
        }

        $sourceUserName = $_SESSION[ 'user' ][ 'username' ];

        if ( $sourceUserName == $targetUserName )
        {
            return new Response(
                    Response::FAIL,
                    "Source and target profiles cannot be identical",
                    Response::STATUS_BAD_REQUEST
            );
        }


        $source = $this->model->getUuid( $sourceUserName );
        if ( empty( $source ) )
        {
            return new Response( Response::FAIL,
                    'Source user not found',
                    Response::STATUS_NOT_FOUND
                    );
        }

        /**
         * Submit a request to merge the profiles via service message bus
         */
        try
        {
            $this->model->requestMerge( $source[ 'uuid' ], $target[ 'uuid' ] );
        }
        catch( \InvalidArgumentException $e )
        {
            return new Response(
                    Response::FAIL,
                    $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
            );
        }

        return new Response( Response::SUCCESS );
    }


    /**
     * Transmit an operational message to the profile via various transports
     * (email, SMS, in-application, etc)
     * <p>It's assumed that the content of the notification has already been
     * translated to the profile user's preferred language
     *
     * @param string Log-in identity for the profile
     *
     * @requestbody <var>subject</var> {string}
     * @requestbody <var>body</var>
     * @requestbody <var>transport</var> {string}
     * <p>NOTE: Only the email transport is currently implemented
     *
     * @since Sprint 7
     */
    protected function postNotify( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $requiredParameters = array ( 'subject', 'body' );
        $missingParameters = array ();
        foreach( $requiredParameters as $parameter )
        {
            if ( empty( $this->restler->request_data[ $parameter ] ) )
            {
                $missingParameters[] = $parameter;
            }
        }

        if ( count( $missingParameters ) > 0 )
        {
            $missingParameters = implode( ', ', $missingParameters );
            return new Response( Response::FAIL,
                    "Missing required parameter(s): $missingParameters",
                    Response::STATUS_BAD_REQUEST );
        }

        $transport = NULL;
        /**
         * Determine the delivery method for the notification
         */
        if ( !empty( $this->restler->request_data[ 'transport' ] ) )
        {
            $transport = $this->restler->request_data[ 'transport' ];
        }

        /**
         * Execute the delivery of the message
         */
        switch ( $transport )
        {
            case 'mail':
            case 'email':
            default:
                $subject = $this->restler->request_data[ 'subject' ];
                $body = $this->restler->request_data[ 'body' ];

                $userProfile = $this->model->get( 'username', $userName );

                if ( empty( $userProfile[ 'email' ] ) )
                {
                    return new Response( Response::FAIL,
                            NULL,
                            Response::STATUS_NOT_FOUND
                    );
                }

                $address = $userProfile[ 'email' ];

                try
                {
                    $this->queueEmail( $address, $subject, $body );
                }
                catch ( \InvalidArgumentException $e )
                {
                    return new Response( Response::FAIL,
                            $e->getMessage(),
                            Response::STATUS_BAD_REQUEST
                    );
                }
        }

        return new Response( Response::SUCCESS );
    }


    /**
     * Submit a request to send an email via the message bus
     *
     * @param string Email address of the receiving party
     * @param string Email subject for the message
     * @param string Main content block for the message
     * @throws InvalidArgumentException
     */
    private function queueEmail( $recipient, $subject, $body )
    {
        /**
         * Validate input
         */
        $payload = array
        (
            'recipient'     => $recipient,
            'subject'       => $subject,
            'body'          => $body,
        );

        $inputErrors = array ();
        foreach( $payload as $key => $value )
        {
            if ( empty( $value ) )
            {
                $inputErrors[] = $key;
            }
        }

        if ( count( $inputErrors ) > 0 )
        {
            $inputErrors = implode( ', ', $inputErrors );
            throw new InvalidArgumentException(
                    "Parameter(s) [$inputErrors] cannot be empty"
            );
        }

        $payload = json_encode( $payload );

        $mq = new \core\lib\connectors\mqPublish();
        $mq->setExchange( \core\models\Profile::MAIL_BUS );
        $mq->send( $payload );
    }


    /**
     * Set the profile user's progress towards an application achievement
     *
     * @category PUT
     *
     * @param string Log-in identity for the profile
     * @param string Unique identifier for the achievement
     * @param integer Percentage of goals completed towards the achievement
     *
     * @return  Response
     */
    protected function putAchievement(
            $userName, $achievementUuid, $progressPercent
            )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        $profileId = $this->model->getUuid( $userName );
        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL,
                    'username=' . $userName,
                    Response::STATUS_NOT_FOUND
                    );
        }

        if ( empty( $achievementUuid ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'uuid' (unique achievement identifier)",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Link the achievement to the profile
         */
        try
        {
            $isSuccess =
                    $this->model->updateAchievement(
                            $profileId[ 'uuid' ],
                            $achievementUuid,
                            intval( $progressPercent )
                            );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(),
                    $e->getCode()
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Add a badge to a user profile
     *
     * @category POST
     *
     * @param string Log-in identity for the profile
     * @param string Unique identifier for the badge
     *
     * @requestparam <var>expires_timestamp</var> {integer}
     *      POSIX timestamp for date/time after which the badge is no longer
     *      displayed. The submitted value must occur in the future
     *
     * @return  Response
     */
    protected function postBadge(
            $userName,
            $badgeUuid
            )
    {
       \lib\DebugConsole::stampAction( $this->restler );

       /**
        * Validate the profile
        */
        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Validate the badge
         */
        if ( empty( $badgeUuid ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'uuid' (unique badge identifier)",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Validate the expiration timestamp
         */
        $expiresTimestamp = NULL;
        if ( !empty( $this->restler->request_data[ 'expires_timestamp' ] ) )
        {
            if ( !filter_var(
                    $this->restler->request_data[ 'expires_timestamp' ],
                    FILTER_VALIDATE_INT
                    )
            )
            {
                return new Response( Response::FAIL,
                        "Request parameter -expires_timestamp- must be an integer",
                        Response::STATUS_BAD_REQUEST
                        );
            }

            $expiresTimestamp =
                    intval( $this->restler->request_data[ 'expires_timestamp' ] );
        }

        /**
         * Link the badge to the profile
         */
        $profileId = $this->model->getUuid( $userName );
        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL,
                    'username=' . $userName,
                    Response::STATUS_NOT_FOUND
                    );
        }

        try
        {
            $isSuccess =
                    $this->model->addBadge(
                            $profileId[ 'uuid' ],
                            $badgeUuid,
                            $expiresTimestamp
                            );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            {
                return new Response( Response::FAIL,
                        $e->getCode() . ' - ' . $e->getMessage(),
                        Response::STATUS_BAD_REQUEST
                        );
            }
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL, $e->getMessage(), $e->getCode() );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Remove a badge from a user profile
     *
     * @category DELETE
     *
     * @param string Log-in identity for the profile
     * @param string Unique identifier for the badge
     *
     * @return  Response
     */
    protected function deleteBadge(
            $userName,
            $badgeUuid
            )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $badgeUuid ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'uuid' (unique badge identifier)",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        $profileId = $this->model->getUuid( $userName );
        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL,
                    'username \'' . $userName . '\'',
                    Response::STATUS_NOT_FOUND
                    );
        }

        try
        {
            $isSuccess =
                    $this->model->deleteBadge(
                            $profileId[ 'uuid' ],
                            $badgeUuid
                            );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(),
                    $e->getCode()
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


/**
     * Remove an achievement from a user profile
     *
     * @category DELETE
     *
     * @param string Log-in identity for the profile
     * @param string Unique identifier for the achievement
     *
     * @return  Response
     */
    protected function deleteAchievement(
            $userName,
            $achievementUuid
            )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $achievementUuid ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'uuid' (unique achievement identifier)",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        $profileId = $this->model->getUuid( $userName );
        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL,
                    'username \'' . $userName . '\'',
                    Response::STATUS_NOT_FOUND
                    );
        }

        try
        {
            $isSuccess =
                    $this->model->deleteAchievement(
                            $profileId[ 'uuid' ],
                            $achievementUuid
                            );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(),
                    $e->getCode()
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Set application-specific attributes for the profile
     *
     * @category PUT
     *
     * @param string Log-in identity for the profile
     * @requestparam <var>attributes</var> {array}
     *      List of key/value pairs to store for the profile
     *
     * @return  Response
     */
    protected function putApplication( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty ( $_SESSION[ 'sourceApplication' ] ) )
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_FORBIDDEN );
        }

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $this->restler->request_data[ 'attributes' ] ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'attributes'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        try
        {
            $profile = $this->model->getUuid( $userName );
            $isStored =
                    $this->model->setApplicationAttributes( $profile[ 'uuid' ],
                        $_SESSION[ 'sourceApplication' ],
                        $this->restler->request_data[ 'attributes' ]
                        );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( $isStored );
    }


    /**
     * Get application-specific attributes for the profile
     *
     * @category GET
     *
     * @param string Log-in identity for the profile
     * @param string [optional]
     *      Unique identity for the application. This parameter only needs to be
     *      supplied when the intention is to retrieve attributes specific to
     *      an application other than the identity generating the request
     *
     * @requestparam <var>attributes</var> {array} [optional]
     *      List of key/value pairs to retrieve. If not supplied, all available
     *      attributes are retrieved
     *
     * @return  Response
     */
    protected function getApplication( $userName, $applicationId )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'username'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $applicationId ) )
        {
            if ( !empty ( $_SESSION[ 'sourceApplication' ] ) )
            {
                $applicationId = $_SESSION[ 'sourceApplication' ];
            }
            else
            {
                return new Response( Response::FAIL,
                        NULL,
                        Response::STATUS_FORBIDDEN
                        );
            }
        }

        $isSharedOnly = TRUE;
        if ( $applicationId == $_SESSION[ 'sourceApplication' ] )
        {
            $isSharedOnly = FALSE;
        }

        $attributes = array ();
        if ( !empty( $this->restler->request_data[ 'attributes' ] ) )
        {
            $attributes = $this->restler->request_data[ 'attributes' ];
        }

        try
        {
            $profile = $this->model->getUuid( $userName );
            $results =
                    $this->model->getApplicationAttributes( $profile[ 'uuid' ],
                        $applicationId,
                        $attributes,
                        $isSharedOnly
                        );
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
                        $e->getCode() . ' - ' . $e->getMessage()
                        );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getCode() . ' - ' . $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $results );
    }


    /**
     * Retrieve the list of applications that the profile user
     * has chosen to register
     *
     * @param string Log-in identity for the profile
     * @param string Unique identity for an application (optional)
     *
     * @requestparam <var>minimum_progress</var> {integer}
     *      Filter results to achievements with a progress percentage equal to
     *      or greater than the specified value
     * @requestparam <var>maximum_progress</var> {integer}
     *      Filter results to achievements with a progress percentage equal to
     *      or less than the specified value
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>uuid</var> {string}
     *      Unique identifier for the achievement
     * @responseparam <var>name</var> {string}
     *      Friendly name for the application (en-us)
     * @responseparam <var>progress_percentage</var> {integer}
     *      The percentage of goal(s) completed towards the achievement
     *      (100 = successfully completed achievement)
     * @responseparam <var>logo</var> {string}
     *      Global resource path for the achievement badge image
     * @responseparam <var>application_uuid</var> {string}
     *      Unique identity for the parent application associated with the
     *      achievement. This parameter is only returned when
     *      <var>applicationId</var> is not supplied
     *
     * @category GET
     */
    protected function getAchievements( $username, $applicationId = NULL )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $profileId = $this->model->getUuid( $username );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }

        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }

        $minimumProgress = 0;
        if ( !empty( $this->restler->request_data[ 'minimum_progress' ] ) )
        {
            if ( !filter_var( $this->restler->request_data[ 'minimum_progress' ],
                    FILTER_VALIDATE_INT
                    ) )
            {
                return new Response( Response::FAIL,
                        'Parameter -minimum_progress- must be an integer',
                        Response::STATUS_BAD_REQUEST
                        );
            }
            $minimumProgress =
                    intval( $this->restler->request_data[ 'minimum_progress' ] );
        }

        $maximumProgress = 100;
        if ( !empty( $this->restler->request_data[ 'maximum_progress' ] ) )
        {
            if ( !filter_var( $this->restler->request_data[ 'maximum_progress' ],
                    FILTER_VALIDATE_INT
                    ) )
            {
                return new Response( Response::FAIL,
                        'Parameter -maximum_progress- must be an integer',
                        Response::STATUS_BAD_REQUEST
                        );
            }
            $maximumProgress =
                    intval( $this->restler->request_data[ 'maximum_progress' ] );
        }

        try
        {
            $apps = $this->model->getAchievements(
                        $profileId[ 'uuid' ],
                        $applicationId,
                        $minimumProgress,
                        $maximumProgress
                        );
        }
        catch ( \Exception $e )
        {
            if ( stristr( $e->getMessage(), 'must be an integer' ) )
            {
                return new Response( Response::FAIL,
                    $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
            }

            if ( $e->getMessage() == 'UUID not found' )
            {
                return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL,
                        $e->getMessage() . " (" . $e->getCode() . ")" );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $apps );
    }


/**
     * Retrieve the list of badges that the profile user has acquired
     *
     * @param string Log-in identity for the profile
     * @param string Unique identity for an application (optional)
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>uuid</var> {string}
     *      Unique identifier for the badge
     * @responseparam <var>name</var> {string}
     *      Friendly name for the application (en-us)
     * @responseparam <var>logo</var> {string}
     *      Global resource path for the badge image
     * @responseparam <var>application_uuid</var> {string}
     *      Unique identity for the parent application associated with the
     *      achievement. This parameter is only returned when
     *      <var>applicationId</var> is not supplied
     *
     * @category GET
     */
    protected function getBadges( $username, $applicationId = NULL )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $profileId = $this->model->getUuid( $username );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }

        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }

        try
        {
            $badges = $this->model->getBadges(
                        $profileId[ 'uuid' ],
                        $applicationId
                        );
        }
        catch ( \Exception $e )
        {
            if ( $e->getMessage() == 'UUID not found' )
            {
                return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL,
                        $e->getMessage() . " (" . $e->getCode() . ")" );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
                    );
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $badges );
    }


    /**
     * Retrieve the list of applications that the profile user
     * has chosen to register
     *
     * @param string Log-in identity for the profile
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>uuid</var>
     *      Unique identifier for the application
     * @responseparam <var>name</var>
     *      Common English name for the application
     * @responseparam <var>logo</var>
     *      Global resource path for the application logo badge
     * @responseparam <var>launch_uri</var>
     *      Global resource path for the application home page
     *
     * @category GET
     */
    protected function getApplications( $username )
    {
       \lib\DebugConsole::stampAction( $this->restler );

       \lib\DebugConsole::end();

        try
        {
            $profileId = $this->model->getUuid( $username );
        }
        catch ( \Exception $e )
        {
            if ( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, $e->getMessage(),
                        $e->getCode() );
            }
        }

        if ( empty( $profileId ) )
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }

        try
        {
            $apps = $this->model->getApplications( $profileId[ 'uuid' ] );
        }
        catch ( \Exception $e )
        {
            if ( $e->getMessage() == 'UUID not found' )
            {
                return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL,
                        $e->getMessage() . " (" . $e->getCode() . ")" );
            }
        }

        return new Response( Response::SUCCESS, $apps );
    }


    /**
     * Determine if a profile is a member of a specific group
     *
     * @param string Log-in identity for the profile
     * @param string Common name of the profile group
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>is_member</var> {boolean}
     *      Returns TRUE if the profile is a member of the specified group
     *
     * @category GET
     */
    protected function getGroup( $userName, $groupName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $userName ) )
        {
            return new Response( Response::FAIL,
                    'Parameter -userName- cannot be empty',
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $groupName ) )
        {
            return new Response( Response::FAIL,
                    'Parameter -groupName- cannot be empty',
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( !$this->model->isExists( 'username', $userName ) )
        {
            return new Response( Response::FAIL,
                    NULL,
                    Response::STATUS_NOT_FOUND
                    );
        }

        try
        {
            $result = $this->model->isMemberOfGroup( $userName, $groupName );
        }
        catch ( \Exception $e )
        {
            return new Response( Response::FAIL,
                        $e->getMessage() . " (" . $e->getCode() . ")" );
        }

        return new Response( Response::SUCCESS,
                array ( 'is_member' => $result )
                );


       \lib\DebugConsole::end();
    }
    
    /**
     * Add user to specific group.
     * @param type $userName
     * @param type $groupName
     * @return \Response
     * @responseparam <var>is_member</var> {boolean}
     *      Returns TRUE if the profile is a member of the specified group
     * @category POST
     */
    protected function postGroup($userName, $groupName) {

        \lib\DebugConsole::stampAction($this->restler);

        if (empty($userName)) {
            return new Response(Response::FAIL, 
                    'Parameter -userName- cannot be empty', 
                    Response::STATUS_BAD_REQUEST
            );
        }

        if (empty($groupName)) {
            return new Response(Response::FAIL, 
                    'Parameter -groupName- cannot be empty',
                    Response::STATUS_BAD_REQUEST
            );
        }

        if (!$this->model->isExists('username', $userName)) {
            return new Response(Response::FAIL, 
                    NULL, 
                    Response::STATUS_NOT_FOUND
            );
        }

        try {
            $result = $this->model->addToGroup($userName, $groupName);
        } catch (\core\errorhandling\LdapException $e) {
            if ($e->getCode() ==
                    \core\errorhandling\LdapException::TYPE_VALUE_EXISTS) {
                return new Response(Response::FAIL, 
                        NULL, 
                        Response::STATUS_NOT_FOUND);
            } else {
                return new Response(Response::FAIL, 
                        $e->getCode() . ' - ' . $e->getMessage()
                );
            }
        } catch (\InvalidArgumentException $e) {
            return new Response(Response::FAIL, 
                    $e->getCode() . ' - ' . $e->getMessage(), 
                    Response::STATUS_BAD_REQUEST
            );
        }

        \lib\DebugConsole::end();

        return new Response(Response::SUCCESS, 
                array('is_member' => $result)
        );
    }


    /**
     * Update the log-in password for a member profile
     *
     * @requestbody <var>userhash</var> {string}
     *      New password for the profile
     *
     *      <p>**NOTE:** Value must be base64-encoded
     *
     *      Refer to {@link Profile::postValidatePassword} for password strength
     *      criteria
     *
     * @param string Log-in identity for the profile
     *
     * @return Response
     *
     * @category PUT
     */
    protected function putPassword( $username )
    {
       \lib\DebugConsole::stampAction( $this->restler );

       \lib\DebugConsole::end();

		/**
         * Deny access if any hash is passed via URI
         */
        if ( stristr( $_SERVER[ 'REQUEST_URI' ], 'hash=' ) )
		{
           \lib\DebugConsole::warn( "Submitting hashes via URI not permitted" );
           \lib\DebugConsole::end();

            return new Response( Response::FAIL,
                    'Submitting hashes via URI not permitted',
                    Response::STATUS_BAD_REQUEST
                    );
        }

        $requestData = array_change_key_case( $this->restler->request_data );

		$userhash = $requestData[ 'userhash' ];

        $isSuccess = FALSE;
		try
        {
            $isSuccess = $this->model->setPassword( $username, $userhash );
        }
        catch ( \core\errorhandling\LdapException $e )
        {
            if ( $e->getCode() == \core\errorhandling\LdapException::NO_RESULTS )
            {
                return new Response( Response::FAIL, NULL,
                        Response::STATUS_NOT_FOUND );
            }
            else
            {
                return new Response( Response::FAIL,
                        $e->getCode() . ' - ' . $e->getMessage(),
                        Response::STATUS_ERROR );
            }
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response(
                    Response::FAIL, $e->getMessage(),
                    Response::STATUS_BAD_REQUEST
            );
        }

        return new Response( $isSuccess, array () );
    }


    /**
     * Retrieve all listed information for a profile based on an attribute
     *
     * @category GET
     *
     * @param string Name of the attribute field to search
     *
     * Acceptable values: [username, email, uuid]
     *
     * **NOTE:** In order to query 'uuid', use the true ldap attribute name
     * 'nsuniqueid' or use query parameters
     * (e.g. attribute=uuid&query=g12322...)to avoid conflict with the
     * profile::uuid action
     *
     * @param string Value to match for specified attribute
     *
     * @requestparam <var>is_public_only</var> {boolean}
     *      If TRUE, the information returned is restricted to the
     * list of attributes designated as -public- for the profile
     *
     * @return Response List of all populated attributes for the profile
     */
    protected function get( $attribute, $query )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $attribute = strtolower( $attribute );

        $attributeActions =
			array ( 'username', 'uid', 'email', 'mail', 'nsuniqueid', 'uuid' );

        if ( !in_array( $attribute, $attributeActions ) )
        {
           \lib\DebugConsole::warn( "Invalid attribute = $attribute" );
           \lib\DebugConsole::end();

            return new Response ( Response::FAIL, NULL,
                    Response::STATUS_BAD_REQUEST );
        }

        $isPublicOnly = FALSE;
        if ( !empty( $this->restler->request_data[ 'is_public_only' ] ) AND
        \RequestHelper::isTruthy( $this->restler->request_data[ 'is_public_only' ] ) )
        {
            $isPublicOnly = TRUE;
        }

        try
        {
            $profile = $this->model->get( $attribute, $query, $isPublicOnly );
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

        if ( count( $profile ) > 0 )
        {
            return new Response( Response::SUCCESS, $profile );
        }
        else
        {
            return new Response( Response::FAIL, NULL,
                    Response::STATUS_NOT_FOUND );
        }
    }
    
    /**
     * Retrieve all listed user profiles for a given specific application.
     * 
     * @category  GET
     * 
     * @param type $applicationUuid  UUID of the application
     * @param type $page    Page number
     * @return Response List of user profiles limit 10 per response.
     */
    protected function getUsers( $applicationUuid, $page ) {

        $limit = 10;
        \lib\DebugConsole::stampAction($this->restler);

        /**
         * Check if application exists
         */
        $applicationObj = new core\models\Application();
        
        $appExists = $applicationObj->isExists($applicationUuid);
        
        if($appExists == FALSE){
            return new Response( Response::FAIL,
                    'Incorrect application uuid',
                    Response::STATUS_NOT_FOUND
                    );
        }
       
        
        /**
         *  Number of rows
         */
        try {
            $numberOfRows = $this->model->getProfileCount($applicationUuid);
        } catch ( \Exception $e ) {
            if( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS ) {
                \lib\DebugConsole::end();

                return new Response(Response::FAIL, $e->getMessage(),
                        $e->getCode());
            }
        }

        /**
         * get all user profile id given appplicaiton uuid
         */
        try {
            $profileIds = $this->model->getProfileIds($applicationUuid, $page);
        } catch ( \Exception $e ) {
            if( $e->getCode() != \core\errorhandling\LdapException::NO_RESULTS ) {
                \lib\DebugConsole::end();

                return new Response(Response::FAIL, $e->getMessage(),
                        $e->getCode());
            }
        }
        
        
        $result = new stdClass();
        /**
         * for each ldap uuid get the profile info
         */
        foreach( $profileIds as $key => $value ) {
            //convert the profile id to ldap uuid
            $userUuid = $this->_hyphenate($value['profile_id']);
            $profile = $this->model->get('nsuniqueid', $userUuid);

            $result->$key = $profile;
        }
        
        /**
         * assembling pagination
         */
        $link = "https://" . $_SERVER['SERVER_NAME'] . $_SERVER['PATH_INFO'];
        $link = rtrim($link, $page);

        /**
         * Getting number of pages / Pagination
         */
        $numberOfPages = ceil($numberOfRows / $limit);
        $prev = $page - 1;
        $next = $page + 1;
        $pagination = array(
            'first' => 1,
            'last' => $numberOfPages,
            'previous' => $link . $prev,
            'next' => $link . $next );
        

        if( $page == 1 ) {
            unset($pagination['previous']);
        }
        if( $page == $numberOfPages ) {
            unset($pagination['next']);
        }
        if( $numberOfPages < $page ) {
            unset($pagination);
        }
      



        \lib\DebugConsole::end();
        return new Response(Response::SUCCESS, $result,Response::STATUS_OK, $pagination);

    }

    /**
     * upper cases and hyphenates the uuid from mysql profileid to 
     * check against ldap
     * @param type $str
     * @return type
     */
    private function _hyphenate( $str ) {
        return strtoupper(implode("-", str_split($str, 8)));
    }


    /**
     * Create a new member profile
     *
     * @requestbody <var>username</var> {string} Log-in identity
     * @requestbody <var>firstname</var> {string} Given name
     * @requestbody <var>lastname</var> {string} Sirname
     * @requestbody <var>email</var> {string} Primary email address
     * @requestbody <var>password</var> {string} Secret authentication phrase
     * @requestbody <var>country</var> {string} Secret authentication phrase
     *
     * <p>**NOTE:** Currently, password is a required field that must be
     * submitted as a base64-encoded string. In upcoming iterations,
     * the requirement will be that the password must be an
     * AES-encrypted payload with an HMAC checksum (details TBD).
     *
     * Refer to {@link Profile::postValidatePassword} for password strength
     * criteria
     *
     * @requestbody <var>country</var> {string} Home country
     *
     * <p>**NOTE:** Submitted value may only contain a valid
     * ISO 3166-1 alpha-2 country code
     *
     * @requestbody <var>alternate_email</var> {string} *(optional)*
     *      Alternate email address
     * @requestbody <var>displayname</var> {string} *(optional)*
     *      Preferred name
     * @requestbody <var>initials</var> {string} *(optional)*
     *      Abbreviated name
     * @requestbody <var>streetaddress</var> {string} *(optional)*
     *      Home physical address
     * @requestbody <var>city</var> {string} *(optional)*
     *      Home location
     * @requestbody <var>state</var> {string} *(optional)*
     *      Home region (e.g. state/province)
     * @requestbody <var>postalcode</var> {string} *(optional)*
     *      Home postal/zip code
     * @requestbody <var>phone</var> {string} *(optional)*
     *      Telephone number
     * <p>**NOTE:** Submitted value may only contain numeric digits [0-9]
     * @requestbody <var>mobile</var> {string} *(optional)*
     *      Cellular telephone number
     * <p>**NOTE:** Submitted value may only contain numeric digits [0-9]
     * @requestbody <var>alternate_phone</var> {string} *(optional)*
     *      Telephone number
     * <p>**NOTE:** Submitted value may only contain numeric digits [0-9]
     * @requestbody <var>preferredlanguage</var> {string} (optional)
     *	    Language/locale identifier
     * @requestbody <var>license_agreement</var> {boolean} (optional)
     *	    Indicates that the user has acknowledged the end-user license
     *      agreement
     * @requestbody <var>license_language</var> {string} (optional)
     *      Indicates the human language in which the license agreement was
     *      displayed
     * @requestbody <var>birthdate</var> {string} (optional, extended attribute)
     *      ISO 8601-formatted birthday for the profile user.
     *      Per ISO 8601, value may be submitted with or without dashes
     * @requestbody <var>occupation</var> {string}
     *      (optional, extended attribute)
     *      Generic label or title for the profile user's current employment.
     * <p>**NOTE:** Maximum length for submitted values is 200 characters.
     * @requestbody <var>gender</var> {string} (optional, extended attribute)
     *      Indicates the sex of the profile user.
     * @requestbody <var>employer_name</var> {string}
     *      (optional, extended attribute)
     *      Profile user's current company name
     * @requestbody <var>employer_address</var> {string}
     *      (optional, extended attribute)
     *      Current employer's physical street address
     * @requestbody <var>employer_city</var> {string}
     *      (optional, extended attribute)
     *      Name of the city where profile user is currently employed
     * @requestbody <var>employer_region</var> {string}
     *      (optional, extended attribute)
     *      Current employer's state/province
     * @requestbody <var>employer_postal_code</var> {string}
     *      (optional, extended attribute)
     *      Zip/postal code for current employer's location
     * @requestbody <var>employer_location_id</var> {string}
     *      (optional, extended attribute)
     *      Internal store number or location identifier for current employment
     *      location
     * @requestbody <var>accounts_payable_address</var> {string}
     *      (optional, extended attribute, dependent)
     *      Email address by which the profile user can receive monetary
     *      payments through an online payment service, such as PayPal
     * <p>**NOTE:** Depends on <i>accounts_payable_service</i>.
     * Modifications to this attribute will be rejected unless all dependent
     * attributes are included
     * @requestbody <var>accounts_payable_service</var> {string}
     *      (optional, extended attribute, dependent)
     *      Unique identifier for the online payment service related to
     *      <i>accounts_payable_address</i>. Refer to the
     *      Refer to {@link AccountsPayable::getServices()} for retrieving
     *      payment service identifiers
     * <p>**NOTE:** Depends on <i>accounts_payable_address</i>.
     * Modifications to this attribute will be rejected unless all dependent
     * attributes are included
     * @requestbody <var>public_attributes</var> {string}
     *      Comma-separated list of extended attributes that may be listed in
     *      the public view of the profile
     *
     * @return Response Operational attributes for profile. Refer to response
     * parameters
     *
     * @responseparam publicname {string} Publicly-accessible identifier
     * @responseparam uuid {string} Unique identifier
     * @responseparam is_password_set {boolean} Indicates whether or not the
     * submitted password was successfully stored
     * @responseparam is_license_agreement {boolean} Indicates whether or not
     * the license agreement timestamp was successfully stored
     *
     * @category POST
     */
    protected function post()
    {
       \lib\DebugConsole::stampAction( $this->restler );

       \lib\DebugConsole::log( $_REQUEST, "REQUESTOBJECT" );

        $inputParameters = array_change_key_case( $this->restler->request_data );

       \lib\DebugConsole::log( $inputParameters, "Submitted User" );

		/**
         * Deny access if any hash is passed via URI
         */
        if ( stristr( $_SERVER[ 'REQUEST_URI' ], 'hash=' ) )
		{
           \lib\DebugConsole::warn( "Submitting hashes via URI not permitted" );
           \lib\DebugConsole::end();

            return new Response( Response::FAIL,
                    'Submitting hashes via URI not permitted',
                    Response::STATUS_BAD_REQUEST
                    );
        }

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
     * @category PUT
     *
     * @param string Log-in identity for the profile
     * @param string Globally unique identity for the profile
     *
     * @return  Response
     */
    protected function putUndelete( $userName, $uuid )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $isSuccess = $this->model->unDelete( $userName, $uuid );
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
     * @category PUT
     *
     * @param string Log-in identity for the profile
     * @param mixed Refer to Profile::post() for attribute list
     * <p>**NOTE:** Including the 'public_attributes' request parameter
     * will replace the existing list of public attributes.
     * <p>Example:
     * <p>Assuming the existing value for
     * public_attributes = 'occupation,gender', submitting 'gender,birthdate'
     * will result in the stored value for public_attributes = 'gender,birthdate'
     *
     * @return  Response
     */
    protected function put( $userName, $request_data )
    {
       \lib\DebugConsole::stampAction( $this->restler );

		/**
         * Deny access if any hash is passed via URI
         */
        if ( stristr( $_SERVER[ 'REQUEST_URI' ], 'hash=' ) )
		{
           \lib\DebugConsole::warn( "Submitting hashes via URI not permitted" );
           \lib\DebugConsole::end();

            return new Response( Response::FAIL,
                    'Submitting hashes via URI not permitted',
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Update the profile
         */
        try
        {
            $isSuccess =
                    $this->model->update( $userName, $request_data );
        }
        catch ( \Exception $e )
        {
           \lib\DebugConsole::end();

            return new Response( Response::FAIL, $e->getMessage(),
                    $e->getCode() );
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

       \lib\DebugConsole::stampBoolean( $isSuccess );
       \lib\DebugConsole::end();

        return new Response( $isSuccess, array () );
    }


    /**
     * Permanently remove a security question associated with a member profile
     *
     * @category DELETE
     *
     * @param string The log-in identity for the member profile
     * @param string The unique identity for the security question
     *
     * @return \Response
     *
     * @since Sprint 1
     */
    protected function deleteSecurityQuestion( $userName, $questionId )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        try
        {
            $isSuccess = $this->model->deleteSecurityQuestion( $userName,
                    $questionId );
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
        catch ( \core\errorhandling\LdapException $e )
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


    /**
     * Remove a member profile
     *
     * @category DELETE
     *
     * @param string Log-in identity for the profile
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


    /**
     * Permanently delete a member profile
     *
     * @category DELETE
     *
     * @param string Log-in identity for the profile
     *
     * @return  Response
     *
     * @throws \core\errorhandling\LdapException
     * @throws \Exception

     * @class AuthApiGroup(groupRequired=admin)
     */
    protected function deleteDestroy( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $isDeleted = FALSE;

        try
        {
            $isDeleted = $this->model->delete( $userName );
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

        if ( !$isDeleted )
        {
            return new Response( $isDeleted );
        }

        $isSuccess = FALSE;

        try
        {
            $isSuccess = \core\lib\connectors\Ldap::deleteEntry( $entry,
                            \core\lib\connectors\Ldap::ROLE_PRIVILEGED
                            );
        }
        catch ( \core\errorhandling\LdapException $e )
        {
            return new Response( Response::FAIL, $e->getMessage() );
        }

       \lib\DebugConsole::end();

        return new Response( $isSuccess );
    }


    /**
     * Create a security question for providing an alternative method
     * of authentication
     *
     * @requestbody <var>question</var> {string}
     *      Textual content for the question to display
     * @requestbody <var>answerhash</var> {string}
     *      Base64-encoded answer content (case-insensitive)
     *
     * @category POST
     *
     * @param string Log-in identity for the member profile
     * @param mixed Refer to Request Body
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam id {string}
     * Unique identifier for the security question
     *
     * @since Sprint 1
     */
    protected function postSecurityQuestion( $userName )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $inputParameters = array_change_key_case( $this->restler->request_data );

       \lib\DebugConsole::log( $inputParameters, "Submitted Question" );

		/**
         * Deny access if any hash is passed via URI
         */
        if ( stristr( $_SERVER[ 'REQUEST_URI' ], 'hash=' ) )
		{
           \lib\DebugConsole::warn( "Submitting hashes via URI not permitted" );
           \lib\DebugConsole::end();

            return new Response( Response::FAIL,
                    'Submitting hashes via URI not permitted',
                    Response::STATUS_BAD_REQUEST
                    );
        }

        $permittedAttributes = array
        (
            'question'      => 1,
            'answerhash'    => 1,
        );

		$inputParameters =
				array_intersect_key( $inputParameters, $permittedAttributes );

		try
        {
            $newQuestion = $this->model->createSecurityQuestion(
                    $userName,
                    $inputParameters[ 'question' ],
                    $inputParameters[ 'answerhash' ]
            );
        }
        catch ( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL,
                    $e->getMessage(), Response::STATUS_BAD_REQUEST );
        }
        catch ( LdapException $e )
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

        return new Response( Response::SUCCESS, $newQuestion,
                Response::STATUS_CREATED );
    }
}