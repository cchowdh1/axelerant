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
use Drupal\user\UserAuth;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "get_rest_user_logout",
 *   label = @Translation("User Logout get rest resource"),
 *   uri_paths = {
 *     "canonical" = "/user-logout",
 *       "https://www.drupal.org/link-relations/create" = "/user-logout"
 *   }
 * )
 */
class UserLogout extends ResourceBase {
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
   * @param \Drupal\user\UserAuth $authUser
   *   To authenticate user.
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
    $container->get('entity.query'),
    );
  }

  /**
   * Responds to GET requests.
   */
  public function get() {
    // Use current user after pass authentication to validate access.
    $result['statusMessage'] = "error";
    $result['errorMessage'] = null;
    //$config = \Drupal::config('webservice.common.settings');
    $uid = $this->currentUser->id();
	$user_storage = $this->entityManager->getStorage('user');
	$user_profile = ($uid > 0)?$user_storage->load($uid):"";
 
	if(empty($user_profile)) {
	  $result['errorMessage'] = "Invalid User";
	  $response = new ResourceResponse($result, 403);
		$response->addCacheableDependency($result);
		return $response;
	}
 
	if(user_is_blocked($user_profile->getUsername())) {
	 $result['blockstatus'] = true;
	 $result['errorMessage'] = "User is blocked";
	 $response = new ResourceResponse($result, 403);
	 $response->addCacheableDependency($result);
	 return $response;
	}
 
    $collector = \Drupal::service('simple_oauth.expired_collector');
    $collector->deleteMultipleTokens($collector->collectForAccount($user_profile));
 
    $result['statusMessage'] = "User is successfully logged out";
    $response = new ResourceResponse($result, 200);
    $response->addCacheableDependency($result);
    return $response;    
    
  }

}
