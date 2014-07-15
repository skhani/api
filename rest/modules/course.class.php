<?php

/**
 * Description of course
 *
 * @author Shahin Mohammadkhani <skhani at creativechannel.com>
 * 
 */
use \GeoCode;


/**
 * Course Group
 * @package DeniZEN_Models
 * @api
 * 
 */
class Course {

    private $model;


    /**
     * Create an instance of the ApiKey model
     */
    function __construct() {
        \lib\DebugConsole::stampFunctionCall();

        try {
            \lib\DebugConsole::log("Instantiating DeniZEN CORE model");
            $this->model = new core\models\Course();
        } catch ( \Exception $e ) {
            return new Response(Response::FAIL, $e);
        } catch ( \InvalidArgumentException $e ) {
            return new Response(Response::FAIL, $e);
        }

        \lib\DebugConsole::end();
    }


    /**
     * Add/Update a course Deep Link.
     * If it is a new course the response will be 201.
     * If it is an existing course the response will be 200.
     * @category PUT
     * @param type $orgId
     * @param type $courseId
     * @requestbody <var>api_key</var> {string} 
     * @requestbody <var>course_uri</var> {url}
     * @requestbody <var>vendor</var> {string}
     * @requestbody <var>metadata</var> {array}
     * @return \Response
     */
    protected function putDeepLink( $orgId, $courseId ) {

        $result = $this->_handlePutDeeplink($orgId, $courseId);

        if( $result == false ) {
            return new Response(Response::FAIL, Response::STATUS_NOT_FOUND,
                    Response::STATUS_NOT_FOUND
            );
        } elseif( $result == 200 ) {
            return new Response(Response::SUCCESS, null, Response::STATUS_OK);
        }
        return new Response(Response::SUCCESS, null, Response::STATUS_CREATED);
    }


    /**
     * Delete a course deeplink
     * @category DELETE
     * @param type $orgId
     * @param type $courseId
     * @requestbody <var>api_key</var> {string} 
     * @return \Response
     */
    protected function deleteDeepLink( $orgId, $courseId ) {

        $result = $this->_handleDeleteDeeplink($orgId, $courseId,
                $recordStatus = 0);
        if( $result == false ) {
            return new Response(Response::FAIL, "Deeplink not found",
                    Response::STATUS_NOT_FOUND
            );
        } elseif( $result == 200 ) {
            return new Response(Response::SUCCESS, null, Response::STATUS_OK);
        }
        return new Response(Response::FAIL, null, Response::STATUS_BAD_REQUEST);
    }


    /**
     * @category PUT
     * @param type $orgId
     * @param type $userId
     * @param type $courseId
     * @param type $timeCompleted
     * @requestbody <var>api_key</var> {string} 
     * @requestbody <var>passed</var> {url} *(optional)*
     * @requestbody <var>score</var> {float} *(optional)*
     * @return \Response
     */
    protected function putCompletion( $orgId, $userId, $courseId, $timeCompleted ) {

        $record = $this->_handleCompletion($orgId, $userId, $courseId,
                $timeCompleted);
        if( $record == false ) {
            return new Response(Response::FAIL, Response::STATUS_NOT_FOUND,
                    Response::STATUS_NOT_FOUND
            );
        } elseif( $record == 200 ) {
            return new Response(Response::SUCCESS, null, Response::STATUS_OK);
        }
        return new Response(Response::SUCCESS, null, Response::STATUS_CREATED);
    }


    /**
     * @category DELETE
     * @param type $orgId
     * @param type $userId
     * @param type $courseId
     * @param type $timeCompleted
     * @requestbody <var>api_key</var> {string} 
     * @return \Response
     */
    protected function deleteCompletion( $orgId, $userId, $courseId,
            $timeCompleted ) {

        $record = $this->_handleCompletion($orgId, $userId, $courseId,
                $timeCompleted, $status = 0);
        if( $record == false ) {
            return new Response(Response::FAIL,
                    "Course Completion record not found",
                    Response::STATUS_NOT_FOUND
            );
        } elseif( $record == 200 ) {
            return new Response(Response::SUCCESS, null, Response::STATUS_OK);
        }
        return new Response(Response::FAIL, null, Response::STATUS_BAD_REQUEST);
    }


