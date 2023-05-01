<?php

#include '../../../inc/includes.php';
#include (__DIR__ . '/../../../inc/includes.php');
#include (__DIR__ . '/../../../inc/api.class.php');
#include '../../../inc/api.class.php';
use Glpi\Api\API;

include_once 'includes.php';

class PluginReservationApi extends API
{
    protected $request_uri;
    protected $url_elements;
    protected $verb;
    protected $parameters;
    protected $debug = 0;
    protected $format = "json";

    public static function getTypeName($nb = 0)
    {
        return __('Reservation Plugin Rest API');
    }

    public function call()
    {
        //parse http request and find parts
        $this->request_uri = $_SERVER['REQUEST_URI'];
        $this->verb = $_SERVER['REQUEST_METHOD'];
        $path_info = (isset($_SERVER['PATH_INFO'])) ? str_replace("api/", "", trim($_SERVER['PATH_INFO'], '/')) : '';
        $this->url_elements = explode('/', $path_info);

        // retrieve requested resource
        $resource = trim(strval($this->url_elements[0]));
        $is_inline_doc = (strlen($resource) == 0) || ($resource == "api");

        // Add headers for CORS
        $this->cors($this->verb);

        // retrieve paramaters (in body, query_string, headers)
        $this->parseIncomingParams($is_inline_doc);

        // show debug if required
        if (isset($this->parameters['debug'])) {
            $this->debug = $this->parameters['debug'];
            if (empty($this->debug)) {
                $this->debug = 1;
            }
            if ($this->debug >= 2) {
                $this->showDebug();
            }
        }

        // retrieve session (if exist)
        $this->retrieveSession();
        $this->initApi();
        $this->manageUploadedFiles();

        // retrieve param who permit session writing
        if (isset($this->parameters['session_write'])) {
            $this->session_write = (bool) $this->parameters['session_write'];
        }

        // inline documentation (api/)
        if ($is_inline_doc) {
            $this->inlineDocumentation("plugins/reservation/apirest.md");
        } else {
            switch ($resource) {
                case "initSession":
                    $this->session_write = true;
                    $this->returnResponse($this->initSession($this->parameters));
                    return;
                case "killSession":
                    $this->retrieveSession($this->parameters);
                    $this->session_write = true;
                    $this->returnResponse($this->killSession());
                    return;
                case "reservationItem":
                    return $this->reservationItemRoutes();
                // case "user":
                //    return $this->userRoutes();
                default:
                    return $this->returnError(
                        __("resource not found"),
                        400,
                        "ERROR_RESOURCE_NOT_FOUND"
                    );
            }
        }

        $this->messageLostError();
    }

    private function reservationItemRoutes()
    {
        $method = trim(strval($this->url_elements[1]));

        switch ($method) {
            case "checkin":
                return $this->checkinReservation();
            case "checkout":
                return $this->checkoutReservation();
            case "nextReservation":
                return $this->nextReservation();
            case "currentReservation":
                return $this->currentReservation();
            case "currentOrNextReservation":
                return $this->currentOrNextReservation();
            case "todayReservationOfUserId":
                return $this->todayReservationOfUserId();
            case "searchReservations":
                return $this->searchReservations();
            case "searchReservationItems":
                return $this->searchReservationItems();
            case "category":
                return $this->getCategory();
            default:
                return $this->returnError(
                    __("resource not found"),
                    400,
                    "ERROR_RESOURCE_NOT_FOUND"
                );
        }
    }

