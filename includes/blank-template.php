<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package _s
 */
 
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


wp_head(); 
show_admin_bar(false);
the_content();
?>

 <style>
  html {
      overflow: scroll;
      overflow-x: hidden;
  }
  ::-webkit-scrollbar {
      width: 0px;  /* Remove scrollbar space */
      background: transparent;  /* Optional: just make scrollbar invisible */
  }
  /* Optional: show position indicator in red */
  ::-webkit-scrollbar-thumb {
      background: #FF0000;
  }
  </style>

<?php

wp_footer(); 

?>
