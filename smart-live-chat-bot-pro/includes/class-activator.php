<?php
class SLCBP_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_conversations = $wpdb->prefix . 'chat_conversations';
        $sql1 = "CREATE TABLE $table_conversations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            session_id varchar(100) NOT NULL,
            user_name varchar(100) DEFAULT '',
            agent_id bigint(20) DEFAULT 0,
            status varchar(20) DEFAULT 'open',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql1 );

        $table_messages = $wpdb->prefix . 'chat_messages';
        $sql2 = "CREATE TABLE $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            sender_type varchar(20) NOT NULL,
            sender_id bigint(20) DEFAULT 0,
            message text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql2 );

        $table_agents = $wpdb->prefix . 'chat_agents';
        $sql3 = "CREATE TABLE $table_agents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'offline',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql3 );

        $table_leads = $wpdb->prefix . 'chat_leads';
        $sql4 = "CREATE TABLE $table_leads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(50) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql4 );
        
        // Add default settings
        add_option('slcbp_openai_api_key', '');
        add_option('slcbp_openai_model', 'gpt-4o-mini');
        add_option('slcbp_system_prompt', 'You are a helpful customer support bot.');
    }
}
