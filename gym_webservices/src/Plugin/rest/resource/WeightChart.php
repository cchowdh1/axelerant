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
 *   id = "post_rest_upadate_weight_chart",
 *   label = @Translation("Update Weight Chart post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/update-weight-chart",
 *       "https://www.drupal.org/link-relations/create" = "/update-weight-chart"
 *   }
 * )
 */
 
class WeightChart extends ResourceBase {
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
    if ($this->currentUser->id() && array_key_exists('trainee', $data) && array_key_exists('field_weight_date', $data) && array_key_exists('field_before_weight', $data)) {
	 if(empty($data['field_weight_date']) || empty($data['field_before_weight'])) {
	  $result['errorMessage'] = "Date or Before weight is missing.";
      $response = new ResourceResponse($result, 403);
      $response->addCacheableDependency($result);
	  return $response;
	 }
	 $uid = $this->currentUser->id();
	 $trainee = $data['trainee'];
	 //Check trainee
	 $ids = $this->entityQuery->get('user')
		->condition('field_trainee', $trainee)
		->condition('uid', $uid)
		->range(0, 1)
		->execute();
	  if(empty($ids)) {
		$result['errorMessage'] = "Trainee and Trainer details are incorrect.";
        $response = new ResourceResponse($result, 403);
        $response->addCacheableDependency($result);
	    return $response;
	  }
	 //Check date
	  $nids = $this->entityQuery->get('node')
		->condition('field_member_details', $data['trainee'])
		->condition('field_weight_date', $data['field_weight_date'])
		//->range(0, 1)
		->execute();
		/* 
		echo "<pre>"; print_r($nids);
		
		print_r(array_values($nids));exit; */
	  if(count($nids) > 1) {
	   $result['errorMessage'] = "More than one entry found for single date.";
       $response = new ResourceResponse($result, 403);
       $response->addCacheableDependency($result);
	   return $response;
	  }
	  if(count($nids) == 1) {
	   $nid = array_values($nids)[0];
	   $node_data = $this->entityManager->getStorage('node')->load($nid);
	  }
	  if(empty($nids)) {
	   $node_data = Node::create([
	    'type' => 'weight_chart'
	   ]);
	  }
      $node_data->set('field_weight_date',$data['field_weight_date']);
      $node_data->set('field_before_weight',$data['field_before_weight']);
      $node_data->set('field_member_details',$data['trainee']);
	  if(!empty($data['field_after_weight'])) {
       $node_data->set('field_after_weight',$data['field_after_weight']);
	  }
	  $node_data->save();
	  if(!empty($node_data) && $node_data->id()) {
	   $result['statusMessage'] = "Weight data captured successfully";
       $response = new ResourceResponse($result, 200);
       $response->addCacheableDependency($result);
       return $response;
	  }
    }
    else {
     $result['errorMessage'] = "Invalid parameter provided.";
     $response = new ResourceResponse($result, 400);
     $response->addCacheableDependency($result);
	 return $response;
    }
  }
}