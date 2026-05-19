<?php
/**
 * Plugin Name: Smart Live Chat Bot Pro
 * Description: Complete, production-ready WordPress plugin with both AI chatbot and human live chat functionality.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: smart-live-chat-bot-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'SLCBP_VERSION', '1.0.0' );
define( 'SLCBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLCBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once SLCBP_PLUGIN_DIR . 'includes/class-activator.php';
require_once SLCBP_PLUGIN_DIR . 'includes/class-admin.php';
require_once SLCBP_PLUGIN_DIR . 'includes/class-api.php';
require_once SLCBP_PLUGIN_DIR . 'includes/class-frontend.php';

// Activation Hook
register_activation_hook( __FILE__, array( 'SLCBP_Activator', 'activate' ) );

// Initialize components
function slcbp_init() {
    new SLCBP_Admin();
    new SLCBP_API();
    new SLCBP_Frontend();
}
add_action( 'plugins_loaded', 'slcbp_init' );
