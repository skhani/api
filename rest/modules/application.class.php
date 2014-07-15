<?php
/**
 * Application class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * Child Application Manager
 *
 * @package DeniZEN_Models
 * @since Sprint 5
 */
class Application
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
            $this->model = new core\models\Application();
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
     * Retrieve the universal unique identifier (UUID) for an application
     *
     * @queryparam <var>application_name</var> {string}
     *      Friendly name for the application
     *
     * @return \Response
     */
    protected function getUuid()
    {
        \lib\DebugConsole::stampAction();

        $this->restler->request_data =
                array_change_key_case( $this->restler->request_data );

        if ( empty( $this->restler->request_data[ 'application_name' ] ) )
        {
            return new Response(
                    Response::FAIL,
                    'Missing or empty parameter - application_name',
                    Response::STATUS_BAD_REQUEST
                    );
        }

        try
        {
            $app = $this->model->getUuid(
                    $this->restler->request_data[ 'application_name' ]
                    );
        }
        catch( \InvalidArgumentException $e )
        {
            return new Response(
                    Response::FAIL,
                    $e->getMessage(),
                    $e->getCode()
                    );
        }
        catch( \Exception $e )
        {
            return new Response(
                    Response::FAIL,
                    $e->getMessage(),
                    $e->getCode()
                    );
        }

        return new Response( Response::SUCCESS, $app );

        \lib\DebugConsole::end();
    }


    /**
     * Retrieve the list of profile merge requests
     *
     * @param string Unique identifier for the application
     *
     * @return \Response Refer to response parameters
     *
     * @responseparam <var>source</var> {string}
     *      Unique identifier for the profile originating the request
     * @responseparam <var>target</var> {string}
     *      Unique identifier for the profile to be absorbed
     * @responseparam <var>request</var> {string}
     *      Unique identifier for the merge request transaction
     */
    protected function getMergeRequests( $applicationUuid )
    {
       \lib\DebugConsole::stampAction();

        try
        {
            $requests = $this->model->getMergeRequest( $applicationUuid );
        }
        catch( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL, $e );
        }

        return new Response( Response::SUCCESS, $requests );

       \lib\DebugConsole::end();
    }


    /**
     * Retrieve the list of profile merge requests
     *
     * @param string Unique identifier for the application
     * @param string Unique identifier for the merge request transaction
     *
     * @requestbody <var>is_merge_accepted</var> {boolean}
     *      Indicates whether or not the application has
     *      transferred the target profile's metadata to the source profile
     * @requestbody <var>error_code</var> {integer}
     *      Numeric code to indicate error condition.
     *      If <var>is_merge_accepted</var> is set to FALSE,
     *      the code value must be greater than zero(0).
     * @requestbody <var>error_message</var> {string}
     *      Human-friendly description of the error condition.
     *      If <var>is_merge_accepted</var> is set to FALSE,
     *      the message value must not be empty.
     *
     * @return \Response
     */
    protected function putMergeResponse( $applicationUuid, $requestId )
    {
       \lib\DebugConsole::stampAction();

        try
        {
            $isSuccess =
                    $this->model->mergeResponse( $applicationUuid,
                            $requestId,
                            $this->restler->request_data[ 'is_merge_accepted' ],
                            $this->restler->request_data[ 'error_code' ],
                            $this->restler->request_data[ 'error_message' ]
                            );
        }
        catch( \InvalidArgumentException $e )
        {
            return new Response( Response::FAIL, $e );
        }

        return new Response( $isSuccess );

       \lib\DebugConsole::end();
    }


    /**
     * Add attributes to the application-specific information for
     * profiles that can be viewed by other applications
     *
     * @category POST

     * @param string Comma-separated list of attributes to add
     *
     * @return  Response
     */
    protected function postSharedAttributes( $attributes )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Validate the application identity
         */
        if ( empty ( $_SESSION[ 'sourceApplication' ] ) )
        {
            return new Response( Response::FAIL,
                    NULL,
                    Response::STATUS_FORBIDDEN
                    );
        }

        /**
         * Validate the attributes list
         */
        if ( empty ( $attributes ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'attributes'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Sanitize the attributes list
         */
        $attributes = explode( ',', $attributes );
        $counter = count( $attributes );
        for ( $i = 0; $i < $counter; $i++ )
        {
            $attributes[ $i ] = trim( $attributes[ $i ] );
        }

        /**
         * Set the shared attributes
         */
        try
        {
            $isSuccess =
                    $this->model->addSharedAttributes(
                            $_SESSION[ 'sourceApplication' ],
                            $attributes
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
     * Retrieve the list of application-specific attributes that can be
     * viewed by other applications
     *
     * @category GET
     *
     * @param string Unique identity for the application
     *
     * @responseparam <var>shared_attributes</var> {array}
     *      List of shared attribute keys
     *
     * @return  Response
     */
    protected function getSharedAttributes( $applicationId )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        if ( empty( $applicationId ) )
        {
            if ( empty ( $_SESSION[ 'sourceApplication' ] ) )
            {
                return new Response( Response::FAIL,
                        NULL,
                        Response::STATUS_FORBIDDEN
                        );
            }
            else
            {
                $applicationId = $_SESSION[ 'sourceApplication' ];
            }
        }

        try
        {
            $sharedAttributes =
                    $this->model->getSharedAttributes( $applicationId );
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
                array ( 'shared_attributes' => $sharedAttributes )

        );
    }


    /**
     * Remove attributes from the list of application-specific
     * information that can be viewed by other applications
     *
     * @category DELETE
     *
     * @param string Comma-separated list of attributes to remove
     *
     * @return  Response
     */
    protected function deleteSharedAttributes( $attributes )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Validate the application identity
         */
        if ( empty ( $_SESSION[ 'sourceApplication' ] ) )
        {
            return new Response( Response::FAIL,
                    NULL,
                    Response::STATUS_FORBIDDEN
                    );
        }

        /**
         * Validate the attributes list
         */
        if ( empty ( $attributes ) )
        {
            return new Response( Response::FAIL,
                    "Missing parameter 'attributes'",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Sanitize the attributes list
         */
        $attributes = explode( ',', $attributes );
        $counter = count( $attributes );
        for ( $i = 0; $i < $counter; $i++ )
        {
            $attributes[ $i ] = trim( $attributes[ $i ] );
        }

        try
        {
            $isSuccess =
                    $this->model->deleteSharedAttributes(
                            $_SESSION[ 'sourceApplication' ],
                            $attributes
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
     * Retrieve metadata for an application
     *
     * @param Unique identifier for the application
     *
     * @category GET
     *
     * @return Response Refer to response parameters
     *
     * @responseparam <var>name</var>
     *      Common English name for the application
     * @responseparam <var>logo</var>
     *      Global resource path for the application logo badge
     * @responseparam <var>launch_uri</var>
     *      Global resource path for the application home page
     */
    protected function get( $applicationId )
    {
       \lib\DebugConsole::stampAction();

        try
        {
            $app = $this->model->get( $applicationId );
        }
        catch ( \mysqli_sql_exception $e )
        {
            return new Response( Response::FAIL, $e );
        }
        catch ( \InvalidArgumentException $e )
        {
            if ( $e->getMessage() == 'Application ID not found' )
            {
                return new Response( Response::FAIL,
                        $e->getMessage(),
                        Response::STATUS_NOT_FOUND
                        );
            }
            else
            {
                return new Response( Response::FAIL, $e );
            }
        }

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $app );
    }


    /**
     * Retrieve a list of all known applications
     *
     * @category GET
     *
     * @requestparam <var>include_unpublished</var> {boolean}
     *      If TRUE, the result set includes applications notated as unpublished
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
     */
    protected function index()
    {
       \lib\DebugConsole::stampAction();

       $isIncludeUnpublished = FALSE;
       if ( !empty( $this->restler->request_data[ 'include_unpublished' ] ) )
       {
           $isIncludeUnpublished = RequestHelper::isTruthy(
                   $this->restler->request_data[ 'include_unpublished' ]
                   );
       }

        $apps = $this->model->getAll( $isIncludeUnpublished );

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $apps );
    }


    /**
     * Transmit an operational message via various transports
     * (email, SMS, in-application, etc)
     *
     * <p>It's assumed that the content of the notification has already been
     * translated to the profile user's preferred language
     *
     * @category POST
     *
     * @param {string} The transport medium for sending the message
     * <p>NOTE: Only the email transport is currently implemented
     *
     * @requestbody <var>recipients</var> {string}
     *      Comma-separated list of email address(es) that should receive the
     *      message
     * @requestbody <var>subject</var> {string}
     *      Text content for the message subject
     * @requestbody <var>body</var> {mixed}
     *      Content for the message body
     * @requestbody <var>sender_name</var> {string}
     *      Friendly name to impersonate as the message sender
     *
     * @since 2013-11-19
     */
    protected function postMessage( $transport )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $requiredParameters = array ( 'subject', 'body', 'recipients' );
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
                    "Missing required request parameter(s) - $missingParameters",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        if ( empty( $transport ) )
        {
            return new Response( Response::FAIL,
                    "Missing required parameter - transport",
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Build recipient list
         */
        $recipients =
                explode( ',', $this->restler->request_data[ 'recipients' ] );

        if ( count( $recipients ) <= 0 )
        {
            return new Response( Response::FAIL,
                    'Unable to parse recipients',
                    Response::STATUS_BAD_REQUEST
                    );
        }

        /**
         * Execute the delivery of the message
         */
        switch ( $transport )
        {
            case 'mail':
            case 'email':
                $senderName = NULL;
                $senderAddress = NULL;

                if ( !empty( $this->restler->request_data[ 'sender_name' ] ) )
                {
                    $senderName = $this->restler->request_data[ 'sender_name' ];
                }

                $subject = $this->restler->request_data[ 'subject' ];
                $body = $this->restler->request_data[ 'body' ];

                $results = array ();

                foreach ( $recipients as $recipient )
                {
                    $result = array ();

                    $recipient = trim( $recipient );
                    $result[ 'recipient' ] = $recipient;

                    $result[ 'is_queued' ] = FALSE;
                    $result[ 'message' ] = NULL;

                    $status =
                            \core\models\Profile::validateEmailAddress( $recipient );

                    switch ( $status )
                    {
                        case \core\models\Profile::EMAIL_STATUS_OK:
                        case $status & \core\models\Profile::EMAIL_STATUS_ADDRESS_EXISTS:
                            try
                            {
                                $this->queueEmail( $recipient,
                                        $subject,
                                        $body,
                                        $senderName,
                                        $senderAddress
                                        );

                                $result[ 'is_queued' ] = TRUE;
                            }
                            catch ( \InvalidArgumentException $e )
                            {
                                $result[ 'message' ]  = $e->getMessage();
                            }
                            break;

                        case $status & \core\models\Profile::EMAIL_STATUS_INVALID_FORMAT:
                            $result[ 'message' ]  .= 'Invalid address format. ';

                        case $status & \core\models\Profile::EMAIL_STATUS_INVALID_MX_RECORD:
                            $result[ 'message' ]  .= 'Invalid domain. ';

                    }

                    $result[ 'message' ] = trim( $result[ 'message' ] );
                    array_push( $results, $result );
                }
                break;

            default:
                return new Response( Response::FAIL,
                        'Invalid transport - ' . $transport,
                        Response::STATUS_BAD_REQUEST
                        );
        }

        return new Response( Response::SUCCESS, $results );
    }


    /**
     * Submit a request to send an email via the message bus
     *
     * @param string Email address of the receiving party
     * @param string Email subject for the message
     * @param string Main content block for the message
     * @throws InvalidArgumentException
     */
    private function queueEmail( $recipient, $subject, $body,
            $senderName = NULL, $senderAddress = NULL )
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

        if ( empty( $senderName ) AND
                !empty( $_SESSION[ 'sourceApplication' ] ) )
        {
            $app = $this->model->get( $_SESSION[ 'sourceApplication' ] );
            $senderName = $app[ 'name' ];
        }

        if ( !empty( $senderName ) )
        {
            $payload[ 'senderName' ] = $senderName;
        }

        if ( !empty( $senderAddress ) )
        {
            $payload[ 'senderAddress' ] = $senderAddress;
        }



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
}