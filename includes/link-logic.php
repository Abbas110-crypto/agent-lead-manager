<?php
// includes/link-logic.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Listen for the "Generate" request on every page load
add_action('init', 'alm_handle_link_generation');

function alm_handle_link_generation() {
    
    // 1. TRIGGER CHECK: Is this our "Magic Link" request?
    if ( isset($_GET['alm_action']) && $_GET['alm_action'] == 'generate_link' ) {
        
        // Security: Must be logged in
        if ( !is_user_logged_in() ) {
            wp_die('You must be logged in to generate links.');
        }

        global $wpdb;
        $current_user_id = get_current_user_id();
        
        // 2. SANITIZE INPUTS
        $lead_id = intval($_GET['lead_id']);
        $raw_url = esc_url_raw($_GET['url']);
        
        if ( empty($raw_url) || empty($lead_id) ) {
            wp_die('Error: Missing URL or Lead ID.');
        }

        // 3. DETECT PLATFORM & GENERATE LINK
        // This function (defined below) does the affiliate math
        $link_data = alm_calculate_affiliate_link( $raw_url );
        $final_url = $link_data['url'];
        $platform  = $link_data['platform'];

        // 4. SAVE ORDER TO DATABASE
        $table_orders = $wpdb->prefix . 'crm_orders';
        $table_leads  = $wpdb->prefix . 'crm_leads';
        $current_time = current_time('mysql');

        $wpdb->insert( 
            $table_orders, 
            array( 
                'lead_id'       => $lead_id, 
                'agent_id'      => $current_user_id,
                'platform'      => $platform,
                'original_url'  => $raw_url,
                'affiliate_url' => $final_url,
                'order_status'  => 'generated',
                'time'          => $current_time
            ) 
        );

        // 5. UPDATE LEAD STATUS TO "CONVERTED"
        // Since we generated a link, we assume the sale is in progress
        $wpdb->update( 
            $table_leads, 
            array( 'status' => 'converted' ), 
            array( 'id' => $lead_id ) 
        );

        // 6. SHOW SUCCESS SCREEN
        // We output raw HTML here because we are interrupting the page load
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Link Generated</title>
            <style>
                body { font-family: sans-serif; background: #f4f6f9; text-align: center; padding: 50px; }
                .box { background: white; padding: 40px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
                h1 { color: #2ecc71; margin-top: 0; }
                textarea { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px; margin: 20px 0; }
                .btn { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
                .platform-badge { background: #eee; padding: 5px 10px; border-radius: 12px; font-size: 12px; color: #555; text-transform: uppercase; }
            </style>
        </head>
        <body>
            <div class="box">
                <h1>Success! Order Created.</h1>
                <p>Platform Detected: <span class="platform-badge"><?php echo esc_html($platform); ?></span></p>
                <p>Copy this link and send it to the customer:</p>
                
                <textarea rows="4" id="affLink"><?php echo esc_textarea($final_url); ?></textarea>
                
                <br>
                <button onclick="copyToClipboard()" class="btn" style="background:#2ecc71; border:none; cursor:pointer;">Copy Link</button>
                <a href="<?php echo home_url('/agent-dashboard/'); ?>" class="btn">Back to Dashboard</a>
            </div>

            <script>
                function copyToClipboard() {
                    var copyText = document.getElementById("affLink");
                    copyText.select();
                    document.execCommand("copy");
                    alert("Copied to clipboard!");
                }
            </script>
        </body>
        </html>
        <?php
        exit; // Stop WordPress from loading the rest of the page
    }
}

/**
 * THE AFFILIATE LOGIC ENGINE
 * Edit the IDs below with your real affiliate IDs.
 */
function alm_calculate_affiliate_link( $url ) {
    $host = parse_url($url, PHP_URL_HOST);
    $platform = 'Other';
    $final_url = $url;

    // --- AMAZON ---
    if ( strpos($host, 'amazon') !== false || strpos($host, 'amzn') !== false ) {
        $platform = 'Amazon';
        // 1. Remove existing tags to be safe
        $url = remove_query_arg('tag', $url);
        // 2. Add your tag
        $final_url = add_query_arg('tag', 'YOUR_AMAZON_STORE_ID-20', $url);
    }

    // --- WALMART (Impact Radius Example) ---
    elseif ( strpos($host, 'walmart') !== false ) {
        $platform = 'Walmart';
        // Walmart links usually look like: https://goto.walmart.com/c/YOUR_ID/...
        // You need your specific Impact Radius tracking base URL here
        $tracking_base = 'https://goto.walmart.com/c/YOUR_IMPACT_ID/YOUR_AD_ID';
        $final_url = $tracking_base . '?u=' . urlencode($url);
    }

    // --- EBAY (eBay Partner Network) ---
    elseif ( strpos($host, 'ebay') !== false ) {
        $platform = 'eBay';
        // Example logic for EPN
        $final_url = 'https://www.ebay.com/rover/1/711-53200-19255-0/1?mpre=' . urlencode($url) . '&campid=YOUR_CAMPAIGN_ID&toolid=10001';
    }

    // --- TARGET (Impact Radius) ---
    elseif ( strpos($host, 'target') !== false ) {
        $platform = 'Target';
        $tracking_base = 'https://goto.target.com/c/YOUR_IMPACT_ID/YOUR_AD_ID';
        $final_url = $tracking_base . '?u=' . urlencode($url);
    }

    // --- ALIEXPRESS ---
    elseif ( strpos($host, 'aliexpress') !== false ) {
        $platform = 'AliExpress';
        // Requires API usually, but Portals allows deep linking sometimes
        $final_url = 'https://s.click.aliexpress.com/deep_link.htm?dl_target_url=' . urlencode($url) . '&aff_short_key=YOUR_KEY';
    }

    // --- GENERIC FALLBACK ---
    else {
        // Try to identify name for database even if we don't have an affiliate ID
        if (strpos($host, 'shein')) $platform = 'Shein';
        if (strpos($host, 'temu')) $platform = 'Temu';
        if (strpos($host, 'wayfair')) $platform = 'Wayfair';
        if (strpos($host, 'homedepot')) $platform = 'Home Depot';
    }

    return array(
        'url' => $final_url,
        'platform' => $platform
    );
}
?>