<?php

/**
 * 
 * @since 1.0.0 
 * 
 * @package    WPRNE
 * @subpackage WPRNE/includes
 */

 
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * WPRNE Rest API class.
 */
class WPRNE_Rest_Api
{

  /**
   * Register rest api route 
   *
   * @since    1.0.0
   */

  public function rest_api_init()
  {
    //init data api	
    register_rest_route('wprne/v1', '/init/get_init_data', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_init_data'),
      'permission_callback' => array($this, 'permission_check')
    ));
	
	//apps api
    register_rest_route('wprne/v1', '/apps', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_apps'),
      'permission_callback' => array($this, 'permission_check')
    ));
	
    register_rest_route('wprne/v1', '/apps', array(
      'methods' => 'POST',
      'callback' => array($this, 'save_apps'),
      'permission_callback' => array($this, 'permission_check')
    ));	
	
    register_rest_route('wprne/v1', '/apps/(?P<id>\S+)', array(
      'methods' => 'PUT',
      'callback' => array($this, 'edit_app'),
      'permission_callback' => array($this, 'permission_check')
    ));
	
    register_rest_route('wprne/v1', '/apps/(?P<id>\S+)', array(
      'methods' => 'DELETE',
      'callback' => array($this, 'delete_app'),
      'permission_callback' => array($this, 'permission_check')
    ));

    //pages api
    register_rest_route('wprne/v1', '/pages/(?P<id>\S+)', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_pages'),
      'permission_callback' => array($this, 'permission_check')
    ));
	
    register_rest_route('wprne/v1', '/pages/(?P<id>\S+)', array(
      'methods' => 'POST',
      'callback' => array($this, 'save_pages'),
      'permission_callback' => array($this, 'permission_check')
    ));
	
    //templates api
    register_rest_route('wprne/v1', '/templates', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_templates'),
      'permission_callback' => array($this, 'permission_check')
    ));
	
    register_rest_route('wprne/v1', '/templates', array(
      'methods' => 'POST',
      'callback' => array($this, 'save_templates'),
      'permission_callback' => array($this, 'permission_check')
    ));

    //push notification api
    register_rest_route('wprne/v1', '/notif/add_token', array(
      'methods' => 'POST',
      'callback' => array($this, 'add_push_notif_token'),
      'permission_callback' => '__return_true'
    ));    
	
	//media api
    register_rest_route('wprne/v1', '/media/insert_media', array(
      'methods' => 'POST',
      'callback' => array($this, 'insert_media'),
      'permission_callback' => array($this, 'permission_check')
    ));
    register_rest_route('wprne/v1', '/media/insert_font', array(
      'methods' => 'POST',
      'callback' => array($this, 'insert_font'),
      'permission_callback' => array($this, 'permission_check')
    ));

    //custom post
    register_rest_route('wprne/v1', '/post/get_post_types', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_post_types'),
      'permission_callback' => array($this, 'permission_check')
    ));
	
    register_rest_route('wprne/v1', '/post/create_post', array(
      'methods' => 'POST',
      'callback' => array($this, 'create_post'),
      'permission_callback' => array($this, 'permission_check')
    ));

    //acf content
    register_rest_route('wprne/v1', '/acf/get_fields', array(
      'methods' => 'POST',
      'callback' => array($this, 'acf_get_fields'),
      'permission_callback' => array($this, 'permission_check')
    ));

    //license activations
    register_rest_route('wprne/v1', '/license', array(
      'methods' => 'POST',
      'callback' =>  array($this, "save_license"),
      'permission_callback' => array($this, 'permission_check')
    ));
	
	  $custom_post_types = get_post_types(array(
      'show_in_rest' => true,
      '_builtin' => false
    ));
	
    $post_types = array_unique(array_merge(array('post' => 'post'), $custom_post_types));

    foreach ($post_types as $post_type) {
      add_filter("rest_prepare_{$post_type}", array($this, "custom_rest_api_response"),  10, 3);
    }
  }
  
  private function handle_preflight() {
    
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
	header("Access-Control-Allow-Credentials: true");
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, X-WP-Nonce, Content-Type, Accept, Authorization');
	
 }

  public function permission_check()
  {
    $permission = current_user_can('edit_others_posts');
    return apply_filters('wprne_api_permission_callback', $permission);
  }

  public function custom_rest_api_response($response, $post, $request)
  {
	$image = get_the_post_thumbnail_url($post);
	$response->data['featured_image'] = array("url" => $image);
    if (function_exists('get_fields')) {

      $fields = get_fields($response->data['id']);
      if (!empty($fields)) {
        foreach ($fields as $key => $value) {
          $response->data[$key] = $value;
        }
      }
    }

    return $response;
  }

  public function create_post($request)
  {

    $data = $request->get_json_params();
    $post_type = $data['post_type'];
    $post_values = $data['values'];

    $title   = !empty($post_values['title'])   ? $post_values['title'] : '';
    $content = !empty($post_values['content']) ? $post_values['content'] : '';
    $excerpt = !empty($post_values['excerpt']) ? $post_values['excerpt'] : '';

    $post_data = array(
      'post_title'    => wp_strip_all_tags($title),
      'post_content'  => $content,
      'post_excerpt'  => $excerpt,
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => $post_type
    );

    $post_id = wp_insert_post($post_data);

    $groups = acf_get_field_groups(array('post_type' => $post_type));
    foreach ($groups as $group) {
      $groupFields = acf_get_fields($group['key']);
      foreach ($groupFields as $field) {
        $key = $field['key'];
        $name = $field['name'];
        if (!empty($post_values[$name])) {
          update_field($key, $post_values[$name], $post_id);
        }
      }
    }
  }

  public function get_init_data()
  {
    $custom_post_types = get_post_types(array(
      'show_in_rest' => true,
      '_builtin' => false
    ));

    $post_types = array_unique(array_merge(array('post' => 'post'), $custom_post_types));

    global $_wp_post_type_features;
    $post_typeData = array();
    foreach ($post_types as $post_type) {
      $fields = array();
      //get acf custom field
      if (function_exists('acf_get_field_groups') && function_exists('acf_get_fields')) {
        $groups = acf_get_field_groups(array('post_type' => $post_type));
        foreach ($groups as $group) {
          $groupFields = acf_get_fields($group['key']);
          foreach ($groupFields as $field) {
            $fields[] = $field;
          }
        }
      }

      $post_type_data[$post_type] = array(
        'name' => $post_type,
        'features' => $_wp_post_type_features[$post_type],
        'fields' => $fields,
        //'meta' => $meta_keys
      );
    }

    $pages = get_option('wprne_pages', array());

    if (!is_array($pages)) {
      $pages = array();
    }

    $templates = get_option('wprne_templates', array());
    $settings = get_option('wprne_settings', array());

    return rest_ensure_response(array(
      'status' => true,
      'post_types' => $post_type_data,
      'pages' => $pages,
      'templates' => $templates,
      'settings' => $settings,
    ));
  }

  public function acf_get_fields($request)
  {

    $data = $request->get_json_params();
    $ids = $data['ids'];

    $fields = array();
    if (function_exists('get_fields')) {
      foreach ($ids as $id) {
        $fields[$id] = get_fields($id);
      }
    }

    return rest_ensure_response(array(
      'status' => true,
      'fields' => $fields,
    ));
  }

  public function get_post_types($request)
  {

    $custom_post_types = get_post_types(array(
      'show_in_rest' => true,
      '_builtin' => false
    ));

    $post_types = array_unique(array_merge(array('post' => 'post'), $custom_post_types));

    if (!empty($post_types['product'])) unset($post_types['product']);

    global $_wp_post_type_features;
    $post_typeData = array();
    foreach ($post_types as $post_type) {
      //get acf custom field
      $fields = array();
      if (function_exists('acf_get_field_groups')) {
        $groups = acf_get_field_groups(array('post_type' => $post_type));
        foreach ($groups as $group) {
          $groupFields = acf_get_fields($group['key']);
          foreach ($groupFields as $field) {
            $fields[] = $field;
          }
        }
      }

      $post_type_obj = get_post_type_object($post_type);

      $post_type_data[$post_type] = array(
        'name' => $post_type,
        'label' => $post_type_obj->labels->singular_name,
        'features' => $_wp_post_type_features[$post_type],
        'fields' => $fields
      );
    }

    return rest_ensure_response(array('status' => true, 'postTypes' => $post_type_data));
  }
  
  

  public function get_apps($request)
  {
    $apps = get_option('wprne_apps', array());

    if (!is_array($apps)) {
      $apps = array();
    }

    return rest_ensure_response(array(
      'status' => true,
      'apps' => $apps
    ));
  }

  public function save_apps($request)
  {
    $newApps = $request->get_json_params();

    update_option('wprne_apps', $newApps);
	$newApps = get_option('wprne_apps', array());
	
    return rest_ensure_response(array(
      'status' => true,
      'apps' => $newApps
    ));
  }
  
  public function add_push_notif_token($request)
  {
    $data = $request->get_json_params();
	$token = $data["token"];
	$status = $data["status"];
	
	$tokens = get_option('wprne_push_notif_token', array());
	$tokens[$token] = array(
		"status" => $status
	);

    update_option('wprne_push_notif_token', $tokens);
	
    return rest_ensure_response(array(
      'status' => true,
      'tokens' => $tokens
    ));
  }

  public function edit_app($request)
  {
	$app_id = $request['id'];
    $newApp = $request->get_json_params();
	
	$apps = get_option('wprne_apps', array());
	$apps[$app_id] = $newApp;

    update_option('wprne_apps', $apps);
	$apps = get_option('wprne_apps', array());
	
    return rest_ensure_response(array(
      'status' => true,
      'apps' => $apps
    ));
  }

  public function delete_app($request)
  {
	$app_id = $request['id'];
	
	$apps = get_option('wprne_apps', array());
	unset($apps[$app_id]);

    update_option('wprne_apps', $apps);
	delete_option( 'wprne_pages_'.$app_id );
	$apps = get_option('wprne_apps', array());

    return rest_ensure_response(array(
      'status' => true,
      'apps' => $apps
    ));
  }

  public function save_pages($request)
  {
	$app_id = $request['id'];
    $newPages = $request->get_json_params();

    update_option('wprne_pages_'.$app_id, $newPages);
    $pages = get_option('wprne_pages_'.$app_id, array());

    return rest_ensure_response(array(
      'status' => true,
      'pages' => $pages
    ));
  }

  public function get_pages($request)
  {
	$app_id = $request['id'];
    $pages = get_option('wprne_pages_'.$app_id, array());

    return rest_ensure_response(array(
      'status' => true,
      'pages' => $pages
    ));
  }

  public function save_templates($request)
  {
    $newTemplates = $request->get_json_params();

    update_option('wprne_templates', $newTemplates);
	$templates = get_option('wprne_templates', array());
	
    return rest_ensure_response(array(
      'status' => true,
      'templates' => $templates
    ));
  }
  
  public function get_templates($request)
  {
    $templates = get_option('wprne_templates', array());
    return rest_ensure_response(array('status' => true, 'templates' => $templates));
  }
  
  
  public function add_template($request){
    $templates = get_option('wprne_templates',array());	
    $data = $request->get_json_params();
    
    $templates[] = array(
      'name' => $data['name'],
      'json' => $data['json']
    );
    
    update_option('wprne_templates', $templates);	
	$templates = get_option('wprne_templates', array());
  
    wp_send_json(array(
      'status' => true,
      'templates' => $templates
    ));
  }
  
  public function insert_media($request)
  {

    // Get the path to the upload directory.
    $wp_upload_dir = wp_upload_dir();
    $images = $request->get_file_params();
    $json = array();
    foreach ($images as $image) {
      $name = $image['name'];
      $new_file_path = $wp_upload_dir['path'] . '/' . $name;
      $new_file_url = $wp_upload_dir['url'] . '/' . $name;

      if (move_uploaded_file($image['tmp_name'], $new_file_path)) {
        $attachment = array(
          'guid' => $new_file_path,
          'post_mime_type' => 'image/png',
          'post_title'     => preg_replace('/\.[^.]+$/', '', $name),
          'post_content'   => '',
          'post_status'    => 'inherit'
        );

        $image_id = wp_insert_attachment($attachment, $new_file_path);

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata($image_id, $new_file_path);

        wp_update_attachment_metadata($image_id, $attach_data);

        $json[] = array(
          'id'         => $image_id,
          'source_url' => wp_get_attachment_url($image_id)
        );
      }
    }

    return rest_ensure_response(array('status' => true, 'data' => $json));
  }

  public function insert_media_from_url($url)
  {

    // Gives us access to the download_url() and wp_handle_sideload() functions
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    $timeout_seconds = 5;

    // Download file to temp dir
    $temp_file = download_url($url, $timeout_seconds);

    if (!is_wp_error($temp_file)) {

      // Array based on $_FILE as seen in PHP file uploads
      $file = array(
        'name'     => basename($url),
        'type'     => 'image/png',
        'tmp_name' => $temp_file,
        'error'    => 0,
        'size'     => filesize($temp_file),
      );

      $overrides = array(
        // Tells WordPress to not look for the POST form
        // fields that would normally be present as
        // we downloaded the file from a remote server, so there
        // will be no form fields
        // Default is true
        'test_form' => false,

        // Setting this to false lets WordPress allow empty files, not recommended
        // Default is true
        'test_size' => true,
      );

      // Move the temporary file into the uploads directory
      $results = wp_handle_sideload($file, $overrides);

      if (!empty($results['error'])) {
        // Insert any error handling here
      } else {

        $name = basename($url); // Full path to the file
        $new_file_path = $results['file'];  // URL to the file in the uploads dir
        $type = $results['type']; // MIME type of the file

        $attachment = array(
          'guid' => $new_file_path,
          'post_mime_type' => $type,
          'post_title'     => preg_replace('/\.[^.]+$/', '', $name),
          'post_content'   => '',
          'post_status'    => 'inherit'
        );

        $image_id = wp_insert_attachment($attachment, $new_file_path);

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata($image_id, $new_file_path);

        wp_update_attachment_metadata($image_id, $attach_data);

        $json[] = array(
          'id'         => $image_id,
          'source_url' => wp_get_attachment_url($image_id)
        );
      }
    }

    return rest_ensure_response(array('status' => true, 'data' => $json));
  }
  
  public function insert_font($request)
  {
    // Get the path to the upload directory.
    $wp_upload_dir = wp_upload_dir();
    $fonts = $request->get_file_params();
    $json = array();
    foreach ($fonts as $font) {
      $name = $font['name'];
      $new_file_path = $wp_upload_dir['path'] . '/' . $name;
      $new_file_url = $wp_upload_dir['url'] . '/' . $name;

      if (move_uploaded_file($font['tmp_name'], $new_file_path)) {
        $json = array(
		  'status' => true,
          'source_url' => $new_file_url
        );
      }
    }

    return rest_ensure_response($json);
  }

  public function save_license($request){
    $data = $request->get_json_params();
    update_option('wprne_license_data', $data);
    return rest_ensure_response(array('status' => true));
  }
}
