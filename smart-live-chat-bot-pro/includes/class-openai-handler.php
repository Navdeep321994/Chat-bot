<?php
class SLCBP_OpenAI_Handler {
    private $api_key;
    private $model;
    private $system_prompt;

    public function __construct() {
        $this->api_key = get_option( 'slcbp_openai_api_key' );
        $this->model = get_option( 'slcbp_openai_model', 'gpt-4o-mini' );
        $this->system_prompt = get_option( 'slcbp_system_prompt', 'You are a helpful customer support bot.' );
    }

    public function get_response( $user_message, $context_messages = array() ) {
        if ( empty( $this->api_key ) ) {
            return "Error: OpenAI API Key is not set.";
        }

        $messages = array(
            array(
                'role' => 'system',
                'content' => $this->system_prompt
            )
        );

        foreach ( $context_messages as $msg ) {
            $role = ( $msg->sender_type === 'user' ) ? 'user' : 'assistant';
            $messages[] = array(
                'role' => $role,
                'content' => $msg->message
            );
        }

        // Add the current message if it's not already in context
        if ( empty($context_messages) || end($context_messages)->message !== $user_message ) {
             $messages[] = array(
                'role' => 'user',
                'content' => $user_message
            );
        }

        $body = array(
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return "Sorry, I am having trouble connecting right now.";
        }

        $body_response = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body_response['choices'][0]['message']['content'] ) ) {
            return $body_response['choices'][0]['message']['content'];
        }

        // Return the actual error from OpenAI for debugging
        if ( isset( $body_response['error']['message'] ) ) {
            return "API Error: " . $body_response['error']['message'];
        }

        return "Sorry, I couldn't understand that. (Debug: " . print_r($body_response, true) . ")";
    }
}
