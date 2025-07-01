<?php

namespace Types;

use App\Models\Track;
use ErrorException;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class MutationType extends ObjectType {
  public function __construct() {
    $config = [
      'fields' => [
        'incrementTrackNumberOfViews' => [
          'type' => new IncrementTrackNumberOfViewsResponseType(),
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
    ];

    parent::__construct($config);
  }
}