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
 *   id = "post_rest_work_out_plan_for_trainee",
 *   label = @Translation("Workout Plan for Trainee post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/workout-plan-save",
 *       "https://www.drupal.org/link-relations/create" = "/workout-plan-save"
 *   }
 * )
 */
 
class SaveWorkOutPlan extends ResourceBase {
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
	$flag = FALSE;
	$uid = 0;
	if(empty($data['uid']) || !is_numeric($data['uid'])) {
	 $result['errorMessage'] = "Invalid parameter provided.";
     $response = new ResourceResponse($result,400);
     $response->addCacheableDependency($result);
	 return $response;
	}
	
	 //Check trainee
	$ids = $this->entityQuery->get('user')
		->condition('field_trainee', $data['uid'])
		->condition('uid', $this->currentUser->id())
		->range(0, 1)
		->execute();
	if(empty($ids)) {
	 $result['errorMessage'] = "Trainee and Trainer details are incorrect.";
     $response = new ResourceResponse($result,406);
     $response->addCacheableDependency($result);
	 return $response;
	}
	 $uid = $data['uid'];
	 $flag = TRUE;
	
    if ($flag) {
     $connection = $this->connection;
	 $reviw_query = $connection->select('user__field_workout_plan', 'wp');
	 $reviw_query->fields('wp', ['field_workout_plan_target_id']);
	 $reviw_query->join('paragraphs_item', 'pi', 'pi.id = wp.field_workout_plan_target_id');
	 $reviw_query->condition('wp.entity_id', $uid);
	 $paragraph_id = $reviw_query->execute()->fetchField();
	 
	 $mappingArr = 
	   ['cardio' => ['field_name' => 'field_cardio'],
		'chest' => ['field_name' => 'field_chest_plan'],
		'back' => ['field_name' => 'field_back'],
		'triceps' => ['field_name' => 'field_triceps'],
		'fore_arms' => ['field_name' => 'field_fore_arms'],
		'abs' => ['field_name' => 'field_abs'],
		'side' => ['field_name' => 'field_side'],
		'thigh' => ['field_name' => 'field_thigh'],
		'biceps' => ['field_name' => 'field_biceps'],
		'shoulder' => ['field_name' => 'field_shoulder'],
	  ];
	 
	 if(!empty($paragraph_id)) {
	  $workout_plan_data = $this->entityManager->getStorage('paragraph')->load($paragraph_id);
	  $paragraph_exists = TRUE;
	 }
	 else {
      $workout_plan_data = Paragraph::create([
       'type' => 'workout_plan'
      ]);
	 }
	 
	 foreach($data['workout_plan'] as $field_key => $field_value ) {
	  $child_paragraph_data = "";
	  $child_paragraph_data_id = ($paragraph_exists)?$workout_plan_data->{$mappingArr[$field_key]['field_name']}->getValue()[0]['target_id']:"";
	  if(!empty($child_paragraph_data_id)) {
	   $child_paragraph_data = $this->entityManager->getStorage('paragraph')->load($child_paragraph_data_id);
	  }
	  else {
	   $child_paragraph_data = Paragraph::create([
       'type' => $field_key
      ]);
	  }
	  foreach($field_value as $field_arr) {
	   if($field_key == 'cardio') {
		$child_paragraph_data->set($field_arr['FieldIdentifier'], $field_arr['ActivityDuration']);
	   }
	   else {
		$child_paragraph_data->set($field_arr['FieldIdentifier'], $field_arr['ActivitySets']);
		$reps_field = $field_arr['FieldIdentifier'].'_reps';
		$child_paragraph_data->set($reps_field, $field_arr['ActivityReps']);
	   }
	  }
	  $child_paragraph_data->save();
      $child_paragraph_data_final = ['target_id' => $child_paragraph_data->id(), 'target_revision_id' => $child_paragraph_data->getRevisionId()];
	  $workout_plan_data->set($mappingArr[$field_key]['field_name'], $child_paragraph_data_final);
	 }
	 //save data here
	 $workout_plan_data->save();
      $paragraph_workout_plan_data[] = ['target_id' => $workout_plan_data->id(), 'target_revision_id' => $workout_plan_data->getRevisionId()];
	  
	 //User Save	 
	 $trainee_data = $this->entityManager->getStorage('user')->load($data['uid']);
	 $trainee_data->set('field_workout_plan', $paragraph_workout_plan_data);
	 $trainee_data->save();
	 
	 $result['statusMessage'] = "Workout Plan saved successfully";
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