<?php
/**
 * Plugin Name: BxB Layout Dashboard
 * Plugin URI: https://github.com/CVLTIK/BxB-Layout-Dashboard
 * Description: A WordPress dashboard plugin for setting up headers, footers, colors, and global settings.
 * Version: 1.0.1
 * Author: CVTIK / BXBMedia
 * Author URI: 
 * License: MPL-2.0
 * License URI: https://opensource.org/licenses/MPL-2.0
 * Text Domain: bxb-dashboard
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('BXB_dashboard_VERSION', '1.0.1');
define('BXB_dashboard_DIR', plugin_dir_path(__FILE__));
define('BXB_dashboard_URL', plugin_dir_url(__FILE__));

$files_to_include = array(
    // Global files
    'includes/global-functions.php',
    'includes/global-variables.php',
    
    // Modules
    'modules/Documentation/documentation.php',
    'modules/Script Manager/snippets-dashboard.php',
    'modules/Script Manager/snippet-settings.php',
    'modules/Script Manager/snippet-ajax.php'
);

// Initialize modules
foreach ($files_to_include as $file) {
    if (file_exists(BXB_dashboard_DIR . $file)) {
        require_once BXB_dashboard_DIR . $file;
    }
}

// Initialize Server Setup module and its submodules
add_action('plugins_loaded', function() {
    if (class_exists('BxB_Server_Setup')) {
        global $bxb_server_setup;
        $bxb_server_setup = new BxB_Server_Setup();
    }
    
    if (class_exists('BxB_Server_Setup_Docs')) {
        global $bxb_server_setup_docs;
        $bxb_server_setup_docs = new BxB_Server_Setup_Docs();
    }
    
    if (class_exists('BxB_Server_Setup_Toggle')) {
        global $bxb_server_setup_toggle;
        $bxb_server_setup_toggle = new BxB_Server_Setup_Toggle();
    }
});
  
/* Plugin activation hook. */
function bxb_dashboard_activate() {
    // Actions on activation
}
register_activation_hook(__FILE__, 'bxb_dashboard_activate');

/** Plugin deactivation hook. */
function bxb_dashboard_deactivate() {
    // Actions on deactivation
}
register_deactivation_hook(__FILE__, 'bxb_dashboard_deactivate');

function bxb_dashboard_add_admin_menu() {
    add_menu_page(
        'BxB Dashboard',
        'BxB Dashboard',
        'manage_options',
        'bxb-dashboard',
        'bxb_dashboard_page',
        'dashicons-admin-generic',
        2
    );

    add_submenu_page(
        'bxb-dashboard',
        'Snippets',
        'Snippets',
        'manage_options',
        'bxb-snippets-dashboard',
        'bxb_snippets_dashboard_page'
    );
}
add_action('admin_menu', 'bxb_dashboard_add_admin_menu');

// Main Dashboard Page
function bxb_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h1>BxB Dashboard</h1>
        <div class="card" style="max-width: 100%; padding: 20px;">
            <h2>Welcome to BxB Dashboard</h2>
            <p>This dashboard provides tools for managing your WordPress site's layout and functionality.</p>
            <h3>Available Features:</h3>
            <ul>
                <li><strong>Snippets:</strong> Manage and execute code snippets with advanced security controls.</li>
                <li><strong>Documentation:</strong> Access comprehensive documentation for all features.</li>
            </ul>
        </div>
    </div>
    <?php
}

