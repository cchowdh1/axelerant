<?php

namespace Drupal\gym_webservices\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "read_notification_get_service",
 *   label = @Translation("Read Notifications Rest Api"),
 *   uri_paths = {
 *     "canonical" = "/read-notification",
 *       "https://www.drupal.org/link-relations/create" = "/read-notification"
 *   }
 * )
 */
 
class ReadNotification extends ResourceBase {
   /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;
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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityManagerInterface $entityManager,
    QueryFactory $entity_query,
    Connection $connection ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->entityManager = $entityManager;
    $this->entityQuery = $entity_query;
	$this->connection = $connection;
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
	  $container->get('database'),
    );
  }

  /**
   * Responds to GET requests.
   */
  public function post($data) {
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    if ($this->currentUser->id() && !empty($data['nid']) && is_numeric($data['nid'])) {
	 $uid = $this->currentUser->id();
	 
	 $node_data = $this->entityManager->getStorage('node')->load($data['nid']);
	 
	 if($node_data->field_user_to_notify->getValue()[0]['target_id'] != $uid) {
	  $result['errorMessage'] = "You do not have access in this notification";
      $response = new ResourceResponse($result, 403);
      $response->addCacheableDependency($result);
	  return $response;
	 }
	 
	 $node_data->set('field_notification_read_status',1);
	 $node_data->save();
	 //Update Read Status
	 /* $sql =
	  "UPDATE node__field_notification_read_status as rs 
	  join node__field_user_to_notify as u on rs.entity_id = u.entity_id
	  set rs.field_notification_read_status_value = 1
	  where u.field_user_to_notify_target_id = :uid and rs.entity_id = :nid";

	 $args = array(':uid' => $uid, ':nid' => $data['nid']);
     $this->connection->query($sql, $args);
	 
	 $sql =
	  "UPDATE node_revision__field_notification_read_status as rs 
	  join node__field_user_to_notify as u on rs.entity_id = u.entity_id
	  set rs.field_notification_read_status_value = 1
	  where u.field_user_to_notify_target_id = :uid and rs.entity_id = :nid";

     $this->connection->query($sql, $args); */
	 
	 $result['statusMessage'] = "User read all the notificationss succesfully";
     $response = new ResourceResponse($result, 200);
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