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
 *   id = "post_rest_user_login",
 *   label = @Translation("User Login post rest resource"),
 *   uri_paths = {
 *     "canonical" = "/user-login",
 *       "https://www.drupal.org/link-relations/create" = "/user-login"
 *   }
 * )
 */   
class UserLogin extends ResourceBase {
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
    $container->get('user.auth')
    );
  }

  /**
   * Responds to POST requests.
   */
  public function post($data) {
    // Use current user after pass authentication to validate access.
    $result['statusMessage'] = "error";
    $result['errorMessage'] = "na";
    $result['blockstatus'] = false;
	//echo "<pre>"; print_r($data);exit;
    //$config = \Drupal::config('webservice.common.settings');
    if ((count($data) > 0)  && array_key_exists('name', $data) && array_key_exists('pass', $data)) {
     $uid = $this->authUser->authenticate($data['name'],$data['pass']);
if($uid) {
 
 $user_storage = $this->entityManager->getStorage('user');
      $user_profile = ($uid > 0)?$user_storage->load($uid):"";
/*  if(!in_array('attendee',$user_profile->getRoles())){
  $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("login_error_msg_step_1")]);
       $response = new ResourceResponse($result);
       $response->addCacheableDependency($result);
  return $response;
 } */
 if(user_is_blocked($user_profile->getUsername())) {
  $result['blockstatus'] = true;
  //$result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("error_msg_blocked_user")]);
  $result['errorMessage'] = 'User is blocked';
       $response = new ResourceResponse($result, 403);
       $response->addCacheableDependency($result);
  return $response;
 }
 //if($config->get("sms_sent_on_off") == 1) {
 /*  $number = $user_profile->get('field_mobile_phone')->value;
  $otp = $this->loginOtp->generateOtp($uid,$number);
  if(strtolower($user_profile->mail->value) === 'dilipkumar.sabat@cognizant.com' || strtolower($user_profile->mail->value) === 'anil.mathew@cognizant.com') {
   $otp = TRUE;
  }
  if(!$otp){
   $result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get("basic_error")]);
   $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("sms_sent_error")]);
        $response = new ResourceResponse($result);
        $response->addCacheableDependency($result);
   return $response;
  } */
 //}
 /* $resend_count = 0;
 if(strtolower($data['loginType']) == 'login') {
  $resend_count = 1;
  $remaining_otp_count = ($config->get("remaining_otp_count") - $resend_count);
  $user_profile->set('field_resend_otp_count',1);
  $user_profile->set('field_block_status',0);
       $result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get('login_success_msg_step_1')]);
 }
 if(strtolower($data['loginType']) == 'resend_otp') {
  $resend_count = $user_profile->field_resend_otp_count->value;
  $resend_count = $resend_count + 1;
  $user_profile->set('field_resend_otp_count',($resend_count));
  $remaining_otp_count = ($config->get("remaining_otp_count") - $resend_count);
       $result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get('resend_success_msg_step_1')]);
 } */
      //$result['otp'] = $otp;
     /*  $result['resend_otp_count'] = $resend_count;
      $result['remaining_otp_count'] = $remaining_otp_count; */
      $result['userId'] = floatval($uid);
 //$user_profile->save();
	$result['statusMessage'] = 'Successfuly logged in.';
      $response = new ResourceResponse($result, 200);
      $response->addCacheableDependency($result);
      return $response;
}
else {
	$result['errorMessage'] = "Invalid Login";
	$response = new ResourceResponse($result, 403);
    $response->addCacheableDependency($result);
	return $response;
}
/* else {
 $user = user_load_by_mail($data['email']);
 if(!empty($user)) {
  if(user_is_blocked($user->getUsername())) {
   $result['blockstatus'] = true;
   $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("error_msg_blocked_user")]);
        $response = new ResourceResponse($result);
        $response->addCacheableDependency($result);
   return $response;
  }
  if($user->field_block_status->value == ($config->get('allowed_invalid_login')-1)) {
$user->set('field_block_status',0);
$user->block();
$result['blockstatus'] = true;
  }
  else {
$invalid_attempt_remaining = $config->get('allowed_invalid_login') - (((int)$user->field_block_status->value)+1);
$result['invalid_attempt_remaining'] = $invalid_attempt_remaining;
$user->set('field_block_status',((int)$user->field_block_status->value)+1);
  }
  $user->save();
 }
     
 $result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get("basic_error")]);
 if(strtolower($data['loginType']) == 'login') {
       $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("login_error_msg_step_1")]);
 }
 if(strtolower($data['loginType']) == 'resend_otp') {
  $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("resend_error_msg_step_1")]);
 }
      $response = new ResourceResponse($result);
      $response->addCacheableDependency($result);
 return $response;
} */
     
    }
    else {
      //$result['statusMessage'] = $this->t('@statusMessage', ['@statusMessage' => $config->get("basic_error")]);
     // $result['errorMessage'] = $this->t('@errorMessage', ['@errorMessage' => $config->get("wrong_parameters")]);
	  $result['errorMessage'] = 'Wrong parameter provided';
      $response = new ResourceResponse($result, 400);
      $response->addCacheableDependency($result);
 return $response;
    }
  }

}