// Snippets Dashboard Page
function bxb_snippets_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Get all snippets from the database
    $snippets = get_option('bxb_snippets', array());
    
    // Get all unique tags
    $all_tags = array();
    foreach ($snippets as $snippet) {
        if (!empty($snippet['tags'])) {
            $all_tags = array_merge($all_tags, $snippet['tags']);
        }
    }
    $all_tags = array_unique($all_tags);
    sort($all_tags);
    ?>
    <div class="wrap">
        <h1>Snippets Dashboard</h1>
        
        <!-- Tag Filter -->
        <div class="snippet-filters" style="margin-bottom: 20px;">
            <select id="tag-filter" style="min-width: 200px; padding: 5px;">
                <option value="">All Tags</option>
                <?php foreach ($all_tags as $tag): ?>
                    <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($tag); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="snippets-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <!-- Add New Snippet Card -->
            <div class="snippet-card add-new" style="background: #fff; border: 2px dashed #ddd; border-radius: 4px; padding: 20px; text-align: center; cursor: pointer;">
                <h3>Add New Snippet</h3>
                <p>Click to create a new code snippet</p>
                <span class="dashicons dashicons-plus-alt" style="font-size: 40px; color: #ddd;"></span>
            </div>

            <?php foreach ($snippets as $slug => $snippet): ?>
                <div class="snippet-card" data-tags="<?php echo esc_attr(implode(' ', $snippet['tags'] ?? [])); ?>" style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 20px;">
                    <h3><?php echo esc_html($snippet['name']); ?></h3>
                    <p><?php echo esc_html($snippet['description']); ?></p>
                    
                    <?php if (!empty($snippet['tags'])): ?>
                        <div class="snippet-tags" style="margin: 10px 0;">
                            <?php foreach ($snippet['tags'] as $tag): ?>
                                <span class="tag" style="background: #f0f0f0; padding: 2px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                                    <?php echo esc_html($tag); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="snippet-actions" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                        <a href="<?php echo admin_url('admin.php?page=bxb-snippet-settings&snippet=' . $slug); ?>" 
                           class="button button-secondary">
                            Edit
                        </a>
                        
                        <div class="snippet-toggle">
                            <label class="switch">
                                <input type="checkbox" 
                                       class="snippet-toggle-input" 
                                       data-snippet="<?php echo esc_attr($slug); ?>"
                                       <?php checked($snippet['enabled']); ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add New Snippet Modal -->
        <div id="add-snippet-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div style="background: #fff; width: 500px; margin: 50px auto; padding: 20px; border-radius: 4px;">
                <h2>Add New Snippet</h2>
                <form id="add-snippet-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Name</th>
                            <td>
                                <input type="text" name="snippet_name" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Description</th>
                            <td>
                                <textarea name="snippet_description" rows="3" class="large-text" required></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Tags</th>
                            <td>
                                <input type="text" name="snippet_tags" class="regular-text" placeholder="Comma-separated tags">
                                <p class="description">Separate tags with commas</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Add Snippet">
                        <button type="button" class="button" id="cancel-add-snippet">Cancel</button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <style>
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }
    
    input:checked + .slider {
        background-color: #2196F3;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .slider.round {
        border-radius: 34px;
    }
    
    .slider.round:before {
        border-radius: 50%;
    }

    .snippet-card.add-new:hover {
        border-color: #0073aa;
        background: #f8f9fa;
    }

    .snippet-card {
        transition: all 0.3s ease;
    }
    .snippet-card.hidden {
        display: none;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Tag filtering
        $('#tag-filter').on('change', function() {
            var selectedTag = $(this).val();
            $('.snippet-card').each(function() {
                if (selectedTag === '' || $(this).data('tags').split(' ').includes(selectedTag)) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        });

        // Toggle functionality
        $('.snippet-toggle-input').on('change', function() {
            var snippet = $(this).data('snippet');
            var enabled = $(this).is(':checked');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'toggle_snippet',
                    snippet: snippet,
                    enabled: enabled,
                    nonce: '<?php echo wp_create_nonce('toggle_snippet'); ?>'
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Error toggling snippet');
                    }
                }
            });
        });

        // Add New Snippet functionality
        $('.snippet-card.add-new').on('click', function() {
            $('#add-snippet-modal').show();
        });

        $('#cancel-add-snippet').on('click', function() {
            $('#add-snippet-modal').hide();
        });

        $('#add-snippet-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'add_snippet',
                name: $('input[name="snippet_name"]').val(),
                description: $('textarea[name="snippet_description"]').val(),
                tags: $('input[name="snippet_tags"]').val().split(',').map(tag => tag.trim()).filter(tag => tag),
                nonce: '<?php echo wp_create_nonce('add_snippet'); ?>'
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error adding snippet: ' + response.data);
                    }
                }
            });
        });
    });
    </script>
    <?php
}

// Add AJAX handlers
add_action('wp_ajax_toggle_snippet', 'bxb_toggle_snippet');
add_action('wp_ajax_add_snippet', 'bxb_add_snippet');

function bxb_toggle_snippet() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'toggle_snippet')) {
        wp_send_json_error('Invalid nonce');
    }

    $snippet_slug = sanitize_text_field($_POST['snippet']);
    $enabled = (bool) $_POST['enabled'];

    $snippets = get_option('bxb_snippets', array());
    if (isset($snippets[$snippet_slug])) {
        $snippets[$snippet_slug]['enabled'] = $enabled;
        update_option('bxb_snippets', $snippets);
        wp_send_json_success();
    } else {
        wp_send_json_error('Snippet not found');
    }
}

function bxb_add_snippet() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_snippet')) {
        wp_send_json_error('Invalid nonce');
    }

    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $tags = array_map('trim', explode(',', sanitize_text_field($_POST['tags'])));
    $slug = sanitize_title($name);

    $snippets = get_option('bxb_snippets', array());
    $snippets[$slug] = array(
        'name' => $name,
        'description' => $description,
        'tags' => $tags,
        'code' => '',
        'documentation' => '',
        'enabled' => false,
        'security' => array(
            'scope' => 'everywhere',
            'run_once' => false,
            'min_role' => 'manage_options'
        )
    );

    update_option('bxb_snippets', $snippets);
    wp_send_json_success();
}