<?php
/**
 * GeoCode class file
 *
 * @author Danny Knapp <dknapp@creativechannel.com>
 * @copyright (c) 2013-2014, Creative Channel Services
 * @version %Date% %Author%
 */

//use core\lib\utilities\DebugConsole;
use core\errorhandling;

/**
 * GeoCode class for various geographic functions
 *
 * @package DeniZEN_Utilities
 * @since Sprint 3
 */
class GeoCode
{
    private $model;

    /**
     * Create an instance of the GeoCode model
     *
     * @internal
     */
    function __construct()
    {
       \lib\DebugConsole::stampFunctionCall();

        try
        {
           \lib\DebugConsole::log( "Instantiating DeniZEN CORE model" );
            $this->model = new core\models\GeoCode();
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
     * This function doesn't pertain
     *
     * @ignore
     */
    protected function index() {}


    /**
     * Retrieve a list of ISO 3166-1 alpha-2 country codes
     *
     * @category GET
     *
     * @return \Response
     */
    public function getCountries()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $countries = $this->model->getCountries();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $countries );
    }


    /**
     * Retrieve a list of ISO 3166-1 alpha-2 country codes and
     * country names
     *
     * @category GET
     *
     * @return \Response
     */
    public function getCountryFriendlyNames()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $countries = $this->model->getCountryFriendlyNames();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $countries );
    }


    /**
     * Retrieve a list of ISO 3166-1 alpha-2 country codes and
     * localized country names
     *
     * @category GET
     *
     * @return \Response
     */
    public function getCountryLocalNames()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $countries = $this->model->getCountryLocalNames();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $countries );
    }


    /**
     * Load a list of ISO 3166-1 alpha-2 country codes into the cache
     *
     * @category GET
     * @internal
     *
     * @class AuthApiGroup(groupRequired=admin)
     *
     * @return \Response
     * @responseparam
     */
    protected function putCountries()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $countries = $this->model->loadCountries();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $countries );
    }


    /**
     * Retrieve a list of ISO 639-1 alpha-2 language codes
     *
     * @category GET
     *
     * @return \Response
     */
    public function getLanguageCodes()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $languages = $this->model->getLanguageCodes();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $languages );
    }


    /**
     * Retrieve a list of ISO 639-1 alpha-2 language codes and
     * language names
     *
     * @category GET
     *
     * @return \Response
     */
    public function getLanguageFriendlyNames()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $languages = $this->model->getLanguageFriendlyNames();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $languages );
    }


    /**
     * Retrieve a list of ISO 639-1 alpha-2 language codes and
     * localized language names
     *
     * @category GET
     *
     * @return \Response
     */
    public function getLanguageLocalNames()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $languages = $this->model->getLanguageLocalNames();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $languages );
    }


    /**
     * Load a list of ISO 639-1 alpha-2 language codes into the cache
     *
     * @category GET
     * @internal
     *
     * @class AuthApiGroup(groupRequired=admin)
     *
     * @return \Response
     * @responseparam
     */
    protected function putLanguageCodes()
    {
       \lib\DebugConsole::stampAction( $this->restler );

        $languages = $this->model->loadLanguageCodes();

       \lib\DebugConsole::end();

        return new Response( Response::SUCCESS, $languages );
    }
}