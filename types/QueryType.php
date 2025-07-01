<?php

namespace Types;

use App\Models\Track;
use Core\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class QueryType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'tracksForHome' => [
          'type' => Type::listOf(TypeRegistry::type(TrackType::class)),
          'resolve' => fn() => Track::all()
        ],
        'track' => [
          'type' => TypeRegistry::type(TrackType::class),
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
    ];
    parent::__construct($config);
  }
}