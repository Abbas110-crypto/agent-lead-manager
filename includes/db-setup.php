<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
function alm_create_database_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

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

    dbDelta( $sql_leads );


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

    dbDelta( $sql_orders );

    add_option( 'alm_db_version', '1.0' );
}
?>