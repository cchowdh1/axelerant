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

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "post_rest_edit_profile",
 *   label = @Translation("Edit Profile post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/edit-profile",
 *       "https://www.drupal.org/link-relations/create" = "/edit-profile"
 *   }
 * )
 */
class ProfileEdit extends ResourceBase {
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
    //$config = \Drupal::config('webservice.common.settings');
    if ((count($data) > 0) /* && array_key_exists('email', $data) */) {
      if ($this->currentUser->id() /* && $this->currentUser->id() == $data['userId'] */) {
       $uid = $this->currentUser->id();
      }
      $user_storage = $this->entityManager->getStorage('user');
      $user_profile = ($uid > 0)?$user_storage->load($uid):"";
 
	 if(empty($user_profile)) {
	  //$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('valid_uid')]);
	  $result['errorMessage'] = "Please provide a valid user id";
	  $response = new ResourceResponse($result, 403);
		   $response->addCacheableDependency($result);
		   return $response;
	 }
     /* // Save User
	 if(strtolower($data['lang']) != "ar" && strtolower($data['lang']) != "en") {
	  $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('wrong_language')]);
	  $response = new ResourceResponse($result);
		   $response->addCacheableDependency($result);
		   return $response;
	 }
	 else {
	  $user_profile = $user_profile->getTranslation(strtolower($data['lang']));
	 } */
 
	 /* if(array_key_exists('phoneNo', $data)) {
	  if(!is_numeric($data['phoneNo'])) {
		$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('valid_phone_no')]);
		$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
	  }
	  else {
	   $user_profile->set('field_mobile_phone',$data['phoneNo']);
	  }
	 } */
	 /* if(array_key_exists('profileVisibilityKey', $data)) {
	  $profileVisibilityKey = "";
	  $profileVisibilityKey = ($data['profileVisibilityKey'] == "public")? 1 : (($data['profileVisibilityKey'] == "private")? 0 : "");
	  if($profileVisibilityKey !== "") {
	   $user_profile->set('field_visibility',$profileVisibilityKey);
	  }
	  if($profileVisibilityKey === "") {
		$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('valid_profile_visibility')]);
		$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
		return $response;
	  }
	 } */
	 /* if(array_key_exists('email', $data)) {
	  $ids = $this->entityQuery->get('user')
			->condition('mail', $data['email'])
			->condition('uid', $uid,'<>')
			->range(0, 1)
			->execute();
	  if(!empty($ids)) {
		//$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('duplicate_mail')]);
		$result['errorMessage'] = "Please enter a unique email address";
		$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
	  }
	  if(\Drupal::service('email.validator')->isValid($data['email'])){
		$user_profile->set('mail',$data['email']);
		//$user_profile->set('name',$data['email']);
	  }
	  else {  
		//$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('valid_mail')]);
		$result['errorMessage'] = "Please enter a valid Email Address";
		$response = new ResourceResponse($result);
		$response->addCacheableDependency($result);
		return $response;
	  }
	 } */
	 if(array_key_exists('first_name', $data)) {
	  if(empty($data['first_name'])){
		//$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('empty_first_name')]);
		$result['errorMessage'] = "First name cannot be empty";
		$response = new ResourceResponse($result, 403);
			$response->addCacheableDependency($result);
			return $response;
	  }
	  else {  
	   $user_profile->set('field_first_name',$data['first_name']);
	  }
	 }
	 if(array_key_exists('last_name', $data)) {
	  if(empty($data['last_name'])){
		//$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('empty_last_name')]);
		$result['errorMessage'] = "Last name cannot be empty";
		$response = new ResourceResponse($result, 403);
			$response->addCacheableDependency($result);
			return $response;
	  }
	  else {  
	   $user_profile->set('field_last_name',$data['last_name']);
	  }
	 }
	 if(array_key_exists('gender', $data)) {
	  if(empty($data['gender'])){
		$result['errorMessage'] = "Gender cannot be empty";
		$response = new ResourceResponse($result, 403);
			$response->addCacheableDependency($result);
			return $response;
	  }
	  else {  
	   $user_profile->set('field_gender',$data['gender']);
	  }
	 }
	 if(array_key_exists('address', $data)) {
	  if(empty($data['address'])){
		$result['errorMessage'] = "Address cannot be empty";
		$response = new ResourceResponse($result, 403);
			$response->addCacheableDependency($result);
			return $response;
	  }
	  else {  
	   $user_profile->set('field_address',$data['address']);
	  }
	 }
	 if(array_key_exists('about_me', $data)) {
	  if(!empty($data['about_me'])) {
	   $user_profile->set('field_about_me',$data['about_me']);
	  }
	 }
	 $user_profile->save();
     //$result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get('profile_success_msg')]);
     $result['statusMessage'] = "Profile is successfully saved.";
     $response = new ResourceResponse($result, 201);
     $response->addCacheableDependency($result);
     return $response;
    }
    else {
      //$result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get("basic_error")]);
      //$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("wrong_parameters")]);
      $result['errorMessage'] = "Invalid parameter provided.";
      $response = new ResourceResponse($result, 400);
      $response->addCacheableDependency($result);
	  return $response;
    }
  }

}
