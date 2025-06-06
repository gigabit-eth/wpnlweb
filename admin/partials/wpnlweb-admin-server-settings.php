<?php
/**
 * Admin Settings for Server Integration
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin/partials
 * @since      1.0.3
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$server_url = get_option( 'wpnlweb_api_server_url', 'http://localhost:8000' );
$test_result = get_transient( 'wpnlweb_last_connection_test' );
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields( 'wpnlweb_server_settings' );
        do_settings_sections( 'wpnlweb_server_settings' );
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpnlweb_api_server_url"><?php esc_html_e( 'FastAPI Server URL', 'wpnlweb' ); ?></label>
                </th>
                <td>
                    <input type="url" 
                           name="wpnlweb_api_server_url" 
                           id="wpnlweb_api_server_url" 
                           value="<?php echo esc_attr( $server_url ); ?>" 
                           class="regular-text" 
                           placeholder="http://localhost:8000" />
                    <p class="description">
                        <?php esc_html_e( 'URL of your FastAPI server for premium features.', 'wpnlweb' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2><?php esc_html_e( 'Connection Test', 'wpnlweb' ); ?></h2>
    
    <div id="wpnlweb-connection-test-results">
        <?php if ( $test_result ) : ?>
            <div class="notice notice-<?php echo $test_result['success'] ? 'success' : 'error'; ?>">
                <p>
                    <strong><?php echo $test_result['success'] ? '✅ Connected' : '❌ Connection Failed'; ?></strong><br>
                    <?php echo esc_html( isset( $test_result['message'] ) ? $test_result['message'] : 'No details available' ); ?>
                    <?php if ( isset( $test_result['response_time'] ) ) : ?>
                        <br><small>Response time: <?php echo esc_html( round( $test_result['response_time'], 2 ) ); ?>ms</small>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <button type="button" id="wpnlweb-test-connection" class="button button-secondary">
        <?php esc_html_e( 'Test Connection', 'wpnlweb' ); ?>
    </button>
    
    <button type="button" id="wpnlweb-register-site" class="button button-primary" style="margin-left: 10px;">
        <?php esc_html_e( 'Register Site', 'wpnlweb' ); ?>
    </button>
    
    <button type="button" id="wpnlweb-refresh-token" class="button button-secondary" style="margin-left: 10px;">
        <?php esc_html_e( 'Refresh Token', 'wpnlweb' ); ?>
    </button>
    
    <hr>
    
    <h2><?php esc_html_e( 'Server Status', 'wpnlweb' ); ?></h2>
    
    <div id="wpnlweb-server-status">
        <?php
        if ( class_exists( 'Wpnlweb_Server_Integration' ) ) {
            $integration = new Wpnlweb_Server_Integration();
            $status = $integration->get_server_status();
            ?>
            <table class="widefat">
                <tr>
                    <td><strong>Server Configured:</strong></td>
                    <td><?php echo $status['configured'] ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Has API Key:</strong></td>
                    <td><?php echo $status['authentication']['has_api_key'] ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Valid Token:</strong></td>
                    <td><?php echo $status['authentication']['has_valid_token'] ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Site Registered:</strong></td>
                    <td><?php echo $status['authentication']['is_registered'] ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <?php if ( $status['authentication']['site_id'] ) : ?>
                <tr>
                    <td><strong>Site ID:</strong></td>
                    <td><code><?php echo esc_html( $status['authentication']['site_id'] ); ?></code></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php
        } else {
            echo '<p>Server integration not available.</p>';
        }
        ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wpnlweb-test-connection').click(function() {
        var button = $(this);
        var resultsDiv = $('#wpnlweb-connection-test-results');
        
        button.prop('disabled', true).text('Testing...');
        
        $.post(ajaxurl, {
            action: 'wpnlweb_test_server_connection',
            nonce: '<?php echo wp_create_nonce( 'wpnlweb_admin_nonce' ); ?>'
        }, function(response) {
            if (response.success) {
                var message = response.data && response.data.message ? response.data.message : 'Connection successful';
                var responseTime = response.data && response.data.data && response.data.data.response_time ? 
                                  Math.round(response.data.data.response_time) : 'Unknown';
                resultsDiv.html('<div class="notice notice-success"><p><strong>✅ Connected</strong><br>' + 
                              message + '<br><small>Response time: ' + responseTime + 'ms</small></p></div>');
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : 'Connection failed';
                resultsDiv.html('<div class="notice notice-error"><p><strong>❌ Connection Failed</strong><br>' + 
                              errorMessage + '</p></div>');
            }
        }).fail(function() {
            resultsDiv.html('<div class="notice notice-error"><p><strong>❌ Error</strong><br>AJAX request failed</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('Test Connection');
        });
    });
    
    $('#wpnlweb-register-site').click(function() {
        var button = $(this);
        var resultsDiv = $('#wpnlweb-connection-test-results');
        
        button.prop('disabled', true).text('Registering...');
        
        $.post(ajaxurl, {
            action: 'wpnlweb_register_site',
            nonce: '<?php echo wp_create_nonce( 'wpnlweb_admin_nonce' ); ?>'
        }, function(response) {
            if (response.success) {
                var message = response.data && response.data.message ? response.data.message : 'Site registered successfully';
                resultsDiv.html('<div class="notice notice-success"><p><strong>✅ Site Registered</strong><br>' + 
                              message + '</p></div>');
                location.reload(); // Refresh to show updated status
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : 'Registration failed';
                resultsDiv.html('<div class="notice notice-error"><p><strong>❌ Registration Failed</strong><br>' + 
                              errorMessage + '</p></div>');
            }
        }).fail(function() {
            resultsDiv.html('<div class="notice notice-error"><p><strong>❌ Error</strong><br>AJAX request failed</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('Register Site');
        });
    });
    
    $('#wpnlweb-refresh-token').click(function() {
        var button = $(this);
        var resultsDiv = $('#wpnlweb-connection-test-results');
        
        button.prop('disabled', true).text('Refreshing...');
        
        $.post(ajaxurl, {
            action: 'wpnlweb_refresh_token',
            nonce: '<?php echo wp_create_nonce( 'wpnlweb_admin_nonce' ); ?>'
        }, function(response) {
            if (response.success) {
                var message = response.data && response.data.message ? response.data.message : 'Token refreshed successfully';
                resultsDiv.html('<div class="notice notice-success"><p><strong>✅ Token Refreshed</strong><br>' + 
                              message + '</p></div>');
                location.reload(); // Refresh to show updated status
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : 'Token refresh failed';
                resultsDiv.html('<div class="notice notice-error"><p><strong>❌ Token Refresh Failed</strong><br>' + 
                              errorMessage + '</p></div>');
            }
        }).fail(function() {
            resultsDiv.html('<div class="notice notice-error"><p><strong>❌ Error</strong><br>AJAX request failed</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('Refresh Token');
        });
    });
});
</script> 