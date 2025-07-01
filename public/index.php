<?php

require __DIR__ . '/../vendor/autoload.php';

$your_domain_name = 'http://localhost:5173';
header("Access-Control-Allow-Origin: $your_domain_name");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// For testing only, never in production!
// ini_set('display_errors', 1);

// disabling error reporting is important so that errors aren't sent to the client in the graphql response
error_reporting(0);
ini_set('display_errors', 0);

use App\Http\Controllers\GraphQLController;

/* 
Since I am using xampp, I wrote the request uri to be like so . 
  If you are using php built in server, then you would need to test against '/graphql'
*/
$base_url = '/apollo-gql-course-api-to-publish';

if(
  // airbnb style
  // https://github.com/airbnb/javascript?tab=readme-ov-file#control-statements
  $_SERVER['REQUEST_METHOD'] === 'POST' 
  && $_SERVER['REQUEST_URI'] === "$base_url/graphql"
  ) {
  GraphQLController::handle();
}

