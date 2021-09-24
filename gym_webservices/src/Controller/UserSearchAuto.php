<?php

namespace Drupal\gym_webservices\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Defines a route controller for entity autocomplete form elements.
 */
class UserSearchAuto extends ControllerBase {

  /**
   * Handler for autocomplete request to select Program.
   */
  public function userAutocomplete(Request $request, $type) {
    $connection = \Drupal::database();
	if($type == 'email') {
	 $auto_query = $connection->select('users_field_data', 'ufd')
      ->fields('ufd', ['mail']);
	 $auto_query->join('user__roles', 'ur', 'ur.entity_id = ufd.uid');
     $auto_query->condition('ur.roles_target_id', 'member');
     $auto_query->condition('ufd.mail', $connection->escapeLike($request->query->get('q')) . '%', 'LIKE');
     $output_query = $auto_query->execute();
	 while ($final_data = $output_query->fetchObject()) {
       $results[] = [
        'value' => $final_data->mail,
        'label' => $final_data->mail,
      ];
     }
	}
	if($type == 'phone') {
	 $auto_query = $connection->select('users_field_data', 'ufd')
      ->fields('ufd', ['name']);
	 $auto_query->join('user__roles', 'ur', 'ur.entity_id = ufd.uid');
     $auto_query->condition('ur.roles_target_id', 'member');
     $auto_query->condition('ufd.name', $connection->escapeLike($request->query->get('q')) . '%', 'LIKE');
     $output_query = $auto_query->execute();
	 while ($final_data = $output_query->fetchObject()) {
       $results[] = [
        'value' => $final_data->name,
        'label' => $final_data->name,
      ];
     }
	}
    
    return new JsonResponse($results);
  }
}