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
 *   id = "get_rest_work_out_plan_for_trainee",
 *   label = @Translation("Workout Plan for Trainee rest resource"),
 *   uri_paths = {
 *     "canonical" = "/workout-plan/{user}"
 *   }
 * )
 */
 
class GetWorkOutPlan extends ResourceBase {
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
  public function get($user) {
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
	$result['load'] = False;
	$flag = FALSE;
	$uid = 0; 
	if($this->currentUser->id() == $user) {
	 $uid = $user;
	 $flag = TRUE;
	}
	else {
	 //Check trainee
	 $ids = $this->entityQuery->get('user')
		->condition('field_trainee', $user)
		->condition('uid', $this->currentUser->id())
		->range(0, 1)
		->execute();
	 if(empty($ids)) {
	  $result['errorMessage'] = "Trainee and Trainer details are incorrect.";
      $response = new ResourceResponse($result,406);
      $response->addCacheableDependency($result);
	  return $response;
	 }
	 $uid = $user;
	 $flag = TRUE;
	}
    if ($flag) {
     $connection = $this->connection;
	 $reviw_query = $connection->select('user__field_workout_plan', 'wp');
	 $reviw_query->fields('wp', ['field_workout_plan_target_id']);
	 $reviw_query->join('paragraphs_item', 'pi', 'pi.id = wp.field_workout_plan_target_id');
	 $reviw_query->condition('wp.entity_id', $uid);
	 $paragraph_id = $reviw_query->execute()->fetchField();
	 $food_chart = [];
	 $field_data = [];
	 if(!empty($paragraph_id)) {
	  $result['load'] = True;
	  $paragraph_data = $this->entityManager->getStorage('paragraph')->load($paragraph_id);
	  // load each paragraph data using field id
	  
	  //Load data for Cardio
	  $para_cardio_id = $paragraph_data->field_cardio->getValue()[0]['target_id'];
	  $para_cardio = $this->entityManager->getStorage('paragraph')->load($para_cardio_id);
	  
	  //Load data for Chest
	  $para_chest_id = $paragraph_data->field_chest_plan->getValue()[0]['target_id'];
	  $para_chest = $this->entityManager->getStorage('paragraph')->load($para_chest_id);
	  
	  //Load data for Back
	  $para_back_id = $paragraph_data->field_back->getValue()[0]['target_id'];
	  $para_back = $this->entityManager->getStorage('paragraph')->load($para_back_id);
	  
	  //Load data for Triceps
	  $para_triceps_id = $paragraph_data->field_triceps->getValue()[0]['target_id'];
	  $para_triceps = $this->entityManager->getStorage('paragraph')->load($para_triceps_id);
	  
	  //Load data for Fore Arms
	  $para_fore_arms_id = $paragraph_data->field_fore_arms->getValue()[0]['target_id'];
	  $para_forearms = $this->entityManager->getStorage('paragraph')->load($para_fore_arms_id);
	  
	  //Load data for ABS
	  $para_abs_id = $paragraph_data->field_abs->getValue()[0]['target_id'];
	  $para_abs = $this->entityManager->getStorage('paragraph')->load($para_abs_id);
	  
	  //Load data for Side
	  $para_side_id = $paragraph_data->field_side->getValue()[0]['target_id'];
	  $para_side = $this->entityManager->getStorage('paragraph')->load($para_side_id);
	  
	  //Load data for Thigh
	  $para_thigh_id = $paragraph_data->field_thigh->getValue()[0]['target_id'];
	  $para_thigh = $this->entityManager->getStorage('paragraph')->load($para_thigh_id);
	  
	  //Load data for Biceps
	  $para_biceps_id = $paragraph_data->field_biceps->getValue()[0]['target_id'];
	  $para_biceps = $this->entityManager->getStorage('paragraph')->load($para_biceps_id);
	  
	  //Load data for Shoulder
	  $para_shoulder_id = $paragraph_data->field_shoulder->getValue()[0]['target_id'];
	  $para_shoulder = $this->entityManager->getStorage('paragraph')->load($para_shoulder_id);
	 }
	 $entity_type_id = 'paragraph';
	 $cardiodata = [];
	 $chestdata = [];
	 $backdata = [];
	 $tricepsdata = [];
	 $forearmsdata = [];
	 $absdata = [];
	 $sidedata = [];
	 $thighdata = [];
	 $bicepsdata = [];
	 $shoulderdata = [];
     
	 //For Cardio
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'cardio') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
		$cardiodata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivityDuration' => $para_cardio->$field_name->value, 'FieldIdentifier' => $field_name];
      }
     }
	 
	 //For Chest
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'chest') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$chestdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_chest->$field_name->value, 'ActivityReps' => $para_chest->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
     }
	 
	 //For Back
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'back') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$backdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_back->$field_name->value, 'ActivityReps' => $para_back->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
     } 
	 
	 //For Triceps
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'triceps') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$tricepsdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_triceps->$field_name->value, 'ActivityReps' => $para_triceps->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
	 }
	 
	  //For Fore Arms
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'fore_arms') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$forearmsdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_forearms->$field_name->value, 'ActivityReps' => $para_forearms->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
     } 
	 
	 //For ABS
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'abs') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$absdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_abs->$field_name->value, 'ActivityReps' => $para_abs->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
     } 
	 
	 //For Side
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'side') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$sidedata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_side->$field_name->value, 'ActivityReps' => $para_side->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
     }
	 
	 //For Thigh
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'thigh') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$thighdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_thigh->$field_name->value, 'ActivityReps' => $para_thigh->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
	 }
	  //For Biceps
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'biceps') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$bicepsdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_biceps->$field_name->value, 'ActivityReps' => $para_biceps->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
     }
	 
	 //For Shoulder
	 foreach ($this->entityManager->getFieldDefinitions($entity_type_id, 'shoulder') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && !strpos($field_name,"reps")) {
		$shoulderdata[] = ['ActivityIdentifier' => $field_definition->getLabel(), 'ActivitySets' => $para_shoulder->$field_name->value, 'ActivityReps' => $para_shoulder->{$field_name."_reps"}->value, 'FieldIdentifier' => $field_name];
      }
     }
	 
	 $result['workout_plan'] = ['cardio' => $cardiodata,
								'chest' => $chestdata,
								'back' => $backdata,
								'triceps' => $tricepsdata,
								'fore_arms' => $forearmsdata,
								'abs' => $absdata,
								'side' => $sidedata,
								'thigh' => $thighdata,
								'biceps' => $bicepsdata,
								'shoulder' => $shoulderdata
							   ];
	 $result['statusMessage'] = "Forms shared successfully";
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