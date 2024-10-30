<?php
/**
* Plugin Name: Closer: Chat & Video Calls for Sales
* Plugin URI: https://www.closer.app/
* Description: This plugin installs the Closer widget on your website
* Version: 1.0
* Requires at least: 4.9
* Requires PHP:      5.2
* Author: Closer
* License: GPLv2
**/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/** Step 2 (from text above). */
add_action( 'admin_menu', 'closer_app_plugin_menu' );
add_action( 'admin_init', 'closer_admin_init' );

function closer_admin_init(){
  $closer_option_orgId_args = array(
          'type' => 'string',
          'sanitize_callback' => 'sanitize_text_field',
          'default' => NULL,
          );
  register_setting( 'closer-option-group', 'closer_option_orgId', $closer_option_orgId_args );
}

/** Step 1. */
function closer_app_plugin_menu() {
  add_menu_page(__('Closer Settings', 'Closer'), __('Closer Settings', 'Closer'), 'administrator', __FILE__, 'closer_plugin_options' , 'https://closer.app/favicon-32x32.png');
}

/** Step 3. */
function closer_plugin_options() {
  $closer_option_orgId = sanitize_key($_GET["closer_option_orgId"]);
  if (isset($closer_option_orgId) && !empty($closer_option_orgId)) {
    update_option("closer_option_orgId", $closer_option_orgId);
  }

  $closer_option_orgId = get_option('closer_option_orgId');

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	echo '<div class="wrap">';
	echo '<h1>Enter your Closer ID</h1>';
  echo '<form method="post" action="options.php">';
  settings_fields( 'closer-option-group' );
  do_settings_sections( 'closer-option-group' );
  echo '<table class="form-table">';
  echo '<tr valign="top">';
  echo '<th scope="row">Closer Org ID</th>';
  echo '<td><input type="text" name="closer_option_orgId" value="'.$closer_option_orgId.'" /></td>';
  echo '</tr>';
  echo '</table>';

  submit_button();
  echo '</form>';
	echo '</div>';
}

add_action('wp_head', 'closer_hook_head', 1);

function closer_identify_wordpress_user() {
  $output = "";


  if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
  }

  if (!isset($current_user)) {
    return "";
  }

  $email = $current_user->user_email;
  $firstName = $current_user->user_firstname;
  $lastName = $current_user->user_lastname;

  if (!empty($email)) {
    $output .= 'email: "' . $email . '",';
  }

  if (!empty($firstName)) {
    $output .= 'firstName: "' . $firstName . '",';
  }

  if (!empty($lastName)) {
    $output .= 'lastName: "' . $lastName . '",';
  }

  return $output;
}

function closer_identify_woocommerce_customer() {
  $output = "";
  if (!class_exists("WooCommerce") || is_admin()) {
    return $output;
  }

  $customer = WC()->session->get("customer");

  if ($customer == NULL) {
    return $output;
  }

  if (isset($customer["first_name"]) && !empty($customer["first_name"])) {
    $output .= 'firstName: "' . $customer["first_name"] . '",';
  }
  if (isset($customer["last_name"]) && !empty($customer["last_name"])) {
    $output .= 'lastName: "' . $customer["last_name"] . '",';
  }

  return $output;
}


function closer_hook_head() {
  $closer_option_orgId = get_option('closer_option_orgId');

  if (!isset($closer_option_orgId) || empty($closer_option_orgId)) {
    return;
  }

  $identify = "";
  $identify .= closer_identify_wordpress_user();
  $identify .= closer_identify_woocommerce_customer();

  $output="<script id='closer-widget-script'>
      (function (c, l, o, s, e, r) {
          c.closer = c.closer || { q: [] };
          ['init', 'identify'].forEach(function (m) {
              c.closer[m] = function () {
                  this.q.push({ method: m, args: arguments });
                  }
              });
          c.closer['scriptUrl'] = s;
          e = l.createElement(o);
          e.async = 1;
          e.src = s;
          r = l.getElementsByTagName(o)[0];
          r.parentNode.insertBefore(e, r);
      })(window, document, 'script', 'https://widget.closer.app/widget.js');

          closer.init({
              orgId: '$closer_option_orgId',
          })
          ";


  if (!empty($identify)){
    $output .= "
        closer.identify({
          $identify
        });
    ";
  }

  $output .= "</script>";
  echo $output;
}

?>
