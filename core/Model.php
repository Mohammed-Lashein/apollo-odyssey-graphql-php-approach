<?php

namespace Core;

use Exception;

class Model {
  protected static $basePath = "https://odyssey-lift-off-rest-api.herokuapp.com/";
  protected static function get($endpoint) {
    $url = static::$basePath . $endpoint;

    $res =  file_get_contents($url);

    if(!$res) {
      throw new Exception("Failed to fetch data from url: $url");
    }
    return json_decode($res, true);
  }

  protected static function patch($endpoint) {
    $url = static::$basePath . $endpoint;

    $context_options = [
      'http' => [
        'method' => 'PATCH',
        'header' => 'Content-Type: application/json',
        'content' => '',
      ]
      ];
    $context = stream_context_create($context_options);

    $res = @file_get_contents($url, false, $context);
    // the var_dumps aren't displayed in the response from the api on postman 
    /* Wrong, they finally got displayed */
    // var_dump('this is patch res!');
    // var_dump(json_decode($res)); // null
    return json_decode($res, true);
  }
}
