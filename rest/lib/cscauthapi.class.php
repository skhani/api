<?php

use \core\models\CscApiKey;
use core\lib\connectors\SqlStore;


class CscAuthApi implements iAuthenticate {
    /*
     * Status conditions for authorization
     */

    const NOT_AUTHORIZED = FALSE;
    const AUTHORIZED = TRUE;


    function __isAuthenticated() {
        $isExists = self::NOT_AUTHORIZED;
        if( $this->restler->request_method == 'PUT' ) {
            
        }
        if( isset($this->restler->request_data['api_key']) ) {

            /**
             * Check to see if the api key even exists in the system
             */
            $model = new \core\models\CscApiKey();
            $isExists = $model->isExists($this->restler->request_data['api_key']);

            if( !empty($isExists) ) {

                $_SESSION['org_id'] = $isExists->org_id;
                return self::AUTHORIZED;
            }
            throw new RestException(401, 'Invalid API Key');
        }
        return self::NOT_AUTHORIZED;
    }


}