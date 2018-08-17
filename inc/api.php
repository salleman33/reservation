<?php

#include '../../../inc/includes.php';
#include (__DIR__ . '/../../../inc/includes.php');
#include (__DIR__ . '/../../../inc/api.class.php');
#include '../../../inc/api.class.php';

class PluginReservationApi extends API {

  protected $request_uri;
  protected $url_elements;
  protected $verb;
  protected $parameters;
  protected $debug = 0;
  protected $format = "json";

  public static function getTypeName($nb=0) {
    return __('Reservation Plugin Rest API');
  }

  public function call() {
    Toolbox::logInFile('reservations_plugin', "TEST API \n", $force = false);
    return $this->inlineDocumentation("plugins/reservation/apirest.md");
  }

  public function parseIncomingParams($is_inline_doc = false) {
    $parameters = [];

    // first of all, pull the GET vars
    if (isset($_SERVER['QUERY_STRING'])) {
      parse_str($_SERVER['QUERY_STRING'], $parameters);
    }
    // now how about PUT/POST bodies? These override what we got from GET
    $body = trim($this->getHttpBody());
    if (strlen($body) > 0 && $this->verb == "GET") {
      // GET method requires an empty body
      $this->returnError("GET Request should not have json payload (http body)", 400,
	"ERROR_JSON_PAYLOAD_FORBIDDEN");
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
	$this->returnError("JSON payload seems not valid", 400, "ERROR_JSON_PAYLOAD_INVALID",
	  false);
      }
      $this->format = "json";
    } else if (strpos($content_type, "multipart/form-data") !== false) {
      if (count($_FILES) <= 0) {
	// likely uploaded files is too big so $_REQUEST will be empty also.
	// see http://us.php.net/manual/en/ini.core.php#ini.post-max-size
	$this->returnError("The file seems too big", 400,
	  "ERROR_UPLOAD_FILE_TOO_BIG_POST_MAX_SIZE", false);
      }
      // with this content_type, php://input is empty... (see http://php.net/manual/en/wrappers.php.php)
      if (!$uploadManifest = json_decode(stripcslashes($_REQUEST['uploadManifest']))) {
	$this->returnError("JSON payload seems not valid", 400, "ERROR_JSON_PAYLOAD_INVALID",
	  false);
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
	  $headers[str_replace(' ', '-',
	    ucwords(strtolower(str_replace('_', ' ',
	    substr($server_key, 5)))))] = $server_value;
	}
      }
    }
    // try to retrieve basic auth
    if (isset($_SERVER['PHP_AUTH_USER'])
      && isset($_SERVER['PHP_AUTH_PW'])) {
      $parameters['login']    = $_SERVER['PHP_AUTH_USER'];
      $parameters['password'] = $_SERVER['PHP_AUTH_PW'];
    }
    // try to retrieve user_token in header
    if (isset($headers['Authorization'])
      && (strpos($headers['Authorization'], 'user_token') !== false)) {
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

  public function returnResponse($response, $httpcode = 200, $additionalheaders = []) {
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


  public function manageUploadedFiles() {
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

