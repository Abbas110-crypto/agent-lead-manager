<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('admin_menu', 'alm_register_distributor_menu');

function alm_register_distributor_menu() {
    add_menu_page(
        'Lead Distributor', 
        'Lead Distributor', 
        'manage_options', 
        'alm-distributor', 
        'alm_render_distributor_page', 
        'dashicons-businessperson', 
        6
    );
}

function alm_render_distributor_page() {
    global $wpdb;

    $message = '';
    $message_type = ''; // 'success' or 'error'

    if (isset($_POST['alm_submit_csv']) && check_admin_referer('alm_csv_upload_action', 'alm_csv_nonce')) {
        
        if (!empty($_FILES['leads_csv']['tmp_name'])) {
            $result = alm_process_csv($_FILES['leads_csv']);
            $message = $result['msg'];
            $message_type = $result['type'];
        } else {
            $message = "Please select a file to upload.";
            $message_type = "error";
        }
    }

    $table_leads = $wpdb->prefix . 'crm_leads';
    $total_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_leads");
    
    $agents = get_users(['role__not_in' => ['administrator']]);
    $agent_count = count($agents);
    $active_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE status = 'new'");

    ?>
    
    <style>
        .alm-dashboard-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 1200px;
            margin: 20px 0;
        }
        .alm-header {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .alm-header h1 { margin: 0; color: #23282d; font-size: 24px; }
        
        .alm-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .alm-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #0073aa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .alm-card h3 { margin: 0 0 10px 0; color: #555; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .alm-card .number { font-size: 32px; font-weight: bold; color: #23282d; }
        
        .alm-upload-section {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            border: 2px dashed #ccd0d4;
        }
        .alm-upload-section h2 { margin-top: 0; }
        
        .alm-msg { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alm-msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alm-msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .alm-btn-primary {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .alm-btn-primary:hover { background: #005177; }
        .alm-helper-text { color: #666; font-style: italic; margin-top: 10px; }
    </style>

    <div class="alm-dashboard-wrapper">
        
        <div class="alm-header">
            <h1>Lead Distributor Command Center</h1>
            <div>Version 1.0</div>
        </div>

        <?php if ($message): ?>
            <div class="alm-msg <?php echo $message_type; ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <div class="alm-stats-grid">
            <div class="alm-card" style="border-left-color: #46b450;">
                <h3>Total Agents Available</h3>
                <div class="number"><?php echo intval($agent_count); ?></div>
            </div>
            <div class="alm-card" style="border-left-color: #f0ad4e;">
                <h3>Total Leads in System</h3>
                <div class="number"><?php echo intval($total_leads); ?></div>
            </div>
            <div class="alm-card" style="border-left-color: #d9534f;">
                <h3>Active (New) Leads</h3>
                <div class="number"><?php echo intval($active_leads); ?></div>
            </div>
        </div>

        <div class="alm-upload-section">
            <h2>Upload New Leads (CSV)</h2>
            <p>Select a CSV file with columns: <strong>Name, Phone</strong> (in that order).</p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('alm_csv_upload_action', 'alm_csv_nonce'); ?>
                
                <div style="margin: 20px 0;">
                    <input type="file" name="leads_csv" accept=".csv" required style="padding: 10px; background: #f0f0f1; border-radius: 4px;">
                </div>
                
                <input type="submit" name="alm_submit_csv" value="Upload & Distribute Equality" class="alm-btn-primary">
            </form>
            
            <p class="alm-helper-text">
                Note: Leads will be automatically split 50/50 (or equally) among the <?php echo $agent_count; ?> available agents.
            </p>
        </div>

    </div>
    <?php
}

// 3. The Logic Function (Handles the dirty work)
function alm_process_csv($file) {
    global $wpdb;

    // A. Get Agents (Subscribers)
    // IMPORTANT: Assuming your agents have the 'subscriber' role. 
    // If you made a custom role, change 'subscriber' to 'agent'.
    $agents = get_users(['role__not_in' => ['administrator'], 'fields' => 'ID']);
    
    if (empty($agents)) {
        return ['type' => 'error', 'msg' => 'Error: No agents (subscribers) found in the system to assign leads to.'];
    }

    $agent_count = count($agents);
    $agent_index = 0;
    
    // B. Open File
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === FALSE) {
        return ['type' => 'error', 'msg' => 'Error: Could not open the CSV file.'];
    }

    $row_count = 0;
    $table_name = $wpdb->prefix . 'crm_leads';

    // C. Loop Through CSV
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Skip empty rows
        if (empty($data[0])) continue;

        // Map Columns (Assumes Col 1 = Name, Col 2 = Phone)
        $name = sanitize_text_field($data[0]);
        $phone = isset($data[1]) ? sanitize_text_field($data[1]) : '';

        // D. The Equality Math (Round Robin)
        // If we have 3 agents: 0, 1, 2, 0, 1, 2...
        $assigned_agent_id = $agents[$agent_index % $agent_count];

        // E. Insert to DB
        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'phone' => $phone,
                'assigned_agent' => $assigned_agent_id,
                'status' => 'new',
                'time' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );

        $agent_index++; // Move to next agent for the next row
        $row_count++;   // Count total uploads
    }

    fclose($handle);

    return ['type' => 'success', 'msg' => "Success! Uploaded $row_count leads and distributed them equally among $agent_count agents."];
}
?>