<?php
class SLCBP_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function add_plugin_page() {
        add_menu_page(
            'Live Chat Bot', 
            'Live Chat Bot', 
            'manage_options', 
            'smart-live-chat-bot', 
            array( $this, 'create_admin_page' ), 
            'dashicons-format-chat', 
            30
        );

        add_submenu_page(
            'smart-live-chat-bot',
            'Settings',
            'Settings',
            'manage_options',
            'smart-live-chat-bot-settings',
            array( $this, 'create_settings_page' )
        );
    }

    public function create_admin_page() {
        require_once SLCBP_PLUGIN_DIR . 'templates/admin-chat.php';
    }

    public function create_settings_page() {
        require_once SLCBP_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    public function register_settings() {
        register_setting( 'slcbp_option_group', 'slcbp_openai_api_key' );
        register_setting( 'slcbp_option_group', 'slcbp_openai_model' );
        register_setting( 'slcbp_option_group', 'slcbp_system_prompt' );
    }

    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_smart-live-chat-bot' !== $hook ) {
            return;
        }
        
        wp_enqueue_style( 'slcbp-admin-style', SLCBP_PLUGIN_URL . 'assets/css/admin.css', array(), SLCBP_VERSION );
        wp_enqueue_script( 'slcbp-admin-script', SLCBP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SLCBP_VERSION, true );
        
        wp_localize_script( 'slcbp-admin-script', 'slcbp_admin_ajax', array(
            'api_url' => rest_url( 'slcbp/v1' ),
            'nonce'   => wp_create_nonce( 'wp_rest' )
        ) );
    }
}
