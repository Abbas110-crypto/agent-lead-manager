<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/db-setup.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/link-logic.php';

// 2. Load the Admin UI (Only if we are in the admin area)
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/distributor-page.php';
}

// 3. Load the Frontend Agent UI
require_once plugin_dir_path( __FILE__ ) . 'public/agent-dashboard.php';

// 4. Register the "Activate" trigger
register_activation_hook( __FILE__, 'alm_create_database_tables' );

// Load the CSS file
add_action('wp_enqueue_scripts', 'alm_load_scripts');
add_action('admin_enqueue_scripts', 'alm_load_scripts');

function alm_load_scripts() {
    // 1. Load the CSS (Style)
    wp_enqueue_style( 
        'alm-master-style', 
        plugin_dir_url( __FILE__ ) . 'assets/css/style.css', 
        array(), 
        '1.0.0' 
    );

    // 2. Load the JS (Script)
    wp_enqueue_script( 
        'alm-master-script', 
        plugin_dir_url( __FILE__ ) . 'assets/js/script.js', 
        array(),      // Dependencies (none)
        '1.0.0',      // Version
        true          // Load in Footer (Better for performance)
    );
}

?>