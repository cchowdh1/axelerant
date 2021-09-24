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
 *   id = "post_rest_user_review",
 *   label = @Translation("User Review post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/user-review",
 *       "https://www.drupal.org/link-relations/create" = "/user-review"
 *   }
 * )
 */
 
class UserReview extends ResourceBase {
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
   * Responds to POST requests.
   */
  public function post($data) {
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    if ((count($data) > 0)  && array_key_exists('trainer', $data) &&  array_key_exists('comment', $data) &&  array_key_exists('rating', $data)) {
      if ($this->currentUser->id()) {
       $uid = $this->currentUser->id();
      }
	  //Check trainee
	  $ids = $this->entityQuery->get('user')
			->condition('field_trainee', $uid)
			->condition('uid', $data['trainer'])
			->range(0, 1)
			->execute();
	  if(empty($ids)) {
		$result['errorMessage'] = "Trainee and Trainer details are incorrect.";
        $response = new ResourceResponse($result, 403);
        $response->addCacheableDependency($result);
	    return $response;
	  }
     $connection = $this->connection;
	 
	 $reviw_query = $connection->select('user__field_review', 'fr');
	 $reviw_query->fields('ftr', ['entity_id']);
	 $reviw_query->join('paragraph__field_trainee_reference', 'ftr', 'ftr.entity_id = fr.field_review_target_id');
	 $reviw_query->condition('fr.entity_id', $data['trainer']);
	 $reviw_query->condition('ftr.field_trainee_reference_target_id', $uid);
	 $paragraph_id = $reviw_query->execute()->fetchField();
	 
	 if(!empty($paragraph_id)) {
	  $paragraph_data = $this->entityManager->getStorage('paragraph')->load($paragraph_id);
	  $paragraph_data->set('field_rating',$data['rating']);
	  if(!empty($data['comment'])) {
	   $paragraph_data->set('field_trainee_comment',$data['comment']);
	  }
	  $paragraph_data->save();
	 }
	 else {
	  $paragraph_data = Paragraph::create([
	  'type' => 'review',
	  'field_trainee_comment' => [0 => ['value' => $data['comment']]],
	  'field_rating' => [0 => ['value' => $data['rating']]],
	  'field_trainee_reference' => [0 => ['target_id' => $uid]],
	  ]);
	  $paragraph_data->save();
	  $paragraph_data_final = ['target_id' => $paragraph_data->id(), 'target_revision_id' => $paragraph_data->getRevisionId()];
	  $user_storage = $this->entityManager->getStorage('user');
      $trainer_profile = $user_storage->load($data['trainer']);
	  $trainer_profile->field_review[] = $paragraph_data_final;
      $trainer_profile->save();
	 }
	 
	 $result['statusMessage'] = "Review successfully saved";
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