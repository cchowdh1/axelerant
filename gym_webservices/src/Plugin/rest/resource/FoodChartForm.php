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
 *   id = "get_rest_trinee_food_chart",
 *   label = @Translation("Food Chart form rest resource"),
 *   uri_paths = {
 *     "canonical" = "/food-chart-form/{trainee}/{action}"
 *   }
 * )
 */
 
class FoodChartForm extends ResourceBase {
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
  public function get($trainee,$action) {
	  
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    if ($this->currentUser->id() && $trainee && ($action == 'add' || $action == 'detail' || $action == 'edit') ) {
	 
	 $user_type = \Drupal::request()->query->get('user_type');
	 
	 if($user_type != 'trainer' && $user_type != 'trainee') {
	  $result['errorMessage'] = "User type is missing";
      $response = new ResourceResponse($result, 400);
      $response->addCacheableDependency($result);
	  return $response;
	 }
	 
	 if($action == 'detail' || $action == 'edit') {
	  $nid = \Drupal::request()->query->get('nid');
	  if(empty($nid) || !is_numeric($nid)) {
	   $result['errorMessage'] = "Chart detail missing";
       $response = new ResourceResponse($result, 400);
       $response->addCacheableDependency($result);
	   return $response;
	  }
	 }
	 if($action != 'detail' && $user_type == 'trainee') {
	  $result['errorMessage'] = "Operation is not permitted for this account";
      $response = new ResourceResponse($result, 403);
      $response->addCacheableDependency($result);
	  return $response;
	 }
	 $uid = $this->currentUser->id();
	 if($user_type == 'trainer') {
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
	 }
	 else if($user_type == 'trainee' && $uid != $trainee) {
      $result['errorMessage'] = "Invalid trainee details";
	  $response = new ResourceResponse($result, 403);
	  $response->addCacheableDependency($result);
	  return $response;
	 }
	 
	 //Fetch Node Field Details 
	 $entity_type_id = 'node';
     $bundle = 'food_chart';
     foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && $field_definition->getType() != 'entity_reference') {
       $nodeFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
       $nodeFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
       $nodeFields[$entity_type_id][$field_name]['allowed_values'] = $field_definition->getSettings()['allowed_values'];
	   if(!empty($field_definition->getDescription())) {
	    $descArr = explode('|', $field_definition->getDescription());
		$nodeFields[$entity_type_id][$field_name]['description'] = trim($descArr[0]);
		$nodeFields[$entity_type_id][$field_name]['minChecked'] = (int)trim($descArr[1]);
	   }
	   else {
		$nodeFields[$entity_type_id][$field_name]['description'] = '';
		//$bundleFields[$entity_type_id][$field_name]['minChecked'] = '';
	   }
      }
     }
	 $node_data = '';
	 if($action == 'detail' || $action == 'edit') {
	  $node_data = $this->entityManager->getStorage('node')->load($nid);
	  if(empty($node_data)) {
		$result['errorMessage'] = "Chart details not available";
        $response = new ResourceResponse($result, 403);
        $response->addCacheableDependency($result);
	    return $response;
	  }
	 }
	 $food_chart_form = [];
	 foreach($nodeFields['node'] as $field_name => $field_data) {
	  
	   $food_chart_form_item = [];
	   $selected_values = '';
	   $selected_values_arr = [];
	   $options = [];
	   $field_value = '';
	   if($field_data['type'] == 'entity_reference_revisions') {
		$paragraph_form = $this->processParagraphField($field_name,$trainee,$nid,$action);
		foreach($paragraph_form as $paragraph_val) {
		  $food_chart_form[] = 	$paragraph_val;
		}
		continue;
	   }
	   
	   if($field_data['type'] == 'datetime') {
	    $identifier = 'input';
		$food_chart_form_item['inputType'] = 'date';
		if($action != 'detail') {
		 $food_chart_form_item['validations'][] = ['name' => 'required',
												  'validator' => [
												    'type' => 'required',
												    'value' => true,
												  ],
												  'message' => $field_data['label'].' is Required',
												];
		}
	   }
	   else if($field_data['type'] == 'list_string') {
	    $identifier = 'select';
		if($node_data) {
		 $field_value = $node_data->$field_name->getValue()[0]['value'];
		}
		foreach($field_data['allowed_values'] as $key => $value) {
		 $options[] = ['name' => $value];
		 if($key == $field_value) {
		  $food_chart_form_item['value'] = $value;
		 }
		}
		$food_chart_form_item['options'] = $options;
		if($action != 'detail') {
		 $food_chart_form_item['validations'][] = ['name' => 'required',
												  'validator' => [
												    'type' => 'required',
												    'value' => true,
												  ],
												  'message' => $field_data['label'].' is Required',
												];
		}
	   }
	   else if($field_data['type'] == 'string_long') {
	    $identifier = 'textarea';
		if($action != 'detail') {
		 $food_chart_form_item['validations'][] = ['name' => 'maxlength',
												  'validator' => [
												    'type' => 'maxlength',
												    'value' => 400,
												  ],
												  'message' => "maximum length is 400 charcters",
												];
		}
	   }
	   else if($field_data['type'] == 'string') {
	    $identifier = 'input';
		$food_chart_form_item['inputType'] = 'time';
		if($action != 'detail') {
		 $food_chart_form_item['validations'][] = ['name' => 'maxlength',
												  'validator' => [
												    'type' => 'maxlength',
												    'value' => 400,
												  ],
												  'message' => "maximum length is 400 charcters",
												];
		}
	   }
	   
	   
	   $food_chart_form_item['identifier'] = $identifier;
	   $food_chart_form_item['label'] = $field_data['label'];
	   $food_chart_form_item['name'] = $field_name;
	   $food_chart_form_item['ui'] = [ 'class' => "col-sm-6",
									   'required' => true,
									 ];
	   if($action == 'detail') {
	     $food_chart_form_item['ui']['readonly'] = 	true;
	   }	   
	   
	   if(!empty($node_data) && ($field_data['type'] == 'datetime' || $field_data['type'] == 'string_long' || $field_data['type'] == 'string') ) {
		$food_chart_form_item['value'] = $node_data->$field_name->getValue()[0]['value'];
	   }
	   
	   $food_chart_form[] = $food_chart_form_item;
	   
	   
	 }
	 $final_food_chart_form = [];
	 if($action == 'add' || $action == 'edit') {
	  $final_food_chart_form['action']['identifier'] = 'button';
	  $final_food_chart_form['action']['label'] = 'Save';
	  $final_food_chart_form['action']['ui'] = [ 'class' => "col-sm-12 btn-prescribe"];
	 }
	 $form_order = explode(",",\Drupal::config('gym_webservices.settings')->get('food_chart_field_order'));
	 
