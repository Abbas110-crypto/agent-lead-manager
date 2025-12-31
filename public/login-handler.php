<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. THE SHORTCODE [alm_login_form]
 * Place this shortcode on any blank page (e.g., "Portal Login").
 */
add_shortcode('alm_login_form', 'alm_render_login_form');

function alm_render_login_form() {
    
    // If the user is ALREADY logged in, do not show the form.
    // Instead, kick them to their correct dashboard immediately.
    if (is_user_logged_in()) {
        alm_redirect_user_based_on_role();
        exit;
    }

    // --- CSS STYLING ---
    // This makes the form look professional and hides standard WP styling
    $output = '
    <style>
        .alm-login-wrapper {
            max-width: 400px;
            margin: 50px auto;
            padding: 40px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            border-top: 5px solid #0073aa;
        }
        .alm-login-wrapper h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
            color: #333;
            font-weight: 600;
        }
        /* Style the inputs */
        .alm-login-wrapper label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .alm-login-wrapper input[type="text"], 
        .alm-login-wrapper input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fafafa;
            font-size: 16px;
            box-sizing: border-box; /* Fix padding issues */
        }
        .alm-login-wrapper input[type="text"]:focus, 
        .alm-login-wrapper input[type="password"]:focus {
            border-color: #0073aa;
            background: #fff;
            outline: none;
        }
        /* Style the button */
        .alm-login-wrapper input[type="submit"] {
            width: 100%;
            background: #0073aa;
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .alm-login-wrapper input[type="submit"]:hover {
            background: #005177;
        }
        /* Extra links */
        .login-remember { font-size: 13px; color: #777; margin-bottom: 15px; }
        .alm-login-footer { text-align:center; margin-top: 20px; font-size: 12px; color: #999; }
    </style>
    ';

    $output .= '<div class="alm-login-wrapper">';
    $output .= '<h2>Agent Portal Access</h2>';
    
    // The WordPress function that generates the actual form fields
    $args = array(
        'echo'           => false,
        'redirect'       => site_url( $_SERVER['REQUEST_URI'] ), // Reload page to trigger the redirect filter below
        'form_id'        => 'alm-login-form',
        'label_username' => __( 'Username or Email' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in'   => __( 'Secure Login' ),
        'remember'       => true
    );
    
    $output .= wp_login_form($args);
    $output .= '<div class="alm-login-footer">Authorized Personnel Only</div>';
    $output .= '</div>';

    return $output;
}

/**
 * 2. THE REDIRECT LOGIC (The Traffic Cop)
 * This function runs automatically whenever a user successfully logs in.
 */
add_filter('login_redirect', 'alm_custom_login_redirect', 10, 3);

function alm_custom_login_redirect($redirect_to, $request, $user) {
    // If login failed (error object returned), do nothing
    if (isset($user->errors) && is_array($user->errors)) {
        return $redirect_to;
    }

    // Check the user's role
    if (isset($user->roles) && is_array($user->roles)) {
        
        // CASE A: If user is an ADMINISTRATOR (The Boss)
        if (in_array('administrator', $user->roles)) {
            // Redirect to the backend CSV uploader page
            return admin_url('admin.php?page=alm-distributor');
        } 
        
        // CASE B: If user is anyone else (The Agent)
        else {
            // Redirect to the frontend Dashboard Page
            // IMPORTANT: Make sure this path matches your actual page slug!
            return home_url('/agent-dashboard/');
        }
    }

    return $redirect_to;
}

/**
 * 3. HELPER FUNCTION
 * Used by the shortcode to manually move users if they visit the login page while already logged in.
 */
function alm_redirect_user_based_on_role() {
    $user = wp_get_current_user();
    
    if (in_array('administrator', $user->roles)) {
        wp_redirect(admin_url('admin.php?page=alm-distributor'));
        exit;
    } else {
        wp_redirect(home_url('/agent-dashboard/'));
        exit;
    }
}
?>