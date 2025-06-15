<?php

namespace Src\Controller;

use App\Models\Track;
use App\Models\Author;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

class AuthorType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'id' => Type::id(),
      'name' => Type::string(),
      'photo' => Type::string()
      ]
      ];
    parent::__construct(
      $config
    );
  }
}

class TrackType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'id' => Type::id(),
        'title' => Type::string(),
        'thumbnail' => Type::string(),
        'length' => Type::int(),
        'modulesCount' => Type::int(),
        'author' => [
          'type' => new AuthorType(),
          // the $rootValue passed is the result of executing the resolve function for the TrackType
          'resolve' => function($rootValue) {
            return Author::find($rootValue['authorId']);
          }
        ]
      ]
      ];
    parent::__construct($config);
  }
}

class GraphQLController {
  public static function handle() {
    $queryType = new ObjectType([
      'name' => 'Query', 
      'fields' => [
        'tracksForHome' => [
          'type' => Type::listOf(new TrackType()),
          'resolve' => fn() => Track::all()
        ]
      ]
    ]);

    $schema = new Schema([
      'query' => $queryType
    ]);

    $requestBody = file_get_contents('php://input'); // Raw JSON string from HTTP request
    $parsedBody = json_decode($requestBody, true, 10); // Associative array decoded from JSON
    $queryString = $parsedBody['query']; // The actual GraphQL query string

    $result = GraphQL::executeQuery($schema, $queryString, null, null, null);

    header('Content-Type: application/json');
    echo json_encode($result);

  }
}
