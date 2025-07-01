<?php

namespace Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AuthorType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'id' => Type::id(),
      'name' => Type::string(),
      'photo' => Type::string()
      ]
      ];
    parent::__construct($config);
  }
}
