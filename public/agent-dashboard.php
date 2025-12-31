<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_shortcode('agent_dashboard', 'alm_render_agent_dashboard');

function alm_render_agent_dashboard() {
    
    // 1. SECURITY: Redirect if not logged in
    if (!is_user_logged_in()) {
        return '<div class="alm-error">Access Denied. Please <a href="/portal-login">Log In</a>.</div>';
    }

    global $wpdb;
    $current_user = wp_get_current_user();
    $agent_id = $current_user->ID;
    $table_leads = $wpdb->prefix . 'crm_leads';

    // 2. HANDLE ACTIONS (Update Status)
    // If the agent clicked "Mark as Called" or similar
    if (isset($_POST['alm_update_status']) && isset($_POST['lead_id'])) {
        $lid = intval($_POST['lead_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        
        // Security: Ensure this lead actually belongs to this agent
        $wpdb->update(
            $table_leads,
            ['status' => $new_status],
            ['id' => $lid, 'assigned_agent' => $agent_id]
        );
        echo '<div class="alm-toast">Status Updated!</div>';
    }

    // 3. GET DATA
    // Get leads for this specific agent
    $leads = $wpdb->get_results("SELECT * FROM $table_leads WHERE assigned_agent = $agent_id ORDER BY time DESC");
    
    // simple stats
    $total_leads = count($leads);
    $converted_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE assigned_agent = $agent_id AND status = 'converted'");

    ob_start();
    ?>

    <style>
        /* Main Container */
        .alm-agent-wrapper {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
            border-radius: 8px;
            color: #333;
        }

        /* Header & Stats */
        .alm-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .alm-welcome h2 { margin: 0; color: #2c3e50; font-size: 24px; }
        .alm-welcome span { font-size: 14px; color: #7f8c8d; }

        .alm-stats-row {
            display: flex;
            gap: 20px;
        }
        .alm-stat-card {
            background: #fff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            min-width: 120px;
            text-align: center;
        }
        .alm-stat-card strong { display: block; font-size: 24px; color: #0073aa; }
        .alm-stat-card small { color: #999; font-size: 12px; text-transform: uppercase; }

        /* The Table */
        .alm-table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        table.alm-leads-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        table.alm-leads-table th {
            text-align: left;
            padding: 18px 20px;
            background: #f8f9fa;
            color: #7f8c8d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #eef2f7;
        }
        table.alm-leads-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        table.alm-leads-table tr:hover { background: #fbfbfb; }

        /* Status Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge.new { background: #e3f2fd; color: #2196f3; }
        .badge.called { background: #fff3cd; color: #856404; }
        .badge.converted { background: #d4edda; color: #155724; }

        /* Buttons */
        .btn-action {
            cursor: pointer;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.2s;
            margin-right: 5px;
        }
        .btn-call { background: #6c757d; color: white; }
        .btn-call:hover { background: #5a6268; }
        
        .btn-order { background: #0073aa; color: white; font-weight: bold; }
        .btn-order:hover { background: #005177; box-shadow: 0 2px 5px rgba(0,115,170,0.3); }

        /* Modal Popup */
        .alm-modal {
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); 
            backdrop-filter: blur(2px);
        }
        .alm-modal-content {
            background-color: #fff;
            margin: 10% auto; 
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            position: relative;
        }
        .alm-close {
            position: absolute; right: 20px; top: 15px;
            font-size: 24px; font-weight: bold; color: #aaa; cursor: pointer;
        }
        .alm-modal h3 { margin-top: 0; color: #2c3e50; }
        .alm-input {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border: 2px solid #eef2f7;
            border-radius: 6px;
            font-size: 14px;
        }
        .alm-input:focus { border-color: #0073aa; outline: none; }
        
        /* Platform List */
        .platform-tags { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 10px; }
        .platform-tag { font-size: 10px; background: #eee; padding: 2px 6px; border-radius: 4px; color: #666; }
    </style>

    <div class="alm-agent-wrapper">
        
        <div class="alm-header">
            <div class="alm-welcome">
                <h2>Hello, <?php echo esc_html($current_user->display_name); ?> 👋</h2>
                <span>Here are your leads for today.</span>
            </div>
            <div class="alm-stats-row">
                <div class="alm-stat-card">
                    <strong><?php echo $total_leads; ?></strong>
                    <small>Total Leads</small>
                </div>
                <div class="alm-stat-card">
                    <strong><?php echo $converted_count; ?></strong>
                    <small>Orders</small>
                </div>
            </div>
        </div>

        <div class="alm-table-container">
            <?php if (empty($leads)): ?>
                <div style="padding:40px; text-align:center; color:#999;">
                    No leads assigned yet. Relax! ☕
                </div>
            <?php else: ?>
                <table class="alm-leads-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Phone Number</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($lead->name); ?></strong>
                                </td>
                                <td>
                                    <a href="tel:<?php echo esc_attr($lead->phone); ?>" style="color:#555; text-decoration:none;">
                                        📞 <?php echo esc_html($lead->phone); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge <?php echo esc_attr($lead->status); ?>">
                                        <?php echo esc_html($lead->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" style="display:inline-block;">
                                        <input type="hidden" name="lead_id" value="<?php echo $lead->id; ?>">
                                        <input type="hidden" name="alm_update_status" value="1">
                                        <input type="hidden" name="new_status" value="called">
                                        <button type="submit" class="btn-action btn-call" title="Mark as Called">✓ Call</button>
                                    </form>

                                    <button onclick="openOrderModal(<?php echo $lead->id; ?>, '<?php echo esc_js($lead->name); ?>')" class="btn-action btn-order">
                                        + Order
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

    <div id="almOrderModal" class="alm-modal">
        <div class="alm-modal-content">
            <span class="alm-close" onclick="closeOrderModal()">&times;</span>
            
            <h3>Create Order for <span id="modalCustomerName" style="color:#0073aa;"></span></h3>
            <p style="color:#666; font-size:13px; margin-bottom:10px;">
                Ask the customer which product they want. Search for it on a supported platform, copy the URL, and paste it here.
            </p>
            
            <div class="platform-tags">
                <span class="platform-tag">Amazon</span>
                <span class="platform-tag">Walmart</span>
                <span class="platform-tag">Target</span>
                <span class="platform-tag">eBay</span>
                <span class="platform-tag">AliExpress</span>
            </div>

            <input type="text" id="productUrlInput" class="alm-input" placeholder="Paste Product URL here (e.g. https://amazon.com/dp/...)">
            
            <button onclick="generateLink()" class="btn-action btn-order" style="width:100%; padding:15px; font-size:16px;">
                Generate & Save Order 🚀
            </button>
        </div>
    </div>

    <script>
        let currentLeadId = 0;

        function openOrderModal(id, name) {
            currentLeadId = id;
            document.getElementById('modalCustomerName').innerText = name;
            document.getElementById('almOrderModal').style.display = "block";
        }

        function closeOrderModal() {
            document.getElementById('almOrderModal').style.display = "none";
        }

        function generateLink() {
            const url = document.getElementById('productUrlInput').value;
            if(!url) {
                alert("Please paste a URL first!");
                return;
            }

            // Redirect to the processing logic
            // This sends the data to our PHP plugin which handles the affiliate conversion
            const baseUrl = window.location.href.split('?')[0]; 
            const finalLink = '?alm_action=generate_link&lead_id=' + currentLeadId + '&url=' + encodeURIComponent(url);
            
            // Go to the "Magic" link generator
            window.location.href = finalLink;
        }

        // Close modal if clicking outside box
        window.onclick = function(event) {
            const modal = document.getElementById('almOrderModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

    <?php
    return ob_get_clean();
}
?>