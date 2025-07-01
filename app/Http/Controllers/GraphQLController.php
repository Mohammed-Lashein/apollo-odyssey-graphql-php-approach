<?php

namespace Src\Controller;

use App\Models\Track;
use App\Models\Author;
use App\Models\Module;
use ErrorException;
use GraphQL\Error\DebugFlag;
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


class ModuleType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'id' => Type::id(),
        'title' => Type::string(),
        'length' => Type::int()
      ],
    ];
    parent::__construct($config);
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
        'numberOfViews' => Type::int(),
        'description' => Type::string(),
        'author' => [
          'type' => new AuthorType(),
          // the $rootValue passed is the result of executing the resolve function for the TrackType
          'resolve' => function($rootValue) {
            return Author::find($rootValue['authorId']);
          }
        ],
        'modules' => [
          'type' => Type::listOf(new ModuleType()),
          // 'type' => new ModuleType(),
          'resolve' => fn($parent) => Module::all($parent['id'])
        ]
      ]
      ];
    parent::__construct($config);
  }
}

class IncrementTrackNumberOfViewsResponse extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'code' => Type::int(),
        'success' => Type::boolean(),
        'message' => Type::string(),
        // 'track' => new Track(), // Wrong! will throw an error because webonyx-gql expects field config to be an array
        'track' => [
          /* Another error! Object of type App\Models\Track is not callable. 
          Reason of the error: The type you provide to the type key should be a class extending ObjectType
          provided by the library, not the Track model we created! 
          */
          // 'type' => new Track(),
          'type' => new TrackType(),
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
        ],
        'track' => [
          'type' => new TrackType(),
          'resolve' => function($_ , $args) {
            if (!isset($args['trackId'])) {
                throw new \Exception("trackId was not provided in the query arguments.");
            }
            return Track::find($args['trackId']);
          },
          'args' => [
            'trackId' => Type::id()
          ],
        ]
      ]
    ]);

    $mutationType = new ObjectType([
      'name' => 'Mutation',
      'fields' => [
        'incrementTrackNumberOfViews' => [
          'type' => new IncrementTrackNumberOfViewsResponse(),
          'args' => [
            'id' => Type::id(),
          ],
          /* The solution by checking against the track if it is null or not */
          // 'resolve' => function($_, $args) {
          //     $track = Track::incrementNumberOfViewsForTrackWithId($args['id']);
          //     // var_dump($track);//null
          //     if(is_null($track)) {
          //       return [
          //       'code' => 500,
          //       'success' => false,
          //       'message' => 'An error occurred. Please try again later',
          //       'track' => $track
          //     ];
          //     }

          //     return [
          //       'code' => 200,
          //       'success' => true,
          //       'message' => "Hooray! The numberOfViews for track of id: {$args['id']} were updated successfully",
          //       'track' => null
          //     ];
            
          //   }

          /* Modifying the default error handler and restoring it after the
          resolver function exection*/
          'resolve' => function($_, $args) {
            $prev_handler = set_error_handler(function($errno, $errstring, $errfile, $errline) {
              throw new ErrorException($errstring, 0, $errno, $errfile, $errline);
            });
            try {
              $track = Track::incrementNumberOfViewsForTrackWithId($args['id']);
              // var_dump($track);//null
              return [
                'code' => 200,
                'success' => true,
                'message' => "Hooray! The numberOfViews for track of id: {$args['id']} were updated successfully",
                'track' => $track
              ];
            } catch(\Throwable $th) {
              return [
                'code' => 500,
                'success' => false,
                'message' => 'An error occurred. Please try again later',
                'track' => null
              ];
            } finally {
              restore_error_handler();
            }
          }
        ]
      ]
    ]);

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
