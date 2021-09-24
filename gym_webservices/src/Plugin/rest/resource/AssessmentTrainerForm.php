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
 *   id = "get_rest_assessment_trainer_form",
 *   label = @Translation("Assessment Trainer Form Rest Api"),
 *   uri_paths = {
 *     "canonical" = "/assessment-trainer-form/{trainee}/{nid}"
 *   }
 * )
 */
 
class AssessmentTrainerForm extends ResourceBase {
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
  public function get($trainee, $nid) {
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    if ($this->currentUser->id() && is_numeric($trainee) && is_numeric($nid)) {
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
	 $bundleFields = [];
	 $entity_type_id = 'paragraph';
     $bundle = ['cardiovascular_endurance', 'muscular_endurance', 'muscular_strength'];
	 foreach($bundle as $bundle_val) {
	  foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle_val) as $field_name => $field_definition) {
	   if (!empty($field_definition->getTargetBundle())) {
		$bundleFields[$bundle_val][] = $field_name;
	   }
      }
	 }
     
	 // node load
	 $node_data = $this->entityManager->getStorage('node')->load($nid);
	 if(empty($node_data)) {
	  $result['errorMessage'] = "Invalid assessment form";
      $response = new ResourceResponse($result, 400);
      $response->addCacheableDependency($result);
	  return $response;
	 }
	 
	 //form and trainee mapping checking
	 if($node_data->field_trainee_assessment->getValue()[0]['target_id'] != $trainee) {
	  $result['errorMessage'] = "Invalid assessment form";
      $response = new ResourceResponse($result, 400);
      $response->addCacheableDependency($result);
	  return $response;
	 }
	 
	 //Cardiovascular Endurance values
	 $final_data = [];
	 $field_cardiovascular_endurance = $node_data->field_cardiovascular_endurance->getValue();
	 
	 if(count($field_cardiovascular_endurance) == 0) {
	  foreach($bundleFields['cardiovascular_endurance'] as $value) {
	   $final_data['cardio'][0][$value] = ""; 
	  }
	 }
	 else {
	  foreach($field_cardiovascular_endurance as $key => $value) {
	   $cardiovascular_endurance_paragraph_data = $this->entityManager->getStorage('paragraph')->load($value['target_id']);
	   if(!empty($cardiovascular_endurance_paragraph_data)) {
		$cardiovascular_endurance_field_data = [];
	    foreach($bundleFields['cardiovascular_endurance'] as $value) {
	     $cardiovascular_endurance_field_data[$value] = $cardiovascular_endurance_paragraph_data->{$value}->value; 
	    }
		$final_data['cardio'][] = $cardiovascular_endurance_field_data;
	   }
	  }
	 }
	 
	 //Muscular Endurance values
	 $field_mus = $node_data->field_mus->getValue();
	 
	 if(count($field_mus) == 0) {
	  foreach($bundleFields['muscular_endurance'] as $value) {
	   $final_data[$value][0] = ['value' => ""]; 
	  }
	 }
	 else {
	  foreach($field_mus as $key => $value) {
	   $muscular_endurance_paragraph_data = $this->entityManager->getStorage('paragraph')->load($value['target_id']);
	   if(!empty($muscular_endurance_paragraph_data)) {
		$muscular_endurance_field_data = [];
	    foreach($bundleFields['muscular_endurance'] as $value) {
	     $final_data[$value] = !empty($muscular_endurance_paragraph_data->{$value}->getValue())?$muscular_endurance_paragraph_data->{$value}->getValue():[0 =>['value' => ""]];
	    }
	   }
	  }
	 }
	 
	 //Muscular Strength values
	 $field_muscular_strength = $node_data->field_muscular_strength->getValue();
	 if(count($field_muscular_strength) == 0) {
	  foreach($bundleFields['muscular_strength'] as $value) {
	   $final_data[$value][0] = ['value' => ""]; 
	  }
	 }
	 else {
	  foreach($field_muscular_strength as $key => $value) {
	   $muscular_strength_paragraph_data = $this->entityManager->getStorage('paragraph')->load($value['target_id']);
	   if(!empty($muscular_strength_paragraph_data)) {
		$muscular_endurance_field_data = [];
	    foreach($bundleFields['muscular_strength'] as $value) {
	     $final_data[$value] = !empty($muscular_strength_paragraph_data->{$value}->getValue())?$muscular_strength_paragraph_data->{$value}->getValue():[0 =>['value' => ""]]; 
	    }
	   }
	  }
	 }
	
	 $result['form'] = $final_data;
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