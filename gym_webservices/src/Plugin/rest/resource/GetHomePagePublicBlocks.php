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
use Drupal\file\Entity\File;
/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "get_home_page_public_blocks",
 *   label = @Translation("Home Page Public Blocks"),
 *   uri_paths = {
 *     "canonical" = "/home-page-public-blocks"
 *   }
 * )
 */
 
class GetHomePagePublicBlocks extends ResourceBase {
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
  public function get() {
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    
	//Home page Callouts
	$connection = $this->connection;
	$callout_query = $connection->select('node_field_data', 'nd');
	$callout_query->fields('nd', ['title']);
	$callout_query->join('node__field_callout_details', 'cd', 'cd.entity_id = nd.nid');
	$callout_query->join('paragraph__field_callout_status', 'cs', 'cs.entity_id = cd.field_callout_details_target_id');
	$callout_query->fields('cd', ['field_callout_details_target_id']);
	$callout_query->condition('nd.type', 'home_page_callouts_app');
	$callout_query->condition('nd.status', 1);
	$callout_query->condition('cs.field_callout_status_value', 1);
	$callout_result = $callout_query->execute()->fetchAll();
    
	$callouts = [];
	$callouts_field = [];
	$entity_type_id = 'paragraph';
    $bundle = 'home_callout';
    foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
	 if (!empty($field_definition->getTargetBundle())) {
	  $callouts_field[] = $field_name;
	 }
	}
	foreach($callout_result as $data) {
	 if(empty($callouts['title'])) {
	  $callouts['title'] = $data->title;
	 }
	 $callout_paragraph_data = $this->entityManager->getStorage('paragraph')->load($data->field_callout_details_target_id);
	 $child_paragraph_data = [];
	 foreach($callouts_field as $val) {
	  if($val == 'field_callout_description') {
	   $child_paragraph_data['description'] = $callout_paragraph_data->$val->getValue()[0]['value'];
	  }
	  if($val == 'field_callout_title') {
	   $child_paragraph_data['title'] = $callout_paragraph_data->$val->getValue()[0]['value'];
	  }
	  if($val == 'field_callout_icon') {
	   $file = \Drupal\file\Entity\File::load($callout_paragraph_data->$val->getValue()[0]['target_id']);
	   $child_paragraph_data['image'] = file_create_url($file->getFileUri());
	  }
	 }
	 $callouts['data'][] = $child_paragraph_data;
	 
	 
	}
	
	
	//Follow links
	$connection = $this->connection;
	$link_query = $connection->select('node_field_data', 'nd');
	$link_query->fields('nd', ['title']);
	$link_query->join('node__field_follow_links', 'fl', 'fl.entity_id = nd.nid');
	$link_query->join('paragraph__field_link_status', 'ls', 'ls.entity_id = fl.field_follow_links_target_id');
	$link_query->fields('fl', ['field_follow_links_target_id']);
	$link_query->condition('nd.type', 'follow_links');
	$link_query->condition('nd.status', 1);
	$link_query->condition('ls.field_link_status_value', 1);
	$link_result = $link_query->execute()->fetchAll();
	$links = [];
	$link_field = [];
	$entity_type_id = 'paragraph';
    $bundle = 'follow_links';
    foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
	 if (!empty($field_definition->getTargetBundle())) {
	  $link_field[] = $field_name;
	 }
	}
	foreach($link_result as $data) {
	 if(empty($links['title'])) {
	  $links['title'] = $data->title;
	 }
	 $link_paragraph_data = $this->entityManager->getStorage('paragraph')->load($data->field_follow_links_target_id);
	 $child_paragraph_data = [];
	 foreach($link_field as $val) {
	  if($val == 'field_link_text') {
	   $child_paragraph_data['title'] = $link_paragraph_data->$val->getValue()[0]['value'];
	  }
	  if($val == 'field_link_url') {
	   $child_paragraph_data['url'] = $link_paragraph_data->$val->getValue()[0]['value'];
	  }
	  if($val == 'field_external_link') {
	   $child_paragraph_data['external_link'] = ($link_paragraph_data->$val->getValue()[0]['value'] == 1)? TRUE:FALSE;
	  }
	 }
	 $links['data'][] = $child_paragraph_data;
	}
	$result['public_blocks']['callout_activities'] = $callouts;
	$result['public_blocks']['navigation'] = $links;
	$result['statusMessage'] = "Forms shared successfully";
    $response = new ResourceResponse($result, 200);
    $response->addCacheableDependency($result);
    return $response;
    
  }
}