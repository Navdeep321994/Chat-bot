<?php
class SLCBP_API {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        // Frontend user sends message
        register_rest_route( 'slcbp/v1', '/chat', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'handle_chat_message' ),
            'permission_callback' => '__return_true'
        ) );

        // Frontend user fetches messages
        register_rest_route( 'slcbp/v1', '/chat', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_messages_frontend' ),
            'permission_callback' => '__return_true'
        ) );

        // Admin fetches conversations
        register_rest_route( 'slcbp/v1', '/admin/conversations', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_conversations_admin' ),
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ) );

        // Admin fetches messages for a conversation
        register_rest_route( 'slcbp/v1', '/admin/chat', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_messages_admin' ),
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ) );

        // Admin sends message
        register_rest_route( 'slcbp/v1', '/admin/chat', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'admin_send_message' ),
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ) );

        // Admin deletes conversation
        register_rest_route( 'slcbp/v1', '/admin/chat', array(
            'methods'  => 'DELETE',
            'callback' => array( $this, 'admin_delete_conversation' ),
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ) );
    }

    public function handle_chat_message( WP_REST_Request $request ) {
        // Rate Limiting to prevent spam
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
        $transient_key = 'slcbp_rl_msg_' . md5($ip);
        $attempts = get_transient($transient_key);
        
        if ($attempts && $attempts >= 15) {
            return new WP_Error( 'rate_limit', 'You are sending messages too fast. Please wait a minute.', array( 'status' => 429 ) );
        }
        set_transient($transient_key, ($attempts ? $attempts + 1 : 1), 60);

        $message = sanitize_text_field( $request->get_param( 'message' ) );
        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
        
        if ( empty( $message ) || empty( $session_id ) ) {
            return new WP_Error( 'no_message', 'Message and session ID required', array( 'status' => 400 ) );
        }

        $msg_id = $this->save_message( $session_id, 'user', $message );

        // Include OpenAI Handler
        require_once SLCBP_PLUGIN_DIR . 'includes/class-openai-handler.php';
        $openai = new SLCBP_OpenAI_Handler();
        
        // Fetch context (last 10 messages)
        global $wpdb;
        $table_conv = $wpdb->prefix . 'chat_conversations';
        $table_msg = $wpdb->prefix . 'chat_messages';
        
        $context_messages = $wpdb->get_results( $wpdb->prepare( 
            "SELECT m.sender_type, m.message 
             FROM $table_msg m
             INNER JOIN $table_conv c ON m.conversation_id = c.id
             WHERE c.session_id = %s
             ORDER BY m.id DESC LIMIT 10", 
            $session_id 
        ) );
        $context_messages = array_reverse( $context_messages );
        
        $bot_reply = $openai->get_response( $message, $context_messages );
        $bot_msg_id = $this->save_message( $session_id, 'bot', $bot_reply );

        return rest_ensure_response( array(
            'status' => 'success',
            'reply' => $bot_reply,
            'message_id' => $msg_id,
            'bot_message_id' => $bot_msg_id
        ) );
    }

    public function get_messages_frontend( WP_REST_Request $request ) {
        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
        $last_id = intval( $request->get_param( 'last_id' ) );
        
        if ( empty( $session_id ) ) {
            return new WP_Error( 'no_session', 'Session ID required', array( 'status' => 400 ) );
        }

        $messages = $this->get_new_messages( $session_id, $last_id );
        return rest_ensure_response( array( 'status' => 'success', 'messages' => $messages ) );
    }

    public function get_conversations_admin( WP_REST_Request $request ) {
        global $wpdb;
        $table_conv = $wpdb->prefix . 'chat_conversations';
        $conversations = $wpdb->get_results( "SELECT * FROM $table_conv ORDER BY created_at DESC" );
        return rest_ensure_response( array( 'status' => 'success', 'conversations' => $conversations ) );
    }

    public function get_messages_admin( WP_REST_Request $request ) {
        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
        $last_id = intval( $request->get_param( 'last_id' ) );
        
        if ( empty( $session_id ) ) {
            return new WP_Error( 'no_session', 'Session ID required', array( 'status' => 400 ) );
        }

        $messages = $this->get_new_messages( $session_id, $last_id );
        return rest_ensure_response( array( 'status' => 'success', 'messages' => $messages ) );
    }

    public function admin_send_message( WP_REST_Request $request ) {
        $message = sanitize_text_field( $request->get_param( 'message' ) );
        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
        
        if ( empty( $message ) || empty( $session_id ) ) {
            return new WP_Error( 'no_message', 'Message and session ID required', array( 'status' => 400 ) );
        }

        $msg_id = $this->save_message( $session_id, 'bot', $message );

        return rest_ensure_response( array( 'status' => 'success', 'message_id' => $msg_id ) );
    }

    public function admin_delete_conversation( WP_REST_Request $request ) {
        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
        
        if ( empty( $session_id ) ) {
            return new WP_Error( 'no_session', 'Session ID required', array( 'status' => 400 ) );
        }

        global $wpdb;
        $table_conv = $wpdb->prefix . 'chat_conversations';
        $table_msg = $wpdb->prefix . 'chat_messages';

        $conv = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_conv WHERE session_id = %s", $session_id ) );
        if ( $conv ) {
            $wpdb->delete( $table_msg, array( 'conversation_id' => $conv->id ) );
            $wpdb->delete( $table_conv, array( 'id' => $conv->id ) );
        }

        return rest_ensure_response( array( 'status' => 'success' ) );
    }

    public function set_user_name( WP_REST_Request $request ) {
        // Rate Limiting to prevent spam
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
        $transient_key = 'slcbp_rl_name_' . md5($ip);
        $attempts = get_transient($transient_key);
        
        if ($attempts && $attempts >= 5) {
            return new WP_Error( 'rate_limit', 'Too many requests.', array( 'status' => 429 ) );
        }
        set_transient($transient_key, ($attempts ? $attempts + 1 : 1), 60);

        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        
        if ( empty( $session_id ) || empty( $name ) ) {
            return new WP_Error( 'invalid', 'Missing data', array( 'status' => 400 ) );
        }

        global $wpdb;
        $table_conv = $wpdb->prefix . 'chat_conversations';
        
        // Find or create conversation
        $conv_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_conv WHERE session_id = %s LIMIT 1", $session_id ) );
        if ( ! $conv_id ) {
            $wpdb->insert( $table_conv, array( 'session_id' => $session_id, 'user_name' => $name ) );
        } else {
            $wpdb->update( $table_conv, array( 'user_name' => $name ), array( 'session_id' => $session_id ) );
        }

        return rest_ensure_response( array( 'status' => 'success' ) );
    }

    private function save_message( $session_id, $sender_type, $message ) {
        global $wpdb;
        $table_conv = $wpdb->prefix . 'chat_conversations';
        $table_msg = $wpdb->prefix . 'chat_messages';
        
        $conv_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_conv WHERE session_id = %s LIMIT 1", $session_id ) );
        if ( ! $conv_id ) {
            $wpdb->insert( $table_conv, array( 'session_id' => $session_id ) );
            $conv_id = $wpdb->insert_id;
        }

        $wpdb->insert( $table_msg, array(
            'conversation_id' => $conv_id,
            'sender_type' => $sender_type,
            'message' => $message
        ) );

        return $wpdb->insert_id;
    }

    private function get_new_messages( $session_id, $last_id ) {
        global $wpdb;
        $table_conv = $wpdb->prefix . 'chat_conversations';
        $table_msg = $wpdb->prefix . 'chat_messages';
        
        // Optimized with a JOIN to reduce database queries
        $messages = $wpdb->get_results( $wpdb->prepare( 
            "SELECT m.id, m.sender_type, m.message 
             FROM $table_msg m
             INNER JOIN $table_conv c ON m.conversation_id = c.id
             WHERE c.session_id = %s AND m.id > %d 
             ORDER BY m.id ASC", 
            $session_id, $last_id 
        ) );
        
        return $messages ? $messages : array();
    }
}
