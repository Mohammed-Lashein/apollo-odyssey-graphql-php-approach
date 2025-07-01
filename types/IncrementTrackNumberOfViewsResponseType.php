<?php

namespace Types;

use Core\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Types\TrackType;

// This naming convention was mentioned in liftoff-4 in apollo odyssey course
class IncrementTrackNumberOfViewsResponseType extends ObjectType {
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
          'type' => TypeRegistry::type(TrackType::class),
        ]
      ]
    ];
    parent::__construct($config);
  }
}
