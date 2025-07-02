<?php

namespace Tests\Utils;

use GraphQL\Type\Schema;
use Types\MutationType;
use Types\QueryType;


class SchemaFactory {
  public static function build() {
    $queryType = new QueryType();
    $mutationType = new MutationType();
    return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);
  }
}