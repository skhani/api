<?php
/**
 * Tracker class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;
use core\models\Profile;

/**
 * Tracker class for tracking user activity
 *
 * @package DeniZEN_Utilities
 * @since 2013-09-12
 */
class Tracker
{
    /**
     * Create an instance of the Tracker model
     *
     * @internal
     */
    function __construct() {}

    /**
     * Log a page view event to the trackers
     *
     * @category POST
     *
     * @requestparam <var>page_title</var> {string} Document title for the
     * resource page viewed
     *
     * @requestparam <var>page_uri</var> {string} Fully-qualified path for the
     * resource page viewed
     *
     * @requestparam <var>app_id</var> {string} Optional unique identifier for
     * the reporting CCS application
     *
     * @requestparam <var>attributes</var> {array} Optional list of key/value
     * pairs of additional meta-data for the event
     *
     * @requestparam <var>viewer_id</var> {string} Optional unique identifier
     * for the end-user that requested the view.
     * <ul>
     *      <li>If the page view was requested by a known DeniZEN profile user,
     *      specify the profile UUID
     *      <li>Otherwise, if calling this method for the first time for an
     *      anonymous user, specify NULL or exclude the parameter. This method
     *      will respond with a unique identifier that can be used in subsequent
     *      calls in order to track the anonymous user's path.
     * </ul>
     *
     * @return \Response
     *
     * @responseparam <var>viewer_id</var> {string} If viewer_id was not
     *      supplied in the request, this operation will assign a new identity
     *      for the user, which can be stored by the client
     *      (or calling application) to track subsequent requests
     */
    protected function postPageView()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Validate required parameters
         */
        $requiredParameters = array ( 'page_title', 'page_uri' );
        $missingParameters = array ();
        foreach ( $requiredParameters as $required )
        {
            if ( empty( $this->restler->request_data[ $required ] ) )
            {
                array_push( $missingParameters, $required );
            }
        }

        if ( count( $missingParameters ) > 0 )
        {
            return new Response( Response::FAIL,
                    'Missing required parameter(s): ' .
                        implode( ', ', $missingParameters ),
                    Response::STATUS_BAD_REQUEST );
        }

        /**
         * Build the tracking payload
         */
        $trackerInfo = array ();

        /**
         * If supplied, add custom attributes to the tracking payload
         */
        if ( !empty( $this->restler->request_data[ 'attributes' ] ) AND
                is_array( $this->restler->request_data[ 'attributes' ] ) )
        {
            $trackerInfo = array_merge( $trackerInfo,
                    $this->restler->request_data[ 'attributes' ] );
        }

        /**
         * Add page information to the tracking payload
         */
        $trackerInfo[ 'Document title' ] =
                $this->restler->request_data[ 'page_title' ];

        $trackerInfo[ 'URI' ] =
                $this->restler->request_data[ 'page_uri' ];

        /**
         * If supplied, add the application ID to the payload
         */
        if ( !empty( $this->restler->request_data[ 'app_id' ] ) )
        {
            $trackerInfo['Application ID'] =
                \core\lib\utilities\Tracker::formatUuid(
                        $this->restler->request_data[ 'app_id' ]
                        );
        }

       \lib\DebugConsole::info( $trackerInfo, "Attributes" );

        /**
         * Identify the viewer
         */
        $isNewViewer = FALSE;
        if ( !empty( $this->restler->request_data[ 'viewer_id' ] ) )
        {
            $viewerId =
                    \core\lib\utilities\Tracker::formatUuid(
                        $this->restler->request_data[ 'viewer_id' ]
                        );
        }
        else
        {
            $isNewViewer = TRUE;
            $viewerId = \core\lib\utilities\Tracker::formatUuid( uniqid() );
        }

       \lib\DebugConsole::info($viewerId, "Viewer ID");

        /**
         * Submit the tracking event to the message bus
         */
        \core\lib\utilities\Tracker::log(
                $_SESSION[ 'sourceId' ],
                $_SESSION[ 'sourceName' ],
                $viewerId,
                \core\lib\utilities\Tracker::PAGE_VIEW,
                $trackerInfo
        );

       \lib\DebugConsole::end();

        if ( $isNewViewer )
        {
            return new Response( Response::SUCCESS,
                    array ( 'viewer_id' => $viewerId)
                    );
        }
        else
        {
            return new Response( Response::SUCCESS );
        }
    }


    /**
     * Map the conversion of an anonymous user to a DeniZEN profile
     *
     * @category POST
     *
     * @param {string} Anonymous end-user's unique tracking identifier
     * @param {string} Profile UUID for the user's DeniZEN profile
     *
     * @return \Response
     */
    protected function postConvert( $anonymousId, $profileId )
    {
       \lib\DebugConsole::stampAction( $this->restler );

        /**
         * Validate required parameters
         */
        $requiredParameters = array ( 'anonymousId', 'profileId' );
        $missingParameters = array ();
        foreach ( $requiredParameters as $required )
        {
            if ( empty( $$required ) )
            {
                array_push( $missingParameters, $required );
            }
        }

        if ( count( $missingParameters ) > 0 )
        {
            return new Response( Response::FAIL,
                    'Required parameter(s) cannot be empty: ' .
                        implode( ', ', $missingParameters ),
                    Response::STATUS_BAD_REQUEST );
        }

       \lib\DebugConsole::info( $trackerInfo, "Attributes" );

        $trackerInfo = array
        (
            'alias_distinct_id' =>
                \core\lib\utilities\Tracker::formatUuid( $anonymousId ),
        );

//        $model = new core\models\Profile();
//        $profileInfo = $model->get( 'uuid', $profileId );
//        $trackerInfo[ 'public_name' ] = $profileInfo[ 'publicname' ];

        \core\lib\utilities\Tracker::log(
                $_SESSION[ 'sourceId' ],
                $_SESSION[ 'sourceName' ],
                \core\lib\utilities\Tracker::formatUuid( $profileId ),
                \core\lib\utilities\Tracker::CONVERSION,
                $trackerInfo
        );

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS );
    }
}