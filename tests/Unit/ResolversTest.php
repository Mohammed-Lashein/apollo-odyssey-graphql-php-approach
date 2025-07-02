<?php

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use Tests\Utils\SchemaFactory;

it('allows us to query the list of available tracks', function() {
  $query = "
    query {
      tracksForHome {
        id
        title
        numberOfViews
        thumbnail
      }
  }";

  $res = GraphQL::executeQuery(SchemaFactory::build(), $query);
  $res = $res->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
  // var_dump($res);
  // print_r($res);

  expect($res)->toBeArray();
  expect($res['data']['tracksForHome'][0])->toHaveKeys(['id', 'title', 'numberOfViews', 'thumbnail',]);
});

it("allows us to query a specific track", function() {
  $query = '
    query GET_TRACK($id: ID) {
	  track(trackId: $id) {
	  	id
	  	title
	  	numberOfViews
	  }
  }';

$variables = ['id' => 'c_1'];

  $res = GraphQL::executeQuery(SchemaFactory::build(), $query, null, null, $variables);
  $res = $res->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
  // var_dump($res);
  // print_r($res);

  expect($res)->toBeArray();
  expect($res['data']['track'])->toHaveKeys(['id', 'title', 'numberOfViews']);
});

it("allows us to increment track's numberOfViews", function() {
  $query = '
    query GET_TRACK($id: ID) {
	  track(trackId: $id) {
	  	id
	  	title
	  	numberOfViews
	  }
  }';
  $variables = ['id' => 'c_1'];
  $res = GraphQL::executeQuery(SchemaFactory::build(), $query, null, null, $variables);
  $res = $res->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
  // echo "before the mutation!";
  // print_r($res);
  $trackNumberOfViewsBeforeMutation = $res['data']['track']['numberOfViews'];

  $mutation = '
  mutation increment_TRACK_view($id: ID){
    incrementTrackNumberOfViews(id: $id) {
        code
        success
        message
        track {
            numberOfViews
            id
        }
    }
}
';
  $res = GraphQL::executeQuery(SchemaFactory::build(), $mutation, null, null, $variables);
  $res = $res->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);
  // echo "after the mutation!";
  // print_r($res);
  $trackNumberOfViewsAfterMutation = $res['data']['incrementTrackNumberOfViews']['track']['numberOfViews'];

  expect($trackNumberOfViewsAfterMutation)->toBeGreaterThan($trackNumberOfViewsBeforeMutation);
});