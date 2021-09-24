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
 *   id = "post_rest_assessment_trainer_form_save",
 *   label = @Translation("Assessment Trainer Form Save Rest Api"),
 *   uri_paths = {
 *     "canonical" = "/assessment-trainer-form-save",
 *       "https://www.drupal.org/link-relations/create" = "/assessment-trainer-form-save"
 *   }
 * )
 */
 
class AssesmentFormSave extends ResourceBase {
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
    if ($this->currentUser->id() && isset($data['node']) && isset($data['trainee']) && is_numeric($data['trainee'])) {
	 $uid = $this->currentUser->id();
	 //Check trainee
	 $ids = $this->entityQuery->get('user')
		->condition('field_trainee', $data['trainee'])
		->condition('uid', $uid)
		->range(0, 1)
		->execute();
	  if(empty($ids)) {
		$result['errorMessage'] = "Trainee and Trainer details are incorrect.";
        $response = new ResourceResponse($result, 403);
        $response->addCacheableDependency($result);
	    return $response;
	  }
	 //Decide Add or Edit
	 if($data['node'] == 'edit') {
	  if(!is_numeric($data['nid'])) {
	   $result['errorMessage'] = "Invalid Node ID";
       $response = new ResourceResponse($result, 400);
       $response->addCacheableDependency($result);
	   return $response;
	  }
	 }
	 
	 if($data['node'] == 'edit') {
	  $node_data = $this->entityManager->getStorage('node')->load($data['nid']);
	  
	  //Cardiovascular Endurance
	  $field_cardiovascular_endurance = $node_data->field_cardiovascular_endurance->getValue();
	  if(count($field_cardiovascular_endurance) > 0) {
	   foreach($field_cardiovascular_endurance as $key => $value) {
	    $entity = \Drupal::entityTypeManager()->getStorage('paragraph')->load($value['target_id']);
        if ($entity) $entity->delete();
	   }
	  }
	  
	  //Muscular Endurance
	  $field_mus = $node_data->field_mus->getValue();
	  if(count($field_mus) > 0) {
	   foreach($field_mus as $key => $value) {
	    $entity = \Drupal::entityTypeManager()->getStorage('paragraph')->load($value['target_id']);
        if ($entity) $entity->delete();
	   }
	  } 
	  
	  //Muscular Strength
	  $field_muscular_strength = $node_data->field_muscular_strength->getValue();
	  if(count($field_muscular_strength) > 0) {
	   foreach($field_muscular_strength as $key => $value) {
	    $entity = \Drupal::entityTypeManager()->getStorage('paragraph')->load($value['target_id']);
        if ($entity) $entity->delete();
	   }
	  }
	  
	 }
	 if($data['node'] == 'add') {
	  //Check date
	  $ids = $this->entityQuery->get('node')
		->condition('field_trainee_assessment', $data['trainee'])
		->condition('field_assessment_date', date('Y-m-d'))
		->range(0, 1)
		->execute();
	  if(!empty($ids)) {
	   $result['errorMessage'] = "You have already a fiteness test for today.";
       $response = new ResourceResponse($result, 403);
       $response->addCacheableDependency($result);
	   return $response;
	  }
	  $node_data = Node::create([
	   'type' => 'assessment_form'
	  ]);
	  $node_data->set('field_assessment_date',date('Y-m-d'));
	 }
	 
	 
	 $cardiovascular_endurance_data_final = [];
	 foreach($data['form']['cardio'] as $key => $value) {
	  $cardiovascular_endurance_data = Paragraph::create([
        'type' => 'cardiovascular_endurance'
       ]);
	  foreach($value as $fieldkey => $fieldvalue) {
	   $cardiovascular_endurance_data->set($fieldkey, $fieldvalue);
	  }
	  $cardiovascular_endurance_data->save();
      $cardiovascular_endurance_data_single = ['target_id' => $cardiovascular_endurance_data->id(), 'target_revision_id' => $cardiovascular_endurance_data->getRevisionId()];
	  $cardiovascular_endurance_data_final[] = $cardiovascular_endurance_data_single;
	 }
	 
	 // Other paragraph field names
	 $bundleFields = [];
	 $entity_type_id = 'paragraph';
     $bundle = ['muscular_endurance', 'muscular_strength'];
	 foreach($bundle as $bundle_val) {
	  foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle_val) as $field_name => $field_definition) {
	   if (!empty($field_definition->getTargetBundle())) {
		$bundleFields[$bundle_val][] = $field_name;
	   }
      }
	 }
	 
	 //Muscular Endurance
	 $muscular_endurance_data = Paragraph::create([
        'type' => 'muscular_endurance'
       ]);
	 $muscular_endurance_data_final = [];
	 foreach($bundleFields['muscular_endurance'] as $value) {
	  if($data['form'][$value][0] != "" ) {
	   $muscular_endurance_data->set($value, $data['form'][$value]);
	  }
	 }
	 $muscular_endurance_data->save();
     $muscular_endurance_data_final[] = ['target_id' => $muscular_endurance_data->id(), 'target_revision_id' => $muscular_endurance_data->getRevisionId()];
	 
	 //Muscular Strength
	 $muscular_strength_data = Paragraph::create([
        'type' => 'muscular_strength'
       ]);
	 $muscular_strength_data_final = [];
	 foreach($bundleFields['muscular_strength'] as $value) {
	  if($data['form'][$value][0] != "" ) {
	   $muscular_strength_data->set($value, $data['form'][$value]);
	  }
	 }
	 $muscular_strength_data->save();
     $muscular_strength_data_final[] = ['target_id' => $muscular_strength_data->id(), 'target_revision_id' => $muscular_strength_data->getRevisionId()];
	 
	 
	 //Form Save
	 $node_data->set('field_cardiovascular_endurance', $cardiovascular_endurance_data_final);
	 $node_data->set('field_mus', $muscular_endurance_data_final);
	 $node_data->set('field_muscular_strength', $muscular_strength_data_final);
	 $node_data->set('field_assessment_form_status_for', 1);
	 $node_data->set('field_trainee_assessment', $data['trainee']);
	 $node_data->save();
	 
	 
	 $result['statusMessage'] = "Form saved successfully";
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