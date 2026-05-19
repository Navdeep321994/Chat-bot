<?php
class SLCBP_Frontend {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'render_widget' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'slcbp-frontend-style', SLCBP_PLUGIN_URL . 'assets/css/frontend.css', array(), SLCBP_VERSION );
        wp_enqueue_script( 'slcbp-frontend-script', SLCBP_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), SLCBP_VERSION, true );
        
        wp_localize_script( 'slcbp-frontend-script', 'slcbp_ajax', array(
            'api_url' => rest_url( 'slcbp/v1/chat' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'new_session_id' => 'sess_' . bin2hex(random_bytes(16))
        ) );
    }

    public function render_widget() {
        require_once SLCBP_PLUGIN_DIR . 'templates/frontend-widget.php';
    }
}
