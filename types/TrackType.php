<?php

namespace Types;

use App\Models\Author;
use App\Models\Module;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/* 
  An important future note:
  The types that are used only once within the project are called directly
  without storing them in the type registry.

  Since the 'TrackType' was the only one to cause issues on running the first test along with the 
  subsequent tests because it is used more than once inthe schema, this type will be the only one to be
  added to the TypeRegistry.

  I will update the code if I encountered any errors on writing the rest of the resolvers tests.
*/

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
          // this type is used once, so there is no need to add it to the type registry
          'type' => new AuthorType(),
          // the $rootValue passed is the result of executing the resolve function for the TrackType
          'resolve' => function($rootValue) {
            return Author::find($rootValue['authorId']);
          }
        ],
        'modules' => [
          'type' => Type::listOf(new ModuleType()),
          'resolve' => fn($parent) => Module::all($parent['id'])
        ]
      ]
      ];
    parent::__construct($config);
  }
}
