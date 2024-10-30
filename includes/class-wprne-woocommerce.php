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
 * WPRNE Woocommerce support.
 */
class WPRNE_Woocommerce {

  public function woocommerce_maybe_add_multiple_products_to_cart($url = false) {
	
	$add_to_cart = isset($_REQUEST['add-to-cart']) ? sanitize_text_field($_REQUEST['add-to-cart']) : null;
    
	// Make sure WC is installed, and add-to-cart qauery arg exists, and contains at least one comma.
	if ( ! class_exists( 'WC_Form_Handler' ) || empty( $add_to_cart ) || false === strpos( $add_to_cart, "," )) {
		return;
	}
	
	if( ! WC()->cart->is_empty() ){
		WC()->cart->empty_cart();
	}

	// Remove WooCommerce's hook, as it's useless (doesn't handle multiple products).
	remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 );

	$product_ids = explode( ',', $add_to_cart );
	$count       = count( $product_ids );
	$number      = 0;

	foreach ( $product_ids as $id_and_quantity ) {
		// Check for quantities defined in curie notation (<product_id>:<product_quantity>)
		
		$id_and_quantity = explode( ':', $id_and_quantity );
		$product_id = $id_and_quantity[0];

		$_REQUEST['quantity'] = ! empty( $id_and_quantity[1] ) ? absint( $id_and_quantity[1] ) : 1;

		if ( ++$number === $count ) {
			// Ok, final item, let's send it back to woocommerce's add_to_cart_action method for handling.
			$_REQUEST['add-to-cart'] = $product_id;

			return WC_Form_Handler::add_to_cart_action( $url );
		}

		$product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $product_id ) );
		$was_added_to_cart = false;
		$adding_to_cart    = wc_get_product( $product_id );

		if ( ! $adding_to_cart ) {
			continue;
		}

		$add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->get_type(), $adding_to_cart );

		// Variable product handling
		if ( 'variable' === $add_to_cart_handler ) {
			$this->wprne_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_variable', $product_id );

		// Grouped Products
		} elseif ( 'grouped' === $add_to_cart_handler ) {
			$this->wprne_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_grouped', $product_id );

		// Custom Handler
		} elseif ( has_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler ) ){
			do_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler, $url );

		// Simple Products
		} else {
			$this->wprne_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_simple', $product_id );
		}
	}
  }
  
  private function wprne_invoke_private_method( $class_name, $methodName ) {
	if ( version_compare( phpversion(), '5.3', '<' ) ) {
		throw new Exception( 'PHP version does not support ReflectionClass::setAccessible()', __LINE__ );
	}

	$args = func_get_args();
	unset( $args[0], $args[1] );
	$reflection = new ReflectionClass( $class_name );
	$method = $reflection->getMethod( $methodName );
	$method->setAccessible( true );

	//$args = array_merge( array( $class_name ), $args );
	$args = array_merge( array( $reflection ), $args );
	return call_user_func_array( array( $method, 'invoke' ), $args );
  }

  public function wprne_process_order_received($order_id){
    ?>
    <script>
	  if(window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage === "function"){
		window.ReactNativeWebView.postMessage('<?php echo esc_attr( $order_id ); ?>');		  
	  }
    </script>	
    <?php
  }
}