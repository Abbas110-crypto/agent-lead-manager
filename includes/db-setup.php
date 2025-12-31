<?php
// includes/db-setup.php

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main function to create or update database tables.
 * This runs when the plugin is activated.
 */
function alm_create_database_tables() {
    global $wpdb;

    // Use the correct character set for the site (supports emojis/international text)
    $charset_collate = $wpdb->get_charset_collate();

    // Load the upgrade library required for dbDelta
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // --- TABLE 1: LEADS (The Customer List) ---
    $table_leads = $wpdb->prefix . 'crm_leads';
    
    $sql_leads = "CREATE TABLE $table_leads (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        phone varchar(20) NOT NULL,
        assigned_agent bigint(20) UNSIGNED DEFAULT 0,
        status varchar(20) DEFAULT 'new',
        notes text,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY assigned_agent (assigned_agent),
        KEY status (status)
    ) $charset_collate;";

    // Create/Update the leads table
    dbDelta( $sql_leads );


    // --- TABLE 2: ORDERS (The Affiliate Links) ---
    // This stores the history of every link generated
    $table_orders = $wpdb->prefix . 'crm_orders';

    $sql_orders = "CREATE TABLE $table_orders (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        lead_id mediumint(9) NOT NULL,
        agent_id bigint(20) UNSIGNED NOT NULL,
        platform varchar(50) NOT NULL,
        original_url text NOT NULL,
        affiliate_url text NOT NULL,
        order_status varchar(20) DEFAULT 'generated',
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY lead_id (lead_id),
        KEY agent_id (agent_id)
    ) $charset_collate;";

    // Create/Update the orders table
    dbDelta( $sql_orders );

    // Store the database version (useful for future updates)
    add_option( 'alm_db_version', '1.0' );
}
?>