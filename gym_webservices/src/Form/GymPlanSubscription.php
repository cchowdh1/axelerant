<?php

namespace Drupal\gym_webservices\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;


class GymPlanSubscription extends FormBase {
   
   public function getFormId() {
	
	return 'gym_plan_subscription_form';
	
  }
    /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
		
		$values = $form_state->getValues();
		$form['search_with'] = array(
		  '#type' => 'select',
		  '#title' => t('Search a trainee with Email/Phone'),
		  '#options' => [
					'email' => "Email",
					'phone' => "Phone",
					],
		  '#required' => TRUE,
		  '#ajax' => [
            'callback' => [$this, 'searchSelectChange'],
            'event' => 'change',
            'wrapper' => 'subscription-container'
          ],
		);
		$form['subscription_container'] = [
         '#type' => 'container',
         '#attributes' => [
          'id' => 'subscription-container',
         ],
		 //'#prefix' => '<div class="col-sm-12 col-md-12">',
		 //'#suffix' => "</div>"
        ];
		$form['subscription_container']['trainee'] = array(
		  '#type' => 'textfield',
		  '#title' => t('Search a trainee'),
		  '#required' => TRUE,
		  '#attributes' => ['disabled' => TRUE ]
		  
		);
		
		$form['subscription_container']['subscription_plan'] = array(
		  '#type' => 'select',
		  '#title' => t('Choose a plan'),
		  '#required' => TRUE,
		);
		
		if (!empty($values) && !empty($values['search_with'])) {
		 $form['subscription_container']['trainee']['#title'] = 'Search a trainee with '. ucfirst($values['search_with']);
		 $form['subscription_container']['trainee']['#autocomplete_route_name'] = 'gym.user_autocomplete';
		 $form['subscription_container']['trainee']['#autocomplete_route_parameters'] = ['type' => $values['search_with']];
		 $form['subscription_container']['trainee']['#attributes']['disabled'] = FALSE;
		 
		 $connection = \Drupal::database();
         $query = $connection->select('node_field_data', 'fd')
          ->fields('fd', ['title','nid']);
		 $query->fields('pv', ['field_plan_validity_value']);
		 $query->join('node__field_plan_validity', 'pv', 'pv.entity_id = fd.nid');
         $output_query = $query->execute();
		 while ($final_data = $output_query->fetchObject()) {
		  $results[$final_data->nid."-".$final_data->field_plan_validity_value] = $final_data->title;
		 }
		 $form['subscription_container']['subscription_plan']['#options'] = $results;
		 $$form['subscription_container']['#prefix'] = '';
		 $$form['subscription_container']['#suffix'] = '';
		}
		
		$form['submit'] = array(
		  '#type' => 'submit',
		  '#value' => t('Submit'),
		);
		return $form;
  
  }
  
   /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  
	$values = $form_state->getValues();
	if($values['search_with'] == 'email') {
	 $user = user_load_by_mail($values['trainee']);
	 if(empty($user)) {
	  $form_state->setErrorByName('trainee', $this->t('Invalid Trainee selected'));
	 }
	}
	if($values['search_with'] == 'phone') {
	 $user = user_load_by_name($values['trainee']);
	 if(empty($user)) {
	  $form_state->setErrorByName('trainee', $this->t('Invalid Trainee selected'));
	 }
	}
	
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$values = $form_state->getValues();
	if($values['search_with'] == 'email') {
	 $user = user_load_by_mail($values['trainee']);
	}
	if($values['search_with'] == 'phone') {
	 $user = user_load_by_name($values['trainee']);
	}
	
	$planArr = explode('-',$values['subscription_plan']);
	//echo "<pre>"
	$effectiveDate = date('Y-m-d');
	$effectiveDate = date('Y-m-d', strtotime("+".$planArr[1]." months", strtotime($effectiveDate)));
	$user->set('field_subscription_plan',$planArr['0']);
	$user->set('field_subscription_expiry',$effectiveDate);
	$user->save();
	drupal_set_message("Subscription plan is successfuly updated for ".$user->field_first_name->value, 'status');
	
  }
  /**
   * The callback function for when the `my_select` element is changed.
   *
   * What this returns will be replace the wrapper provided.
   */
  public function searchSelectChange(array $form, FormStateInterface $form_state) {
    // Return the element that will replace the wrapper (we return itself).
    return $form['subscription_container'];
  }

}