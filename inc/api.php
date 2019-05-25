<?php

#include '../../../inc/includes.php';
#include (__DIR__ . '/../../../inc/includes.php');
#include (__DIR__ . '/../../../inc/api.class.php');
#include '../../../inc/api.class.php';

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
      $this->request_uri  = $_SERVER['REQUEST_URI'];
      $this->verb         = $_SERVER['REQUEST_METHOD'];
      $path_info          = (isset($_SERVER['PATH_INFO'])) ? str_replace("api/", "", trim($_SERVER['PATH_INFO'], '/')) : '';
      $this->url_elements = explode('/', $path_info);

      // retrieve requested resource
      $resource      = trim(strval($this->url_elements[0]));
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
         $this->session_write = (bool)$this->parameters['session_write'];
      }

      // inline documentation (api/)
      if ($is_inline_doc) {
         return $this->inlineDocumentation("plugins/reservation/apirest.md");
      } else if ($resource === "initSession") {
         // ## DECLARE ALL ENDPOINTS ##
         // login into glpi
         $this->session_write = true;
         return $this->returnResponse($this->initSession($this->parameters));
      } else if ($resource === "killSession") {
         $this->retrieveSession($this->parameters);
         // logout from glpi
         $this->session_write = true;
         return $this->returnResponse($this->killSession());
      } else if ($resource === "checkoutItem") {
         return $this->reservationsRoute();
      } else if ($resource === "categories") {
         return $this->categoriesRoute();
      } else if ($resource === "items") {
         return $this->itemsRoute();
      }

      $this->messageLostError();
   }

   private function itemsRoute() {
      $duration          = $this->getDuration();
      $code              = 200;
      $additionalheaders = [];

      if (!isset($this->parameters['input'])) {
         $this->messageBadArrayError();
      }
      // if duration is passed by query string, add it into input parameter
      $input = (array)($this->parameters['input']);
      if (($duration > 0) && !isset($input['duration'])) {
         $this->parameters['input']->duration = $duration;
      }

      $time = time();
      $now = date("Y-m-d H:i:s", $time);

      switch ($this->verb) {
         default:
            $code = 400;
            $response = "Use GET or PUT request !";
            break;
         case "PUT":
            break;
         case "GET":
            break;
      }
      return $this->returnResponse($response, $code, $additionalheaders);
   }

   private function categoriesRoute()
   {
      $code              = 200;
      $additionalheaders = [];

      switch ($this->verb) {
         default:
            $code = 400;
            $response = "Use GET request !";
            break;
         case "GET":
            $categories = PluginReservationCategory::getCategories();
            if (count($categories) == 0) {
               $response = ["success" => false, 'message' => __("There is no category", "reservation")];
               break;
            }
            $response = ["payload" => $categories, "success" => true, "message" => "OK"];
            break;
      }
      return $this->returnResponse($response, $code, $additionalheaders);
   }

   private function reservationsRoute()
   {
      $id                = $this->getId();
      $additionalheaders = [];
      $code              = 200;

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
            $input = (array)($this->parameters['input']);
            if (($id > 0) && !isset($input['id'])) {
               $this->parameters['input']->id = $id;
            }

            $time = time();
            $now = date("Y-m-d H:i:s", $time);
            $current_reservation = PluginReservationReservation::getAllReservations(["`begin` <= '" . $now . "'", "`end` >= '" . $now . "'", "reservationitems_id = " . $this->parameters['input']->id]);
            $reservationitems = new ReservationItem();
            $reservationitems->getFromDB($this->parameters['input']->id);
            $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');
            #Toolbox::logInFile('reservations_plugin', "API : ".$this->parameters['input']->id. " <=> ".$reservationitems->fields['id']."\n", $force = false);
            if (count($current_reservation) == 0) {
               $response = [$this->parameters['input']->id => $item->fields['name'], "success" => false, 'message'    => __("Item is not currently reserved", "reservation")];
               break;
            }

            $reservation_id = $current_reservation[0]['reservations_id'];
            PluginReservationReservation::checkoutReservation($reservation_id);
            $response = [$input['id'] => $item->fields['name'], "success" => true, "message" => "OK"];

            break;
      }
      return $this->returnResponse($response, $code, $additionalheaders);
   }

   private function getId()
   {
      $id = isset($this->url_elements[1]) && is_numeric($this->url_elements[1])
         ? intval($this->url_elements[1])
         : false;

      $additional_id = isset($this->url_elements[3]) && is_numeric($this->url_elements[3])
         ? intval($this->url_elements[3])
         : false;

      if ($additional_id || isset($this->parameters['parent_itemtype'])) {
         $this->parameters['parent_id'] = $id;
         $id = $additional_id;
      }

      return $id;
   }

   private function getDuration()
   {
      $duration = isset($this->url_elements[1]) && is_numeric($this->url_elements[1])
         ? intval($this->url_elements[1])
         : false;

      return $duration;
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
         $parameters['login']    = $_SERVER['PHP_AUTH_USER'];
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
