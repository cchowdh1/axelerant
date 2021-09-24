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
use Drupal\user\UserAuth;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "post_rest_forgot_password",
 *   label = @Translation("Forgot Password post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/user-forgot-password",
 *       "https://www.drupal.org/link-relations/create" = "/user-forgot-password"
 *   }
 * )
 */
class ForgotPassword extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * A current user authentication.
   *
   * @var Drupal\user\UserAuth
   */
  protected $authUser;
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
   * @param \Drupal\user\UserAuth $authUser
   *   To authenticate user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
  EntityManagerInterface $entityManager,
  QueryFactory $entity_query, UserAuth $authUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->entityManager = $entityManager;
    $this->entityQuery = $entity_query;
    $this->authUser = $authUser;
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
    $container->get('user.auth'),
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    // Use current user after pass authentication to validate access.
    $result['statusMessage'] = "error";
    $result['errorMessage'] = null;
    if ((count($data) > 0) && array_key_exists('forgotType', $data)) {
	 if(strtolower($data['forgotType']) == 'email') {
	  $user_profile = user_load_by_mail($data['value']);
	 }
	 if(strtolower($data['forgotType']) == 'phone') {
	  $user_profile = user_load_by_name($data['value']);
	 }
     if($user_profile) {
      if(user_is_blocked($user_profile->getUsername())) {
       $result['errorMessage'] = "User is blocked.Please contact Administrator";
       $response = new ResourceResponse($result,403);
       $response->addCacheableDependency($result);
       return $response;
      }
      $user_roles = $user_profile->getRoles();
      if(!in_array("member", $user_roles) && !in_array("trainer", $user_roles) ) {
       $result['errorMessage'] = "User is not allowed for forgot password";
       $response = new ResourceResponse($result,403);
       $response->addCacheableDependency($result);
       return $response;
      }
	  if($user_profile->field_forgot_password_applied->value >= 5) {
	   $user_profile->block();
       $user_profile->save();
	   $result['errorMessage'] = "User reached maximum password reset limit. Your account is blocked now. Kindly contact administrator.";
       $response = new ResourceResponse($result,429);
       $response->addCacheableDependency($result);
       return $response;
	  }
	  //Generate random verification
	  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen($characters);
      $randomString = '';
      for ($i = 0; $i < 10; $i++) {
       $randomPasswordString .= $characters[rand(0, $charactersLength - 1)];
      }
	  //Set User Profile data
	  $user_profile->set('field_forgot_password_validation',$randomPasswordString);
	  $user_profile->set('field_forgot_password_applied',($user_profile-> field_forgot_password_applied->value+1));
      $user_profile->save();
	  //Send Email
	 
	  $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'gym_webservices';
      $key = 'gym_forgot_password';
      $to =  $user_profile->mail->value;
      $params['message'] = "<div>
			<p>Kindly use the below validation code to reset your password.</p>
			<p>Validation Code : $randomPasswordString</p>
			</div>";
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = true;

      $mail_result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
      if ($mail_result['result'] !== true) {
       $result['errorMessage'] = "User password changed but not able to sent email. Please contact administrator";
       $response = new ResourceResponse($result,400);
       $response->addCacheableDependency($result);
       return $response;
      }
	  $result['statusMessage'] = "New Password sent to your Email address.";
      $response = new ResourceResponse($result,200);
      $response->addCacheableDependency($result);
      return $response;
     }
     else {
      $result['errorMessage'] = "User Does not exists.";
      $response = new ResourceResponse($result,404);
      $response->addCacheableDependency($result);
      return $response;
     }
     
    }
    else {
     $result['errorMessage'] = "Please provide correct parameter.";
     $response = new ResourceResponse($result,400);
     $response->addCacheableDependency($result);
     return $response;
    }
  }

}