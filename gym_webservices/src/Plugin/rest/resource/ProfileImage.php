<?php

namespace Drupal\gym_webservices\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "post_rest_profile_image",
 *   label = @Translation("Profile Image post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/profile-image",
 *       "https://www.drupal.org/link-relations/create" = "/profile-image"
 *   }
 * )
 */
class ProfileImage extends ResourceBase {
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityManagerInterface $entityManager,
    QueryFactory $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->entityManager = $entityManager;
    $this->entityQuery = $entity_query;
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
    $container->get('entity.query')
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    // Use current user after pass authentication to validate access.
    // if (!$this->currentUser->hasPermission('access content')) {
      // throw new AccessDeniedHttpException();
    // }
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    //$config = \Drupal::config('webservice.common.settings');
    if ((count($data) > 0) && array_key_exists('image', $data)) {
      if ($this->currentUser->id()) {
       $uid = $this->currentUser->id();
      }
      $user_storage = $this->entityManager->getStorage('user');
      $user_profile = ($uid > 0)?$user_storage->load($uid):"";
 
    if(empty($user_profile)) {
     $result['errorMessage'] = "Invalid User Data";
     $response = new ResourceResponse($result, 406);
       $response->addCacheableDependency($result);
       return $response;
    }
    // Save User
    if(empty($data['image']['filename'])) {
     $result['errorMessage'] = "Please provide filename";
     $response = new ResourceResponse($result, 406);
       $response->addCacheableDependency($result);
       return $response;
    }
    if(empty($data['image']['value'])) {
     $result['errorMessage'] = "Please provide valid image";
     $response = new ResourceResponse($result, 406);
     $response->addCacheableDependency($result);
     return $response;
    }
    /* if(empty($data['image']['alt'])) {
     $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get('profile_image_alt')]);
     $response = new ResourceResponse($result);
	   $response->addCacheableDependency($result);
	   return $response;
    } */
    else {
     $directory = 'public://profilepicture/';
     file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
	 $image_value = explode( ',', $data['image']['value'] );
     $image_base64 = base64_decode($image_value[1]);
     $f = finfo_open();
     $mime_type = finfo_buffer($f, $image_base64, FILEINFO_MIME_TYPE);
     if(strpos($mime_type, 'image/') === false) {
      $result['errorMessage'] = "Please provide valid image";
      $response = new ResourceResponse($result, 406);
      $response->addCacheableDependency($result);
      return $response;
     }
     $allowed = array('gif', 'png', 'jpg', 'jpeg');
     $ext = pathinfo($data['image']['filename'], PATHINFO_EXTENSION);
     if (!in_array($ext, $allowed)) {
      $result['errorMessage'] = "Please provide valid image";
      $response = new ResourceResponse($result, 406);
      $response->addCacheableDependency($result);
      return $response;
     }
     $file_data = file_save_data($image_base64, $directory.$data['image']['filename'], FILE_EXISTS_RENAME);
     if($file_data){
      file_delete($user_profile->field_profile_picture->getValue()[0]['target_id']);
      $attachments[] = ["target_id" => $file_data->id(), "display" => '1',"alt" => $data['image']['alt']];
      $user_profile->set('field_profile_picture', $attachments);
     }
     else {
      $result['errorMessage'] = "Unknown error! Please try after some time";
      $response = new ResourceResponse($result, 400);
      $response->addCacheableDependency($result);
      return $response;
     }
    }
     $user_profile->save();
     $result['statusMessage'] = "Image successfully uploaded";
     $response = new ResourceResponse($result, 201);
     $response->addCacheableDependency($result);
     return $response;
    }
    else {
     $result['errorMessage'] = "Invalid parameter provided";
     $response = new ResourceResponse($result, 400);
     $response->addCacheableDependency($result);
     return $response;
    }
  }

}