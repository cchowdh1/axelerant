<?php
/**
 * @file
 * Contains \Drupal\gym_webservices\Form\CommonConfigurationForm.
 */
namespace Drupal\gym_webservices\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CommonConfigurationForm extends ConfigFormBase  {
	
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gym_common_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gym_webservices.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  
    $config = $this->config('gym_webservices.settings');
	$form['subscription_expiry_notification'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscription Expiry Notification Alert Email'),
      '#open' => FALSE,
	  '#tree' => TRUE,
    ];		
	
	$form['subscription_expiry_notification']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('subscription_expiry_notification.subject'),
	  '#format' => 'admin_toolbar',
    ];	
	
	$form['subscription_expiry_notification']['mail_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('subscription_expiry_notification.mail_body'),
      '#maxlength' => 512,
    ];	
	
	$form['food_chart_field_order'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Food Chart form order'),
      '#default_value' => $config->get('food_chart_field_order'),
	  '#maxlength' => 1024
    ];	
	
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {	
  
    $values = $form_state->getUserInput();	
    
	$this->config('gym_webservices.settings')
     ->set('subscription_expiry_notification.subject', $values['subscription_expiry_notification']['subject'])
     ->set('subscription_expiry_notification.mail_body', $values['subscription_expiry_notification']['mail_body'])
     ->set('food_chart_field_order', $values['food_chart_field_order'])
     ->save();
    parent::submitForm($form, $form_state);
	
  }
}