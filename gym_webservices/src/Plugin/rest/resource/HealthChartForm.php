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
 *   id = "get_rest_trainee_figure_correction_chart",
 *   label = @Translation("Figure Correction Chart form rest resource"),
 *   uri_paths = {
 *     "canonical" = "/figure-correction-chart-form/{trainee}"
 *   }
 * )
 */
 
class HealthChartForm extends ResourceBase {
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
    if ($this->currentUser->id() && $trainee) {
	 $uid = $this->currentUser->id();
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
	 $figure_correction_chart = [];
	 $entity_type_id = 'paragraph';
     $bundle = 'health_chart';
     foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
	   
	   //Form Generation
	   $validation_arr = [];
	   $validation_arr[] =  [
		  'name' => 'required',
		  'validator' => ['type' => 'required', 'value' => true],
		  'message' => $field_definition->getLabel().' is Required.'
	     ];
	   if($field_definition->getType() != 'datetime') {
		$validation_arr[] = [
		  'name' => 'pattern',
		  'validator' => ['type' => 'pattern', 'value' => "^[0-9]+(\.?[0-9]+)?$"],
		  'message' => 'Invalid format'
	     ];
	   }
	   $figure_correction_chart[] = [
	    'type' => $field_definition->getType() == 'string'?'input':'date',
	    'inputType' => $field_definition->getType() == 'string'?'text':'date',
		'label' => $field_definition->getLabel(),
		'name' => $field_name,
		'validations' => $validation_arr
	   ];
      }
     }
	 $figure_correction_chart['action']['type'] = 'button';
	 $figure_correction_chart['action']['label'] = 'Save';
	
	 $result['form'] = $figure_correction_chart;
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