    /**
     * Check if var is an integer or a string representation of an integer
     * @param type $timeCompleted
     * @return boolean
     */
    private function _validateStringAsInt( $timeCompleted ) {
        if( (string) (int) $timeCompleted == $timeCompleted ) {
            return true;
        }
        return new Response(Response::FAIL,
                "Malformed Unix Timestamp. Timestamp should be in seconds",
                Response::STATUS_BAD_REQUEST);
    }


    /**
     * Validate the organization id in the request to the one returned from the database
     * given the api key in the request body  refer to cscauthapi.class.php
     * @param type $orgId
     * @return boolean
     */
    private function _validateOrgId( $orgId ) {
        if( strtolower($_SESSION['org_id']) !== strtolower($orgId) ) {
            return false;
        }
        return true;
    }


    /**
     * Validation Method to check if all required parameters are fullfiled
     * @param type $orgId
     * @param type $userId
     * @param type $courseId
     * @param type $timeCompleted
     * @return \Response
     */
    private function _emptyCompletionValidation( $orgId, $userId, $courseId,
            $timeCompleted ) {
        /**
         * Check for Org ID
         */
        if( empty($orgId) ) {
            return new Response(Response::FAIL,
                    "Missing parameter Organization ID",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Compare organizationID in the request with the one returned
         * form the DB.
         */
        $isValidOrg = $this->_validateOrgId($orgId);
        if( !$isValidOrg ) {
            return new Response(Response::FAIL,
                    "Organization Id does not match API Key",
                    Response::STATUS_BAD_REQUEST
            );
        }
        /**
         * Check for user ID
         */
        if( empty($userId) ) {
            return new Response(Response::FAIL, "Missing parameter User Id",
                    Response::STATUS_BAD_REQUEST
            );
        }



        /**
         * Check for course ID
         */
        if( empty($courseId) ) {
            return new Response(Response::FAIL, "Missing parameter Course ID",
                    Response::STATUS_BAD_REQUEST
            );
        }



        /**
         * Check for completion time
         */
        if( empty($timeCompleted) ) {
            return new Response(Response::FAIL,
                    "Missing parameter Completion time",
                    Response::STATUS_BAD_REQUEST
            );
        }
    }


    /**
     * Handle both PUT and DELETE completion since they are the same 
     * with the only difference of the status flag
     * @param type $orgId
     * @param type $userId
     * @param type $courseId
     * @param type $timeCompleted
     * @param type $status
     * @return \Response
     */
    private function _handleCompletion( $orgId, $userId, $courseId,
            $timeCompleted, $status = 1 ) {
        /**
         * lowercase string orgid
         */
        $orgId = strtolower($orgId);
        /**
         * Lower Case keys in the request body
         */
        $inputData = array_change_key_case($this->restler->request_data);
        $this->_emptyCompletionValidation($orgId, $userId, $courseId,
                $timeCompleted);

        /**
         * Validated timestamp
         */
        $this->_validateStringAsInt($timeCompleted);

        if( empty($inputData['passed']) && $status == 1 ) {
            return new Response(Response::FAIL, "Missing parameter 'passed'",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Validate User. if not valid returns false
         * if true returns user attributes
         */
        $validUser = $this->model->validateUser($userId, $orgId);
        if( $validUser == false ) {
            return new Response(Response::FAIL, "Invalid User Id '$userId'",
                    Response::STATUS_BAD_REQUEST
            );
        }
        //print_r($validUser);

        /**
         * Validate Course. If empty result set, returns false
         * if cours exists, gets its information.
         */
        $course = $this->model->isCourseExist($courseId);
        //if false then its not a generic pre-deeplink course
        if( $course == false ) {
            /**
             * check if the course was created through deeplink and if so return 
             * the course ID
             * checking through composite courseid
             */
            $compositeCourseId = "$orgId-$courseId";
            $courseId = $this->model->isDeeplinkCourseExist($compositeCourseId);
            if( $courseId == 0 ) {
                return new Response(Response::FAIL,
                        "Invalid Course Id '$courseId'",
                        Response::STATUS_BAD_REQUEST
                );
            }
        }

        /**
         * Create the completion record
         * return 200 if record is updated, return 201 if record is created
         */
        $record = $this->model->recordCompletion($validUser->nPersonID,
                $courseId, $timeCompleted, $inputData, $status);

        return $record;
    }


    /**
     * Handle the delete deeplink request validation
     * @param type $orgId
     * @param type $courseId
     * @param type $recordStatus
     * @return \Response
     */
    private function _handleDeleteDeeplink( $orgId, $courseId, $recordStatus = 0 ) {
        $inputData = array_change_key_case($this->restler->request_data);
        $dataParametersDefinition = array(
            'api_key' => ''
        );
        $emptyParameters = array(
                );
        $orgId = urldecode($orgId);
        /**
         * Validate Organization Id
         */
        if( empty($orgId) ) {
            return new Response(Response::FAIL,
                    "Missing parameter Organization ID",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Compare organizationID in the request with the one returned
         * form the DB.
         */
        $isValidOrg = $this->_validateOrgId($orgId);
        if( !$isValidOrg ) {
            return new Response(Response::FAIL,
                    "Organization Id does not match API Key",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Validate Course Id.
         */
        if( empty($courseId) ) {
            return new Response(Response::FAIL, "Missing parameter Course ID",
                    Response::STATUS_BAD_REQUEST
            );
        }
        /**
         * Course Id can not be larger than 40 characters
         */
        if( strlen($courseId) > 40 ) {
            return new Response(Response::FAIL,
                    "Course ID is larger than 40 characters",
                    Response::STATUS_BAD_REQUEST
            );
        }

        $courseId = strtolower($courseId);


        /**
         * Validate Data Parameters. Confirm that they exist in the request
         */
        $missingDataParams = array_diff_key($dataParametersDefinition,
                $inputData);


        /**
         * If any is missing throw error
         */
        if( !empty($missingDataParams) ) {
            $missingParams = implode(', ', array_keys($missingDataParams));
            return new Response(Response::FAIL,
                    "Missing Data Parameters : $missingParams",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Verify that the data parameters are not empty
         * If they are empty throw error
         */
        foreach( $inputData as $key => $value ) {
            if( empty($value) ) {
                array_push($emptyParameters, $key);
            }
        }

        if( !empty($emptyParameters) ) {
            $parameters = implode(', ', $emptyParameters);
            return new Response(Response::FAIL,
                    "($parameters) can not be empty",
                    Response::STATUS_BAD_REQUEST);
        }

        $entity = $this->model->getOrganizationEntityId($inputData[api_key]);

        $inputData['vendorId'] = $entity;

        $result = $this->model->setCourseRecord($orgId, $courseId, $inputData,
                $recordStatus);
        return $result;
    }


    /**
     * Performs the necessary functions to create depplink course.
     * records status 1 means active 0 means deleted (soft delete)
     * @param type $orgId
     * @param type $courseId
     * @param type $recordStatus
     * @return \Response
     */
    private function _handlePutDeeplink( $orgId, $courseId, $recordStatus = 1 ) {

        /**
         * Lower Case keys in the request body
         */
        //lowercase all first tier keys
        $inputData = array_change_key_case($this->restler->request_data);
        foreach( $inputData as $key => $value ) {
            //lower case all second tier keys (metadata)
            if( is_array($inputData[$key]) ) {
                $inputData[$key] = array_change_key_case($inputData[$key]);
                //lowercase all 3rd level keys (name,description)
                foreach( $inputData[$key] as $key1 => $value1 ) {
                    if( is_array($inputData[$key][$key1]) ) {
                        $inputData[$key][$key1] = array_change_key_case($inputData[$key][$key1]);
                    }
                }
            }
        }


        $emptyParameters = array(
                );
        $dataParametersDefinition = array(
            'api_key' => '',
            'course_uri' => '',
            'vendor' => '',
            'metadata' => array(
            ) );

        $orgId = urldecode($orgId);
        /**
         * Validate Organization Id
         */
        if( empty($orgId) ) {
            return new Response(Response::FAIL,
                    "Missing parameter Organization ID",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Compare organizationID in the request with the one returned
         * form the DB.
         */
        $isValidOrg = $this->_validateOrgId($orgId);
        if( !$isValidOrg ) {
            return new Response(Response::FAIL,
                    "Organization Id does not match API Key",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Validate Course Id.
         */
        if( empty($courseId) ) {
            return new Response(Response::FAIL, "Missing parameter Course ID",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Course Id can not be larger than 40 characters
         */
        if( strlen($courseId) > 40 ) {
            return new Response(Response::FAIL,
                    "Course ID is larger than 40 characters",
                    Response::STATUS_BAD_REQUEST
            );
        }

        $courseId = strtolower($courseId);


        /**
         * Validate Data Parameters. Confirm that they exist in the request
         */
        $missingDataParams = array_diff_key($dataParametersDefinition,
                $inputData);


        /**
         * If any is missing throw error
         */
        if( !empty($missingDataParams) ) {
            $missingParams = implode(', ', array_keys($missingDataParams));
            return new Response(Response::FAIL,
                    "Missing Data Parameters : $missingParams",
                    Response::STATUS_BAD_REQUEST
            );
        }

        /**
         * Verify that the data parameters are not empty
         * If they are empty throw error
         */
        foreach( $inputData as $key => $value ) {
            if( empty($value) ) {
                array_push($emptyParameters, $key);
            }
        }

        if( !empty($emptyParameters) ) {
            $parameters = implode(', ', $emptyParameters);
            return new Response(Response::FAIL,
                    "($parameters) can not be empty",
                    Response::STATUS_BAD_REQUEST);
        }

        /**
         * Verify that the metadata parameters exist and 
         * are not empty and have values for each language
         */
        if( !in_array('metadata', $emptyParameters) ) {
            $metadataParams = array(
                'name' => '',
                'description' => '' );

            // value contains name and desctription of language as array
            foreach( $inputData['metadata'] as $lang => $value ) {

                //TODO: Verify that the language code is ISO 639-1 compliant

                $missingParam = array_diff_key($metadataParams, $value);
                if( !empty($missingParam) ) {

                    $param = implode(', ', array_keys($missingParam));

                    // returns the data parameters that are malformed for 
                    // each language
                    return new Response(Response::FAIL,
                            "Missing Data Parameters for language $lang: $param",
                            Response::STATUS_BAD_REQUEST);
                }
                /**
                 * for each array parameter in the language code verify
                 * that the value is not empty
                 */
                foreach( $value as $key => $text ) {
                    $text = ltrim(rtrim($text));
                    if( empty($text) ) {
                        return new Response(Response::FAIL,
                                "($key) can not be empty for ($lang) language",
                                Response::STATUS_BAD_REQUEST);
                    }
                }
            }
        }//end if metadata
        //TODO: check ISO-6391 language
        //
        /**
         * Verify vendor exists
         */
        $vendorId = $this->model->getVendorId($inputData['vendor'], null);
        if( $vendorId == 0 ) {
            $vendorName = $inputData['vendor'];
            return new Response(Response::FAIL, "'$vendorName'",
                    Response::STATUS_NOT_FOUND);
        }
        $inputData['vendorId'] = $vendorId;
        $result = $this->model->setCourseRecord($orgId, $courseId, $inputData,
                $recordStatus);
        return $result;
    }


}

