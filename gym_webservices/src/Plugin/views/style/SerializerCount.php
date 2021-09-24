<?php
// thanks Dan - http://www.mediacurrent.com/blog/eight-insights-and-useful-snippets-d8-rest-module

/** @file
 * Contains \Drupal\gym_webservices\Plugin\views\style\SerializerCount.
 */

namespace Drupal\gym_webservices\Plugin\views\style;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\rest\Plugin\views\style\Serializer;

/** The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer_count",
 *   title = @Translation("Serializer with count"),
 *   help = @Translation("Serializes views row data using the Serializer component and adds a count."),
 *   display_types = {"data"}
 * )
 */

class SerializerCount extends Serializer {
  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
	  
      $rows[] = $this->view->rowPlugin->render($row);
    }
	
	foreach($rows as $row_keys => $rows_value) {
	 $field_cardiovascular_endurance_arr = [];
	 if(!empty($rows_value['field_cardiovascular_endurance'])) {
	   $bundleFields = [];
	   foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', 'cardiovascular_endurance') as $field_name => $field_definition) {
	   if (!empty($field_definition->getTargetBundle())) {
		$bundleFields[] = $field_name;
	   }
      }
	   $raw_data = json_decode($rows_value['field_cardiovascular_endurance']);
	   foreach($raw_data as $raw_data_key => $raw_data_value) {
		foreach($bundleFields as $fieldname) {
		 $field_cardiovascular_endurance_arr[$raw_data_key][$fieldname] = $raw_data_value->{$fieldname};
		}
	   }
	   $rows[$row_keys]['field_cardiovascular_endurance'] = $field_cardiovascular_endurance_arr;
	 }
	 if($this->view->id() == 'trainer_review_api') {
	  $total_rating += $rows_value['rating'];
	 }
	}
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    $pager = $this->view->pager;
    $class = get_class($pager);
    $current_page = $pager->getCurrentPage();
    $items_per_page = $pager->getItemsPerPage();
    $total_items = $pager->getTotalItems();
    $total_pages = 0;
    if(!in_array($class, ['Drupal\views\Plugin\views\pager\None', 'Drupal\views\Plugin\views\pager\Some'])){
      $total_pages = $pager->getPagerTotal();
    }
    //$rows[]
	
    $result = [
      'rows' => $rows,
      'pager' => [
        'current_page' => $current_page,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'items_per_page' => $items_per_page,
      ],
    ];
	
	if($this->view->id() == 'trainer_review_api') {
	  $result['rating']['title'] = $this->view->getTitle();
	  $result['rating']['value'] = (count($rows) > 0 )?($total_rating/(count($rows))) : 0;
	  $result['rating']['total_scale'] = 10;
	}
	//echo "<pre>"; print_r($result);exit;
	/* if($this->view->id() == 'home_page_callout' || $this->view->id() == 'follow_links') {
	  $result['title'] = $this->view->getTitle();
	} */
    return $this->serializer->serialize($result, $content_type, ['views_style_plugin' => $this]);
  }
}