<?php

/**
 * @since             1.0.0
 * @package           WPRNE
 *
 * @wordpress-plugin
 * Plugin Name: Mobile App Editor
 * Plugin URI:        https://wprne.xyz/
 * Description:       Drag and drop editor to build mobile app using wordpress.
 * Version:           1.3.1
 * Author:            Moble App Editor
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wprne
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
define( "WPRNE_VERSION", "1.3.1" );
define( "WPRNE_PLUGIN_URL", plugins_url( '', __FILE__ ) );
    
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wprne-activator.php
 */
function activate_wprne()
{
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wprne-activator.php';
    Wprne_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wprne-deactivator.php
 */
function deactivate_wprne()
{
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wprne-deactivator.php';
    Wprne_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wprne' );
register_deactivation_hook( __FILE__, 'deactivate_wprne' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wprne.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wprne()
{
    $plugin = new WPRNE();
    $plugin->run();
}

run_wprne();

