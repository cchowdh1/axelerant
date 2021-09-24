<?php

namespace Drupal\gym_webservices\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\rest\ResourceResponse;
use \DateTimeZone;
use \DateTime;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "post_rest_user_attendence",
 *   label = @Translation("User Attendance post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/user-attendance",
 *       "https://www.drupal.org/link-relations/create" = "/user-attendance"
 *   }
 * )
 */

class CreateAttendance extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager object.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The date formatter service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
  EntityManagerInterface $entityManager,
  QueryFactory $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->entityManager = $entityManager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('ccms_rest'),
      $container->get('current_user'),
    $container->get('entity.manager'),
    $container->get('entity.query')
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    // Use current user after pass authentication to validate access.
    // if (!$this->currentUser->hasPermission('access content')) {
      // throw new AccessDeniedHttpException();
    // }
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    if ((count($data) > 0)  && array_key_exists('trainee', $data) &&  array_key_exists('type', $data) &&  array_key_exists('attendance', $data)) {
      if ($this->currentUser->id()) {
       $uid = $this->currentUser->id();
      }
	  //Check trainee
	  $ids = $this->entityQuery->get('user')
			->condition('field_trainee', $data['trainee'])
			->condition('uid', $uid)
			->range(0, 1)
			->execute();
	  if(empty($ids)) {
		$result['errorMessage'] = "Trainee details is incorrect.";
        $response = new ResourceResponse($result, 406);
        $response->addCacheableDependency($result);
	    return $response;
	  }
	  if($data['type'] != 'add' && $data['type'] != 'delete') {
		$result['errorMessage'] = "Operation type is incorrect";
        $response = new ResourceResponse($result, 406);
        $response->addCacheableDependency($result);
	    return $response;  
	  }
      //$user_storage = $this->entityManager->getStorage('user');
      //$user_profile = ($uid > 0)?$user_storage->load($data['trainee']):"";
 
	 /* if(empty($user_profile)) {
	  $result['errorMessage'] = "Trainee details is incorrect.";
	  $response = new ResourceResponse($result);
		   $response->addCacheableDependency($result);
		   return $response;
	 } */
	 /* $trainee_ids = $this->entityQuery->get('user')
			->condition('field_attendance', $data['attendance'])
			->condition('uid', $data['trainee'])
			->range(0, 1)
			->execute(); */
	 $database = \Drupal::database();
	 $query = $database->select('user__field_attendance', 'u');
     $query->condition('u.entity_id', $data['trainee']);
     $query->condition('u.field_attendance_value', $data['attendance']);
     $query->fields('u', ['delta']);
     $delta = $query->execute()->fetchField();
	 if($data['type'] == 'add') {
	  if(!empty($delta)) {
	   $result['errorMessage'] = "Attendance already exists";
       $response = new ResourceResponse($result, 400);
       $response->addCacheableDependency($result);
	   return $response;
	  }
	  else {
	   $user_storage = $this->entityManager->getStorage('user');
       $user_profile = ($uid > 0)?$user_storage->load($data['trainee']):"";
	   $user_profile->field_attendance[] = $data['attendance'];
	   $user_profile->save();
	   $result['statusMessage'] = "Attendance added successfully";
	  }
	 }
	 
	 if($data['type'] == 'delete') {
	  if(empty($delta)) {
	   $result['errorMessage'] = "Attendance does not exists";
       $response = new ResourceResponse($result, 404);
       $response->addCacheableDependency($result);
	   return $response;
	  }
	  else {
	   $user_storage = $this->entityManager->getStorage('user');
       $user_profile = ($uid > 0)?$user_storage->load($data['trainee']):"";
	   $user_profile->field_attendance->removeItem($delta);
	   $user_profile->save();
	   $result['statusMessage'] = "Attendance removed successfully";
	  }
	 }
	 
     $response = new ResourceResponse($result, 201);
     $response->addCacheableDependency($result);
     return $response;
    }
    else {
      $result['errorMessage'] = "Invalid parameter provided.";
      $response = new ResourceResponse($result, 400);
      $response->addCacheableDependency($result);
	  return $response;
    }
  }

}