<?php
/*	

  lt3 Custom Meta Field Boxes

------------------------------------------------
  custom-meta-field-boxes.php 1.0 
  Sunday, 3rd February 2013
  Beau Charman | @beaucharman | http://beaucharman.me
  Version: 1.0
  Notes:
  
  This file is for the custom meta fields for posts, pages, and custom post types.
  
  Simply add a new array to the $custom_meta_fields_array variable. 
  Use the following as your key and value pairs:
  
  array(
    'id'              => '', 
    'title'           => '',              
    'post_type'       => '', // 'post', 'page', 'link', 'attachment' a custom post type slug, or array             
    'context'         => '', // 'normal', 'advanced', or 'side'         
    'priority'        => '', // 'high', 'core', 'default' or 'low'
    'fields'          => array(
      array(
        'type'        => '',
        'id' 	        => '',
        'label'       => '',
      )
    )  
  )

------------------------------------------------ */

/* 

  Delcare the meta boxes

------------------------------------------------
Field: All require the following parameters: type, id & label
------------------------------------------------ */
$custom_meta_fields_array = array();

add_action('load-post.php', 'create_meta_boxes');
function create_meta_boxes()
{
  global $custom_meta_fields_array;
  foreach($custom_meta_fields_array as $cmfb)
  {
    new Meta_box($cmfb);
  }
}

class Meta_box 
{
  protected $_cmfb;
  function __construct($cmfb) 
  {
    $this->_cmfb = $cmfb;
    add_action('add_custom_meta_field_box', array( &$this, 'add_new_meta_box'));
    add_action('save_post', array( &$this, 'save_data'));
  }
  
  /* Add the Meta Box
  ------------------------------------------------ */
  function add_custom_meta_field_box() 
  {
    add_meta_box(
      ($this->_cmfb['id'])        ? $this->_cmfb['id']        : 'custom_meta_field_box',             
      ($this->_cmfb['title'])     ? $this->_cmfb['title']     : 'Custom Meta Field Box',          
      array( &$this, 'show_custom_meta_field_box'),
      ($this->_cmfb['post_type']) ? $this->_cmfb['post_type'] : 'post', 
      ($this->_cmfb['context'])   ? $this->_cmfb['context']   : 'advanced',
      ($this->_cmfb['priority'])  ? $this->_cmfb['priority']  : 'default'
    );
  }
  
  /* Show the Meta box
  ------------------------------------------------ */
  function show_custom_meta_field_box() 
  {
    global $post;
    $context = $this->_cmfb['context'];
    echo '<input type="hidden" name="custom_meta_fields_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';
    echo '<div class="lt3-form-container '. $this->_cmfb['context'] . '">';
    if($field['type'] == null)
    {
      foreach ( $this->_cmfb['fields'] as $field )
      {
        $field_id = '_' . $this->_cmfb['id'] . '_' . $field['id'];
        $meta = get_post_meta($post->ID, $field_id, true);
        $meta = ($meta) ? $meta : '';
        echo '<section class="custom-field-container">';
        $label_state = ($field['label'] == null) ? 'empty' : '';
        echo '<div class="label-container '. $label_state .'">';
        echo ($field['label'] != null) ? '<label for="'.$field_id.'">'.$field['label'].'</label>' : '&nbsp;';
        echo '<span class="description">'.$field['description'].'</span></div>';
        echo '<div class="input-container">';
        switch($field['type']) 
        {
        
          /* text
          ------------------------------------------------
          Extra parameters: description & placeholder
          ------------------------------------------------ */
          case 'text':
            echo '<input type="text" name="'.$field_id.'" id="'.$field_id.'" placeholder="'.$field['placeholder'].'" value="'.$meta.'"><br>';
          break;
          
          /* textarea
          ------------------------------------------------
          Extra Parameters: description
          ------------------------------------------------ */
          case 'textarea':
            echo '<textarea name="'.$field_id.'" id="'.$field_id.'">'.$meta.'</textarea><br>';
          break;
          
          /* post_list
          ------------------------------------------------
          Extra Parameters: description & post_type
          ------------------------------------------------ */
          case 'post_list':
            $meta = ($meta) ? $meta : array(); 
            $items = get_posts(array (
            'post_type'	=> $field['post_type'],
            'posts_per_page' => -1
            ));
            echo '<ul>';
            foreach($items as $item):
            $is_select = (in_array($item->ID, $meta)) ? ' checked' : '';
            echo '<li><input type="checkbox" name="'.$field_id.'['. $item->ID .']" id="'.$field_id.'['. $item->ID .']" value="'.$item->ID.'" '. $is_select .'>&nbsp;<label for="'.$field_id.'['. $item->ID .']">'.$item->post_title.'</label></li>';
            endforeach;
            echo '</ul>';
          break;	
        }
        echo '</div>';
        echo '</section>';
      }
      echo '</div>';
    }
  }
  
  /* Save the data
  ------------------------------------------------ */
  function save_data($post_id) 
  {
    if (!wp_verify_nonce($_POST['custom_meta_fields_box_nonce'], basename(__FILE__)))
    {
      return $post_id;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    {
      return $post_id;
    }
    if ('page' == $_POST['post_type']) {
      if (!current_user_can('edit_page', $post_id))
      {
        return $post_id;
      }
    } 
    elseif (!current_user_can('edit_post', $post_id)) 
    {
      return $post_id;
    }
    foreach ($this->_cmfb['fields'] as $field) 
    {
      $field_id = '_' . $this->_cmfb['id'] . '_' . $field['id'];
      $old = get_post_meta($post_id, $field_id, true);
      $new = $_POST[$field_id];
      if ($new && $new != $old) 
      {
        update_post_meta($post_id, $field_id, $new);
      } 
      elseif ('' == $new && $old) 
      {
        delete_post_meta($post_id, $field_id, $old);
      }
    }
  }
}