    /**
     *
     */
    private function searchReservations()
    {
        $additionalheaders = [];
        $code = 200;

        switch ($this->verb) {
            default:
            case "PUT":
                $code = 400;
                $response = "Use GET request !";
                break;
            case "GET":
                $response = array();
                $begin = $this->parameters['begin'];
                $end = $this->parameters['end'];
                $user = $this->parameters['user'];

                $filters = array(
                    "`begin` >= '" . $begin . "'",
                    "`end` <= '" . $end . "'",
                );

                if (isset($user)) {
                    array_push($filters, "`users_id` = '" . $user . "'");
                } else {
                    return $this->returnError(
                        __("missing params"),
                        400,
                        "ERROR_METHOD_NOT_ALLOWED"
                    );
                }

                $result = PluginReservationReservation::getAllReservations($filters);

                if (count($result) == 0) {
                    $this->messageNotfoundError();
                    return;
                }

                foreach ($result as $resa) {
                    $reservationItem = new ReservationItem();
                    $reservationItem->getFromDB($resa['reservationitems_id']);

                    $links = [
                        "links" => [
                            ["rel" => "Entity", "href" => self::$api_url . "/Entity/" . $reservationItem->fields['entities_id']],
                            ["rel" => $reservationItem->fields['itemtype'], "href" => self::$api_url . "/" . $reservationItem->fields['itemtype'] . "/" . $reservationItem->fields['items_id']],
                        ],
                    ];

                    array_push($response, array_merge($reservationItem->fields, $links));
                }
                break;
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function searchReservationItems()
    {
        $additionalheaders = [];
        $code = 200;

        $plugin_config = new PluginReservationConfig();
        $custom_categories = $plugin_config->getConfigurationValue("custom_categories", 0);
        if (!$custom_categories) {
            return $this->returnError(
                __("custom categories is not enabled"),
                400,
                "ERROR_METHOD_NOT_ALLOWED"
            );
        }

        switch ($this->verb) {
            default:
            case "PUT":
                $code = 400;
                $response = "Use GET request !";
                break;
            case "GET":
                $response = array();
                $begin = $this->parameters['begin'];
                $end = $this->parameters['end'];

                $result = PluginReservationCategory::getReservationItems($begin, $end, true);
                if (count($result) == 0) {
                    $this->messageNotfoundError();
                    return;
                }

                foreach ($result as $resa) {
                    $reservationItem = new ReservationItem();
                    $reservationItem->getFromDB($resa['id']);

                    $cat = [
                        "category" => [
                            "id" => $resa['category_id'],
                            "name" => $resa['category_name'],
                            "priority" => $resa['items_priority'],
                        ],
                    ];

                    $links = [
                        "links" => [
                            ["rel" => "Entity", "href" => self::$api_url . "/Entity/" . $reservationItem->fields['entities_id']],
                            ["rel" => $reservationItem->fields['itemtype'], "href" => self::$api_url . "/" . $reservationItem->fields['itemtype'] . "/" . $reservationItem->fields['items_id']],
                        ],
                    ];

                    array_push($response, array_merge($reservationItem->fields, $cat, $links));
                }
                break;
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function getCategory()
    {
        $id = $this->getId();
        $additionalheaders = [];
        $code = 200;

        $plugin_config = new PluginReservationConfig();
        $custom_categories = $plugin_config->getConfigurationValue("custom_categories", 0);
        if (!$custom_categories) {
            return $this->returnError(
                __("custom categories is not enabled"),
                400,
                "ERROR_METHOD_NOT_ALLOWED"
            );
        }

        switch ($this->verb) {
            default:
            case "PUT":
                $code = 400;
                $response = "Use GET request !";
                break;
            case "GET":
                $cat_id = PluginReservationCategory_Item::getCategoryId($id);

                if (is_countable($cat_id) && count($cat_id) == 1) {
                    $response = [$cat_id];
                    break;
                }
                $this->messageNotfoundError();
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function currentReservation()
    {
        $id = $this->getId();
        $additionalheaders = [];
        $code = 200;

        switch ($this->verb) {
            default:
            case "PUT":
                $code = 400;
                $response = "Use GET request !";
                break;
            case "GET":
                $time = time();
                $now = date("Y-m-d H:i:s", $time);
                $res = PluginReservationReservation::getAllReservations(
                    [
                        "`begin` <= '" . $now . "'",
                        "`end` >= '" . $now . "'",
                        "reservationitems_id = " . $id,
                    ]
                );

                if (count($res) == 0) {
                    $this->messageNotfoundError();
                    return;
                }
                $reservation = new Reservation();
                $reservation->getFromDB($res[0]['reservations_id']);

                $links = [
                    "links" => [
                        ["rel" => "ReservationItem", "href" => self::$api_url . "/ReservationItem/" . $reservation->fields['reservationitems_id']],
                        ["rel" => "User", "href" => self::$api_url . "/User/" . $reservation->fields['users_id']],
                    ],
                ];

                $response = array_merge($reservation->fields, $links);
                break;
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function nextReservation()
    {
        $id = $this->getId();
        $additionalheaders = [];
        $code = 200;

        switch ($this->verb) {
            default:
            case "PUT":
                $code = 400;
                $response = "Use GET request !";
                break;
            case "GET":
                $time = time();
                $now = date("Y-m-d H:i:s", $time);
                $next_reservation = PluginReservationReservation::getAllReservations(
                    [
                        "`begin` >= '" . $now . "'",
                        "`end` >= '" . $now . "'",
                        "reservationitems_id = " . $id,
                    ],
                    [
                        "order by begin",
                        "limit 1",
                    ]
                );

                if (count($next_reservation) == 0) {
                    $this->messageNotfoundError();
                    return;
                }
                $reservation = new Reservation();
                $reservation->getFromDB($next_reservation[0]['reservations_id']);

                $links = [
                    "links" => [
                        ["rel" => "ReservationItem", "href" => self::$api_url . "/ReservationItem/" . $reservation->fields['reservationitems_id']],
                        ["rel" => "User", "href" => self::$api_url . "/User/" . $reservation->fields['users_id']],
                    ],
                ];
                $response = array_merge($reservation->fields, $links);
                break;
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function currentOrNextReservation()
    {
        $id = $this->getId();
        $additionalheaders = [];
        $code = 200;

        switch ($this->verb) {
            default:
            case "PUT":
                $code = 400;
                $response = "Use GET request !";
                break;
            case "GET":
                $time = time();
                $now = date("Y-m-d H:i:s", $time);
                $res = PluginReservationReservation::getAllReservations(
                    [
                        "`end` >= '" . $now . "'",
                        "reservationitems_id = " . $id,
                    ],
                    [
                        "order by begin",
                        "limit 1",
                    ]
                );

                if (count($res) == 0) {
                    $this->messageNotfoundError();
                    return;
                }
                $reservation = new Reservation();
                $reservation->getFromDB($res[0]['reservations_id']);

                $links = [
                    "links" => [
                        ["rel" => "ReservationItem", "href" => self::$api_url . "/ReservationItem/" . $reservation->fields['reservationitems_id']],
                        ["rel" => "User", "href" => self::$api_url . "/User/" . $reservation->fields['users_id']],
                    ],
                ];

                $response = array_merge($reservation->fields, $links);
                break;
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function todayReservationOfUserId()
    {
        $user_id = $this->getId();
        $additionalheaders = [];
        $code = 200;

        switch ($this->verb) {
            default:
            case "PUT":
                $code = 400;
                $response = "Use GET request !";
                break;
            case "GET":
                $time = time();
                $day = date("d", time());
                $month = date("m", time());
                $year = date("Y", time());
                $now = date("Y-m-d H:i:s", $time);
                $end_day = date("Y-m-d H:i:s", mktime(23, 59, 00, $month, $day, $year));
                $res = PluginReservationReservation::getAllReservations(
                    [
                        "`end` >= '" . $now . "'",
                        "`begin` < '" . $end_day . "'",
                        "users_id = " . $user_id,
                        "checkindate is NULL",
                    ],
                    [
                        "order by begin",
                        "limit 1",
                    ]
                );

                if (count($res) == 0) {
                    $this->messageNotfoundError();
                    return;
                }
                $reservation = new Reservation();
                $reservation->getFromDB($res[0]['reservations_id']);

                $links = [
                    "links" => [
                        ["rel" => "ReservationItem", "href" => self::$api_url . "/ReservationItem/" . $reservation->fields['reservationitems_id']],
                        ["rel" => "User", "href" => self::$api_url . "/User/" . $reservation->fields['users_id']],
                    ],
                ];

                $response = array_merge($reservation->fields, $links);
                break;
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function checkinReservation()
    {
        $id = $this->getId();
        $additionalheaders = [];
        $code = 200;

        $config = new PluginReservationConfig();
        $checkin_enable = $config->getConfigurationValue("checkin", 0);
        if (!$checkin_enable) {
            return $this->returnError(
                __("check in function is not enabled"),
                400,
                "ERROR_METHOD_NOT_ALLOWED"
            );
        }

        switch ($this->verb) {
            default:
            case "GET":
                $code = 400;
                $response = "Use PUT request !";
                break;
            case "PUT":
                if (!isset($this->parameters['input'])) {
                    $this->messageBadArrayError();
                }
                // if id is passed by query string, add it into input parameter
                $input = (array) ($this->parameters['input']);
                if (($id > 0) && !isset($input['id'])) {
                    $this->parameters['input']->id = $id;
                }

                $time = time();
                $now = date("Y-m-d H:i:s", $time);
                $current_reservation = PluginReservationReservation::getAllReservations(
                    [
                        "`end` >= '" . $now . "'",
                        "reservationitems_id = " . $this->parameters['input']->id,
                        "checkindate is null",
                    ],
                    [
                        "order by begin",
                        "limit 1",
                    ]
                );

                if (count($current_reservation) == 1) {
                    $reservation_id = $current_reservation[0]['reservations_id'];
                    try {
                        if (PluginReservationReservation::checkinReservation($reservation_id)) {
                            $response = [$reservation_id => true, "message" => ""];
                        } else {
                            $response = [$reservation_id => false, "message" => "error in glpi !"];
                            $code = 404;
                        }
                    } catch (Exception $e) {
                        $response = [$reservation_id => false, "message" => $e->getMessage()];
                        $code = 404;
                    }
                    break;
                }
                $this->messageNotfoundError();
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function checkoutReservation()
    {
        $id = $this->getId();
        $additionalheaders = [];
        $code = 200;

        switch ($this->verb) {
            default:
            case "GET":
                $code = 400;
                $response = "Use PUT request !";
                break;
            case "PUT":
                if (!isset($this->parameters['input'])) {
                    $this->messageBadArrayError();
                }
                // if id is passed by query string, add it into input parameter
                $input = (array) ($this->parameters['input']);
                if (($id > 0) && !isset($input['id'])) {
                    $this->parameters['input']->id = $id;
                }

                $time = time();
                $now = date("Y-m-d H:i:s", $time);
                $current_reservation = PluginReservationReservation::getAllReservations(
                    [
                        "`begin` <= '" . $now . "'",
                        "`end` >= '" . $now . "'",
                        "reservationitems_id = " . $this->parameters['input']->id,
                        "effectivedate is null",
                    ]
                );

                if (count($current_reservation) == 1) {
                    $reservation_id = $current_reservation[0]['reservations_id'];
                    try {
                        PluginReservationReservation::checkoutReservation($reservation_id);
                        $response = [$reservation_id => true, "message" => ""];
                    } catch (Exception $e) {
                        $response = [$reservation_id => false, "message" => $e->getMessage()];
                    }
                    break;
                }
                $this->messageNotfoundError();
        }
        $this->returnResponse($response, $code, $additionalheaders);
    }

    /**
     *
     */
    private function getId()
    {
        $last = end($this->url_elements);
        $id = is_numeric($last) ? intval($last) : false;

        // $id = isset($this->url_elements[1]) && is_numeric($this->url_elements[1])
        //    ? intval($this->url_elements[1])
        //    : false;

        // $additional_id = isset($this->url_elements[3]) && is_numeric($this->url_elements[3])
        //    ? intval($this->url_elements[3])
        //    : false;

        // if ($additional_id || isset($this->parameters['parent_itemtype'])) {
        //    $this->parameters['parent_id'] = $id;
        //    $id = $additional_id;
        // }

        return $id;
    }

    public function parseIncomingParams($is_inline_doc = false)
    {
        $parameters = [];

        // first of all, pull the GET vars
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $parameters);
        }
        // now how about PUT/POST bodies? These override what we got from GET
        $body = trim($this->getHttpBody());
        if (strlen($body) > 0 && $this->verb == "GET") {
            // GET method requires an empty body
            $this->returnError(
                "GET Request should not have json payload (http body)",
                400,
                "ERROR_JSON_PAYLOAD_FORBIDDEN"
            );
        }
        $content_type = "";
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = $_SERVER['CONTENT_TYPE'];
        } else if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            $content_type = $_SERVER['HTTP_CONTENT_TYPE'];
        } else {
            if (!$is_inline_doc) {
                $content_type = "application/json";
            }
        }
        if (strpos($content_type, "application/json") !== false) {
            if ($body_params = json_decode($body)) {
                foreach ($body_params as $param_name => $param_value) {
                    $parameters[$param_name] = $param_value;
                }
            } else if (strlen($body) > 0) {
                $this->returnError(
                    "JSON payload seems not valid",
                    400,
                    "ERROR_JSON_PAYLOAD_INVALID",
                    false
                );
            }
            $this->format = "json";
        } else if (strpos($content_type, "multipart/form-data") !== false) {
            if (count($_FILES) <= 0) {
                // likely uploaded files is too big so $_REQUEST will be empty also.
                // see http://us.php.net/manual/en/ini.core.php#ini.post-max-size
                $this->returnError(
                    "The file seems too big",
                    400,
                    "ERROR_UPLOAD_FILE_TOO_BIG_POST_MAX_SIZE",
                    false
                );
            }
            // with this content_type, php://input is empty... (see http://php.net/manual/en/wrappers.php.php)
            if (!$uploadManifest = json_decode(stripcslashes($_REQUEST['uploadManifest']))) {
                $this->returnError(
                    "JSON payload seems not valid",
                    400,
                    "ERROR_JSON_PAYLOAD_INVALID",
                    false
                );
            }
            foreach ($uploadManifest as $field => $value) {
                $parameters[$field] = $value;
            }
            $this->format = "json";
            // move files into _tmp folder
            $parameters['upload_result'] = [];
            $parameters['input']->_filename = [];
            $parameters['input']->_prefix_filename = [];
        } else if (strpos($content_type, "application/x-www-form-urlencoded") !== false) {
            parse_str($body, $postvars);
            foreach ($postvars as $field => $value) {
                $parameters[$field] = $value;
            }
            $this->format = "html";
        } else {
            $this->format = "html";
        }
        // retrieve HTTP headers
        $headers = [];
        if (function_exists('getallheaders')) {
            //apache specific
            $headers = getallheaders();
            if (false !== $headers && count($headers) > 0) {
                $fixedHeaders = [];
                foreach ($headers as $key => $value) {
                    $fixedHeaders[ucwords(strtolower($key), '-')] = $value;
                }
                $headers = $fixedHeaders;
            }
        } else {
            // other servers
            foreach ($_SERVER as $server_key => $server_value) {
                if (substr($server_key, 0, 5) == 'HTTP_') {
                    $headers[str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace(
                            '_',
                            ' ',
                            substr($server_key, 5)
                        )))
                    )] = $server_value;
                }
            }
        }
        // try to retrieve basic auth
        if (
            isset($_SERVER['PHP_AUTH_USER'])
            && isset($_SERVER['PHP_AUTH_PW'])
        ) {
            $parameters['login'] = $_SERVER['PHP_AUTH_USER'];
            $parameters['password'] = $_SERVER['PHP_AUTH_PW'];
        }
        // try to retrieve user_token in header
        if (
            isset($headers['Authorization'])
            && (strpos($headers['Authorization'], 'user_token') !== false)
        ) {
            $auth = explode(' ', $headers['Authorization']);
            if (isset($auth[1])) {
                $parameters['user_token'] = $auth[1];
            }
        }
        // try to retrieve session_token in header
        if (isset($headers['Session-Token'])) {
            $parameters['session_token'] = $headers['Session-Token'];
        }
        // try to retrieve app_token in header
        if (isset($headers['App-Token'])) {
            $parameters['app_token'] = $headers['App-Token'];
        }
        // check boolean parameters
        foreach ($parameters as $key => &$parameter) {
            if ($parameter === "true") {
                $parameter = true;
            }
            if ($parameter === "false") {
                $parameter = false;
            }
        }
        $this->parameters = $parameters;
        return "";
    }

    public function returnResponse($response, $httpcode = 200, $additionalheaders = [])
    {
        if (empty($httpcode)) {
            $httpcode = 200;
        }
        foreach ($additionalheaders as $key => $value) {
            header("$key: $value");
        }
        http_response_code($httpcode);
        self::header($this->debug);
        if ($response !== null) {
            $json = json_encode($response, JSON_UNESCAPED_UNICODE
                 | JSON_UNESCAPED_SLASHES
                 | JSON_NUMERIC_CHECK
                 | ($this->debug
                    ? JSON_PRETTY_PRINT
                    : 0));
        } else {
            $json = '';
        }
        if ($this->debug) {
            echo "<pre>";
            var_dump($response);
            echo "</pre>";
        } else {
            echo $json;
        }
        exit;
    }

    public function manageUploadedFiles()
    {
        foreach ($_FILES as $filename => $files) {
            $upload_result = GLPIUploadHandler::uploadFiles(['name' => $filename, 'print_response' => false]);
            foreach ($upload_result as $uresult) {
                $this->parameters['input']->_filename[] = $uresult[0]->name;
                $this->parameters['input']->_prefix_filename[] = $uresult[0]->prefix;
            }
            $this->parameters['upload_result'][] = $upload_result;
        }
    }
}
