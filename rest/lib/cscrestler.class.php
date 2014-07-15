<?php

class cscRestler extends Restler {


    /**
     * An initialize function to allow use of the restler error generation 
     * functions for pre-processing and pre-routing of requests. But in our case
     * we are allowing body in the DELETE
     */
    public function init() {
        if( empty($this->format_map) ) {
            $this->setSupportedFormats('JsonFormat');
        }
        $this->url = $this->getPath();
        $this->request_method = $this->getRequestMethod();
        $this->response_format = $this->getResponseFormat();
        $this->request_format = $this->getRequestFormat();
        if( is_null($this->request_format) ) {
            $this->request_format = $this->response_format;
        }
        if( $this->request_method == 'PUT' || $this->request_method == 'POST' || $this->request_method == 'DELETE' ) {

            $this->request_data = $this->getRequestData();
        }
    }


}