	 foreach($form_order as $val) {
	  foreach($food_chart_form as $field_data) {
	   if($field_data['name'] == $val){
	    $final_food_chart_form[] = $field_data;
		break;
	   }
	  }
	 }
	 $result['form'] = $final_food_chart_form;
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
  
  public function processParagraphField($field,$trainee,$nid,$action) {
	  
	//Fetch Paragraph Fields details
	 $entity_type_id = 'paragraph';
     $bundle = 'food_chart';
     foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
       //$bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
       $bundleFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
       $bundleFields[$entity_type_id][$field_name]['allowed_values'] = $field_definition->getSettings()['allowed_values'];
	   if(!empty($field_definition->getDescription())) {
	    $descArr = explode('|', $field_definition->getDescription());
		$bundleFields[$entity_type_id][$field_name]['description'] = trim($descArr[0]);
		$bundleFields[$entity_type_id][$field_name]['minChecked'] = (int)trim($descArr[1]);
	   }
	   else {
		$bundleFields[$entity_type_id][$field_name]['description'] = '';
		//$bundleFields[$entity_type_id][$field_name]['minChecked'] = '';
	   }
      }
     }
	 
	 
	 $paragraph_id = '';
	 $paragraph_data = '';
	 if($action != 'add') {
		 $db_table_name = 'node__'.$field;
		 $connection = $this->connection;
		 $chart_query = $connection->select($db_table_name, 'fi');
		 $chart_query->fields('fi', ['field_food_items_target_id']);
		 $chart_query->join('node__field_tagged_trainee', 'tt', 'tt.entity_id = fi.entity_id');
		 $chart_query->join('paragraphs_item', 'pi', 'pi.id = fi.field_food_items_target_id');
		 $chart_query->condition('tt.field_tagged_trainee_target_id', $trainee);
		 $chart_query->condition('tt.entity_id', $nid);
		 $paragraph_id = $chart_query->execute()->fetchField();
		 if(!empty($paragraph_id)) {
		  $paragraph_data = $this->entityManager->getStorage('paragraph')->load($paragraph_id);
		 }
	 }
	 $food_chart_form = [];
	 foreach($bundleFields['paragraph'] as $field_name => $field_data) {
	   $selected_values = '';
	   $selected_values_arr = [];
	   $options = [];
	   if($paragraph_data) {
		$selected_values = $paragraph_data->$field_name->getValue();
		if(count($selected_values)) {
		 foreach($selected_values as $values) {
	      $selected_values_arr[] = $values['value'];
		 }
		}
		else if($action == 'detail' && count($selected_values) == 0) {
		 continue;
		}
	   }
	   foreach($field_data['allowed_values'] as $key => $value) {
		if(in_array($key, $selected_values_arr)) {
		 $selected = true;
		}
		else {
	     $selected = false;
		}
		if($action == 'detail') {
	     if($selected == false) {
		  continue;
		 }
		 $ui_readonly = true;
	    }
	    else {
		 $ui_readonly = false;
	    }
		$options[] = [
		 'name' => "$key",
		 'value' => $value,
		 'selected' => $selected
		];
	   }
	   
	   $food_chart_form[] = [
	    'identifier' => 'multicheckbox',
		'label' => $field_data['label'],
		'value' => true,
		'name' => $field_name,
		'options' => $options,
		'collections' => [
		 'type' => 'multicheckbox',
		],
		'ui' => [ 'class' => "col-sm-12",
		         'required' => true,
				 'readonly' => $ui_readonly
		      ]
	   ];
	   if(isset($field_data['minChecked']) && !empty($field_data['minChecked'])) {
		$food_chart_form[0]['collections']['minChecked'] = $field_data['minChecked'];
		if($action != 'detail') {
		 $food_chart_form[0]['validations'][0] = [
		  'name' => 'requireCheckboxToBeChecked',
          'message' => $field_data['description'],
          'validator' => 'custom'		 
		 ];
		}
	   }
	   
	 }
	 return $food_chart_form;
  }
}