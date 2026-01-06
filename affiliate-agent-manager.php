<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/db-setup.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/link-logic.php';

if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/distributor-page.php';
}

require_once plugin_dir_path( __FILE__ ) . 'public/agent-dashboard.php';

register_activation_hook( __FILE__, 'alm_create_database_tables' );

add_action('wp_enqueue_scripts', 'alm_load_scripts');
add_action('admin_enqueue_scripts', 'alm_load_scripts');

function alm_load_scripts() {
    wp_enqueue_style( 
        'alm-master-style', 
        plugin_dir_url( __FILE__ ) . 'assets/css/style.css', 
        array(), 
        '1.0.0' 
    );

    wp_enqueue_script( 
        'alm-master-script', 
        plugin_dir_url( __FILE__ ) . 'assets/js/script.js', 
        array(),      
        '1.0.0',     
        true          
    );
}

?>