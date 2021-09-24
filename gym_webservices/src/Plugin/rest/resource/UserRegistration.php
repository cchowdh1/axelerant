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
use Drupal\user\Entity\User;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "post_rest_gym_user_registration",
 *   label = @Translation("Gym User Registration post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/gym-user-registration",
 *       "https://www.drupal.org/link-relations/create" = "/gym-user-registration"
 *   }
 * )
 */
 
class UserRegistration extends ResourceBase {
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
   $result['statusMessage'] = "error";
   $result['errorMessage'] = "na";
   if ((count($data) > 0) && array_key_exists('email', $data) && array_key_exists('password', $data) && array_key_exists('phone', $data) && array_key_exists('first_name', $data) && array_key_exists('last_name', $data) && array_key_exists('address', $data) && array_key_exists('gender', $data) && array_key_exists('role', $data) ) {
	$validation_error = [];
	if(empty($data['email'])) {
	 $validation_error['email'] = 'Please provide email address';
	}
	if(!empty($data['email']) && !(\Drupal::service('email.validator')->isValid($data['email']))) {
	 $validation_error['email'] = 'Please provide a valid email address';
	}
	//existing email address
	$email_id = $this->entityQuery->get('user')
		->condition('mail', $data['email'])
		->range(0, 1)
		->execute();
    if(!empty($email_id)) {
	 $validation_error['email'] = 'This email address is alreday registered with us';
	}
	if(empty($data['phone'])) {
	 $validation_error['phone'] = 'Please provide Phone number';
	}
	if(!is_numeric($data['phone']) || strlen($data['phone']) != 10) {
	 $validation_error['phone'] = 'Please provide valid Phone number';
	}
	//existing phone number
	$phone_no = $this->entityQuery->get('user')
		->condition('field_mobile', $data['phone'])
		->range(0, 1)
		->execute();
	if(!empty($phone_no)) {
	 $validation_error['phone'] = 'This phone number is alreday registered with us';
	}
	if(empty($data['first_name'])) {
	 $validation_error['first_name'] = 'Please provide First Name';
	}
	if(empty($data['password'])) {
	 $validation_error['password'] = 'Please provide password';
	}
	if(empty($data['last_name'])) {
	 $validation_error['last_name'] = 'Please provide Last Name';
	}
	if(empty($data['address'])) {
	 $validation_error['address'] = 'Please provide address';
	}
	if(empty($data['gender'])) {
	 $validation_error['gender'] = 'Please provide gender';
	}
	if(empty($data['role'])) {
	 $validation_error['role'] = 'Please provide role';
	}
	
	if(count($validation_error) == 0) {
		$user = User::create();
		$user->setPassword($data['password']);
		$user->enforceIsNew();
		$user->setEmail($data['email']);
		$user->setUsername($data['phone']);
		$user->addRole($data['role']);
		$user->set('field_api_email',$data['email']);
		$user->set('field_first_name',$data['first_name']);
		$user->set('field_last_name',$data['last_name']);
		$user->set('field_mobile',$data['phone']);
		$user->set('field_gender',$data['gender']);
		$user->set('field_address',$data['address']);
		$user->set('status',1);
		if(!empty($data['about_me'])) {
		 $user->set('field_about_me',$data['about_me']);
		}
		$user->save();
	 
	 $result['statusMessage'] = "User successfully created.";
     $response = new ResourceResponse($result,201);
     $response->addCacheableDependency($result);
     return $response;
	}
	else {
	 $result['errorMessage'] = $validation_error;
	 $response = new ResourceResponse($result,406);
	 $response->addCacheableDependency($result);
	 return $response;
	}
	
   }
   else {
      //$result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get("basic_error")]);
      //$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("wrong_parameters")]);
      $result['errorMessage'] = "Invalid parameter provided.";
      $response = new ResourceResponse($result,400);
      $response->addCacheableDependency($result);
	  return $response;
   }
  
  
  }
}