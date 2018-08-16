<?php

class ReservationAPIRest extends API {

    protected $request_uri;
    protected $url_elements;
    protected $verb;
    protected $parameters;
    protected $debug           = 0;
    protected $format          = "json";

    public static function getTypeName($nb=0) {
        return __('Reservation Plugin Rest API');
     }

    function call() {
        return $this->inlineDocumentation("apirest.md");
    }

}

