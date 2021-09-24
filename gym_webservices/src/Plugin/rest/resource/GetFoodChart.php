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

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "get_rest_food_chart_for_trainee",
 *   label = @Translation("Food Chart for Trainee Login rest resource"),
 *   uri_paths = {
 *     "canonical" = "/food-chart"
 *   }
 * )
 */
 
class GetFoodChart extends ResourceBase {
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
  public function get($trainee) {
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    if ($this->currentUser->id()) {
	 $uid = $this->currentUser->id();
	 
     $connection = $this->connection;
	 $reviw_query = $connection->select('user__field_food_chart', 'fc');
	 $reviw_query->fields('fc', ['field_food_chart_target_id']);
	 $reviw_query->join('paragraphs_item', 'pi', 'pi.id = fc.field_food_chart_target_id');
	 $reviw_query->condition('fc.entity_id', $uid);
	 $paragraph_id = $reviw_query->execute()->fetchField();
	 $food_chart = [];
	 $field_data = [];
	 if(!empty($paragraph_id)) {
	  $paragraph_data = $this->entityManager->getStorage('paragraph')->load($paragraph_id);
	  
	  $entity_type_id = 'paragraph';
      $bundle = 'food_chart';
      foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
	   $field_details = [];
	   $selected_values_arr = [];
       if (!empty($field_definition->getTargetBundle()) && $paragraph_data) {
		$selected_values = $paragraph_data->$field_name->getValue();
		if(count($selected_values)) {
		 foreach($selected_values as $values) {
	      $selected_values_arr[] = $values['value'];
		 }
		 foreach($field_definition->getSettings()['allowed_values'] as $key => $value) {
		  if(in_array($key, $selected_values_arr)) {
		   $field_details['values'][] = $value;
		  }
	     }
		 $field_details['label']  = $field_definition->getLabel();
		 $field_data[] = $field_details;
		}
       }
      }
	  $food_chart['is_prescribed'] = TRUE;
	  $food_chart['fields'] = $field_data;
	 }
	 else {
	  $food_chart['fields'] = "";
	  $food_chart['is_prescribed'] = FALSE;
	 }
	
	 $result['food_chart'] = $food_chart;
	 $result['statusMessage'] = "Forms shared successfully";
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