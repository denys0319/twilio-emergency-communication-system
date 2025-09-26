<?php
/**
 * Database management class for Emergency Communication System
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECS_Database {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Contact Groups table
        $table_groups = $this->wpdb->prefix . 'ecs_contact_groups';
        $sql_groups = "CREATE TABLE $table_groups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        
        // Contacts table
        $table_contacts = $this->wpdb->prefix . 'ecs_contacts';
        $sql_contacts = "CREATE TABLE $table_contacts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            phone varchar(20) NOT NULL,
            name varchar(255),
            group_id mediumint(9),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY phone (phone),
            KEY group_id (group_id)
        ) $charset_collate;";
        
        // Message Templates table
        $table_templates = $this->wpdb->prefix . 'ecs_message_templates';
        $sql_templates = "CREATE TABLE $table_templates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            subject varchar(255),
            message text NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Message History table
        $table_messages = $this->wpdb->prefix . 'ecs_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            twilio_sid varchar(255),
            recipient_phone varchar(20) NOT NULL,
            recipient_name varchar(255),
            message text NOT NULL,
            from_number varchar(20),
            status varchar(50) DEFAULT 'pending',
            sent_at datetime,
            delivered_at datetime,
            error_code varchar(50),
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY twilio_sid (twilio_sid),
            KEY recipient_phone (recipient_phone),
            KEY status (status)
        ) $charset_collate;";
        
        // Scheduled Messages table
        $table_scheduled = $this->wpdb->prefix . 'ecs_scheduled_messages';
        $sql_scheduled = "CREATE TABLE $table_scheduled (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            recipients text NOT NULL,
            message text NOT NULL,
            from_number varchar(20),
            send_time datetime NOT NULL,
            status varchar(50) DEFAULT 'pending',
            twilio_service_sid varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY send_time (send_time),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_groups);
        dbDelta($sql_contacts);
        dbDelta($sql_templates);
        dbDelta($sql_messages);
        dbDelta($sql_scheduled);
        
        // Insert default message template
        $this->insert_default_template();
    }
    
    /**
     * Insert default message template
     */
    private function insert_default_template() {
        $table_templates = $this->wpdb->prefix . 'ecs_message_templates';
        
        $default_template = array(
            'name' => 'Emergency Alert',
            'subject' => 'Emergency Communication',
            'message' => 'This is an emergency alert. Please take appropriate action immediately.',
            'is_active' => 1
        );
        
        $this->wpdb->insert($table_templates, $default_template);
    }
    
    /**
     * Drop all tables
     */
    public function drop_tables() {
        $tables = array(
            $this->wpdb->prefix . 'ecs_scheduled_messages',
            $this->wpdb->prefix . 'ecs_messages',
            $this->wpdb->prefix . 'ecs_message_templates',
            $this->wpdb->prefix . 'ecs_contacts',
            $this->wpdb->prefix . 'ecs_contact_groups'
        );
        
        foreach ($tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Get table names
     */
    public function get_table_names() {
        return array(
            'groups' => $this->wpdb->prefix . 'ecs_contact_groups',
            'contacts' => $this->wpdb->prefix . 'ecs_contacts',
            'templates' => $this->wpdb->prefix . 'ecs_message_templates',
            'messages' => $this->wpdb->prefix . 'ecs_messages',
            'scheduled' => $this->wpdb->prefix . 'ecs_scheduled_messages'
        );
    }
    
    /**
     * Check if all required tables exist
     */
    public function check_tables_exist() {
        $tables = $this->get_table_names();
        $missing_tables = array();
        
        foreach ($tables as $table_name) {
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $missing_tables[] = $table_name;
            }
        }
        
        return array(
            'all_exist' => empty($missing_tables),
            'missing_tables' => $missing_tables
        );
    }
    
    /**
     * Create missing tables
     */
    public function create_missing_tables() {
        $check_result = $this->check_tables_exist();
        
        if (!$check_result['all_exist']) {
            $this->create_tables();
            return true;
        }
        
        return false;
    }
}
