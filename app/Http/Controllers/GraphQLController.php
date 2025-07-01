<?php

namespace App\Http\Controllers;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Types\MutationType;
use Types\QueryType;

class GraphQLController {
  public static function handle() {
    $queryType = new QueryType();

    $mutationType = new MutationType();

    $schema = new Schema([
      'query' => $queryType,
      'mutation' => $mutationType
    ]);

    $requestBody = file_get_contents('php://input'); // Raw JSON string from HTTP request
    $parsedBody = json_decode($requestBody, true, 10); // Associative array decoded from JSON
    // var_dump($parsedBody);
    $queryString = $parsedBody['query']; // The actual GraphQL query string
    $queryVariables = $parsedBody['variables'];

    $result = GraphQL::executeQuery($schema, $queryString, null, null, $queryVariables);
    $result = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);

    header('Content-Type: application/json');
    echo json_encode($result);
  }
}
