<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://typewriter.sh
 * @since      1.0.0
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and admin functionality including
 * modern tabbed settings page for theme customization and custom CSS.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/admin
 * @author     TypeWriter <team@typewriter.sh>
 */
class Wpnlweb_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add settings page
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'init_settings'));

		// Add admin scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

		// AJAX handlers for live preview
		add_action('wp_ajax_wpnlweb_preview_shortcode', array($this, 'handle_preview_shortcode'));

		// Add settings link to plugins page
		add_filter('plugin_action_links_wpnlweb/wpnlweb.php', array($this, 'add_settings_link'));
	}

	/**
	 * Add admin menu page
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu()
	{
		add_options_page(
			__('WPNLWeb Settings', 'wpnlweb'),
			__('WPNLWeb', 'wpnlweb'),
			'manage_options',
			'wpnlweb-settings',
			array($this, 'settings_page')
		);
	}

	/**
	 * Initialize settings
	 *
	 * @since    1.0.0
	 */
	public function init_settings()
	{
		// Register settings
		register_setting('wpnlweb_settings', 'wpnlweb_custom_css');
		register_setting('wpnlweb_settings', 'wpnlweb_theme_mode');
		register_setting('wpnlweb_settings', 'wpnlweb_primary_color');

		// Add a hook to clear caches when settings are saved
		add_action('update_option_wpnlweb_theme_mode', array($this, 'clear_style_caches'));
		add_action('update_option_wpnlweb_primary_color', array($this, 'clear_style_caches'));
		add_action('update_option_wpnlweb_custom_css', array($this, 'clear_style_caches'));
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since    1.0.0
	 */
	public function enqueue_admin_assets($hook)
	{
		// Only load on our settings page
		if ($hook !== 'settings_page_wpnlweb-settings') {
			return;
		}

		// Enqueue admin CSS
		$css_file_path = plugin_dir_path(__FILE__) . 'css/wpnlweb-admin.css';
		$css_file_url = plugin_dir_url(__FILE__) . 'css/wpnlweb-admin.css';

		if (file_exists($css_file_path)) {
			wp_enqueue_style(
				$this->plugin_name . '-admin',
				$css_file_url,
				array(),
				$this->version,
				'all'
			);
		} else {
			// Fallback: Enqueue minimal inline styles if CSS file is missing
			add_action('admin_head', array($this, 'enqueue_fallback_styles'));
		}

		// Enqueue admin JS
		$js_file_path = plugin_dir_path(__FILE__) . 'js/wpnlweb-admin.js';
		$js_file_url = plugin_dir_url(__FILE__) . 'js/wpnlweb-admin.js';

		if (file_exists($js_file_path)) {
			wp_enqueue_script(
				$this->plugin_name . '-admin',
				$js_file_url,
				array('jquery', 'wp-color-picker'),
				$this->version,
				true
			);

			// Enqueue WordPress color picker
			wp_enqueue_style('wp-color-picker');

			// Localize script for AJAX
			wp_localize_script($this->plugin_name . '-admin', 'wpnlweb_admin', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wpnlweb_admin_nonce')
			));
		} else {
			// Fallback: Enqueue minimal inline JS if JS file is missing
			add_action('admin_footer', array($this, 'enqueue_fallback_scripts'));
		}
	}

	/**
	 * Enqueue fallback inline styles when CSS file is missing
	 *
	 * @since    1.0.0
	 */
	public function enqueue_fallback_styles()
	{
		echo '<style type="text/css">
			.wpnlweb-admin-wrapper { 
				background: #fff; 
				border: 1px solid #ccd0d4; 
				border-radius: 4px; 
				margin: 20px 0; 
				padding: 20px; 
			}
			.wpnlweb-admin-header h1 { 
				margin: 0 0 10px 0; 
				font-size: 24px; 
			}
			.wpnlweb-admin-container { 
				display: flex; 
				gap: 20px; 
			}
			.wpnlweb-admin-sidebar { 
				width: 200px; 
				background: #f9f9f9; 
				padding: 15px; 
				border-radius: 4px; 
			}
			.wpnlweb-nav-item { 
				display: block; 
				padding: 8px 12px; 
				margin: 2px 0; 
				text-decoration: none; 
				border-radius: 3px; 
			}
			.wpnlweb-nav-item.active { 
				background: #0073aa; 
				color: #fff; 
			}
			.wpnlweb-admin-main { 
				flex: 1; 
			}
			.wpnlweb-tab-content { 
				display: none; 
			}
			.wpnlweb-tab-content.active { 
				display: block; 
			}
			.wpnlweb-setting-group { 
				margin: 20px 0; 
				padding: 15px; 
				background: #f9f9f9; 
				border-radius: 4px; 
			}
			.wpnlweb-color-picker, .wpnlweb-color-text { 
				margin: 5px; 
			}
			.wpnlweb-preset-color { 
				display: inline-block; 
				width: 30px; 
				height: 30px; 
				margin: 3px; 
				border-radius: 3px; 
				cursor: pointer; 
			}
		</style>';
	}

	/**
	 * Enqueue fallback inline scripts when JS file is missing
	 *
	 * @since    1.0.0
	 */
	public function enqueue_fallback_scripts()
	{
		echo '<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Basic tab functionality
				$(".wpnlweb-nav-item").on("click", function(e) {
					e.preventDefault();
					var tabId = $(this).data("tab");
					$(".wpnlweb-nav-item").removeClass("active");
					$(this).addClass("active");
					$(".wpnlweb-tab-content").removeClass("active");
					$("#" + tabId + "-tab").addClass("active");
				});
				
				// Basic color picker sync
				$("#wpnlweb_primary_color").on("change", function() {
					$("#wpnlweb_primary_color_text").val($(this).val());
				});
				
				$("#wpnlweb_primary_color_text").on("change", function() {
					$("#wpnlweb_primary_color").val($(this).val());
				});
			});
		</script>';
	}

	/**
	 * Settings page HTML with modern tabbed interface
	 *
	 * @since    1.0.0
	 */
	public function settings_page()
	{
		// Get current settings
		$theme_mode = get_option('wpnlweb_theme_mode', 'auto');
		$primary_color = get_option('wpnlweb_primary_color', '#3b82f6');
		$custom_css = get_option('wpnlweb_custom_css', '');

		// Preset colors for color picker
		$preset_colors = array(
			'#3b82f6', // Blue (default)
			'#ef4444', // Red
			'#10b981', // Green  
			'#f59e0b', // Orange
			'#8b5cf6', // Purple
			'#06b6d4', // Teal
			'#f97316', // Orange variant
			'#84cc16'  // Lime
		);

		// Diagnostic information for troubleshooting
		$css_file_path = plugin_dir_path(__FILE__) . 'css/wpnlweb-admin.css';
		$css_file_url = plugin_dir_url(__FILE__) . 'css/wpnlweb-admin.css';
		$js_file_path = plugin_dir_path(__FILE__) . 'js/wpnlweb-admin.js';
		$js_file_url = plugin_dir_url(__FILE__) . 'js/wpnlweb-admin.js';

		// Check if files exist
		$css_exists = file_exists($css_file_path);
		$js_exists = file_exists($js_file_path);

		// Show diagnostic info if styles aren't loading properly
		$show_diagnostic = !$css_exists || isset($_GET['debug']);
?>

		<div class="wrap">
			<!-- Standard WordPress admin page title -->
			<h1>
				<span class="wpnlweb-admin-icon">⚙️</span>
				<?php echo esc_html(get_admin_page_title()); ?>
				<?php if (!$css_exists): ?><span style="color: #dc3545; font-size: 16px; font-weight: normal;"> - Missing Styles</span><?php endif; ?>
			</h1>

			<!-- WordPress admin notices will appear here automatically -->

			<?php if ($show_diagnostic): ?>
				<!-- Diagnostic Information - WordPress standard notice -->
				<div class="notice notice-warning">
					<h3 style="margin-top: 0;">🔍 WPNLWeb Diagnostic Information</h3>
					<p><strong>CSS File Path:</strong> <code><?php echo esc_html($css_file_path); ?></code></p>
					<p><strong>CSS File URL:</strong> <code><?php echo esc_html($css_file_url); ?></code></p>
					<p><strong>CSS File Exists:</strong> <?php echo $css_exists ? '✅ Yes' : '❌ No'; ?></p>

					<p><strong>JS File Path:</strong> <code><?php echo esc_html($js_file_path); ?></code></p>
					<p><strong>JS File URL:</strong> <code><?php echo esc_html($js_file_url); ?></code></p>
					<p><strong>JS File Exists:</strong> <?php echo $js_exists ? '✅ Yes' : '❌ No'; ?></p>

					<p><strong>Plugin Dir Path:</strong> <code><?php echo esc_html(plugin_dir_path(__FILE__)); ?></code></p>
					<p><strong>Plugin Dir URL:</strong> <code><?php echo esc_html(plugin_dir_url(__FILE__)); ?></code></p>

					<p><strong>Current Hook:</strong> <code><?php echo esc_html(current_filter()); ?></code></p>
					<p><strong>WordPress Version:</strong> <code><?php echo esc_html(get_bloginfo('version')); ?></code></p>

					<?php if (!$css_exists): ?>
						<div class="notice notice-error inline">
							<p><strong>⚠️ CSS File Missing:</strong> The admin CSS file is not found at the expected location.
								Please check your file upload and ensure all plugin files were transferred correctly.</p>
						</div>
					<?php endif; ?>

					<?php if (!$js_exists): ?>
						<div class="notice notice-error inline">
							<p><strong>⚠️ JS File Missing:</strong> The admin JavaScript file is not found at the expected location.
								Please check your file upload and ensure all plugin files were transferred correctly.</p>
						</div>
					<?php endif; ?>

					<p><em>To hide this diagnostic info once files are working, remove <code>?debug</code> from the URL.</em></p>
				</div>
			<?php endif; ?>

			<!-- Custom Admin Interface starts here -->
			<div class="wpnlweb-admin-wrapper" <?php if (!$css_exists): ?> style="border: 2px solid #dc3545; background: #fff5f5;" <?php endif; ?>>
				<div class="wpnlweb-admin-header" <?php if (!$css_exists): ?> style="background: #fee; padding: 20px; border-bottom: 1px solid #fcc;" <?php endif; ?>>
					<p class="wpnlweb-admin-subtitle" <?php if (!$css_exists): ?> style="color: #666; margin: 5px 0 0 0;" <?php endif; ?>>
						<?php _e('Customize the appearance of your WPNLWeb search forms and interface.', 'wpnlweb'); ?>
					</p>
				</div>

				<div class="wpnlweb-admin-container">
					<!-- Sidebar Navigation -->
					<div class="wpnlweb-admin-sidebar">
						<div class="wpnlweb-sidebar-header">
							<h2><?php _e('Settings', 'wpnlweb'); ?></h2>
							<p><?php _e('Configure your preferences', 'wpnlweb'); ?></p>
						</div>

						<nav class="wpnlweb-sidebar-nav">
							<a href="#theme" class="wpnlweb-nav-item active" data-tab="theme">
								<span class="wpnlweb-nav-icon">🎨</span>
								<?php _e('Theme', 'wpnlweb'); ?>
							</a>
							<a href="#custom-css" class="wpnlweb-nav-item" data-tab="custom-css">
								<span class="wpnlweb-nav-icon">📝</span>
								<?php _e('Custom CSS', 'wpnlweb'); ?>
							</a>
							<a href="#live-preview" class="wpnlweb-nav-item" data-tab="live-preview">
								<span class="wpnlweb-nav-icon">👁️</span>
								<?php _e('Live Preview', 'wpnlweb'); ?>
							</a>
						</nav>
					</div>

					<!-- Main Content Area -->
					<div class="wpnlweb-admin-main">
						<form action="options.php" method="post" id="wpnlweb-settings-form">
							<?php settings_fields('wpnlweb_settings'); ?>

							<!-- Theme Tab -->
							<div id="theme-tab" class="wpnlweb-tab-content active">
								<div class="wpnlweb-tab-header">
									<h2>
										<span class="wpnlweb-tab-icon">🎨</span>
										<?php _e('Theme Customization', 'wpnlweb'); ?>
									</h2>
									<p><?php _e('Customize the appearance of your WPNLWeb search forms.', 'wpnlweb'); ?></p>
								</div>

								<div class="wpnlweb-setting-group">
									<label class="wpnlweb-setting-label">
										<?php _e('Theme Mode', 'wpnlweb'); ?>
									</label>
									<p class="wpnlweb-setting-description">
										<?php _e('Choose the theme mode for the search interface.', 'wpnlweb'); ?>
									</p>
									<select name="wpnlweb_theme_mode" id="wpnlweb_theme_mode" class="wpnlweb-select">
										<option value="auto" <?php selected($theme_mode, 'auto'); ?>>
											☀️ <?php _e('Auto (Follow System)', 'wpnlweb'); ?>
										</option>
										<option value="light" <?php selected($theme_mode, 'light'); ?>>
											☀️ <?php _e('Light Mode', 'wpnlweb'); ?>
										</option>
										<option value="dark" <?php selected($theme_mode, 'dark'); ?>>
											🌙 <?php _e('Dark Mode', 'wpnlweb'); ?>
										</option>
									</select>
								</div>

								<div class="wpnlweb-setting-group">
									<label class="wpnlweb-setting-label">
										<?php _e('Primary Color', 'wpnlweb'); ?>
									</label>
									<p class="wpnlweb-setting-description">
										<?php _e('Choose the primary color for buttons and focus states.', 'wpnlweb'); ?>
									</p>

									<div class="wpnlweb-color-picker-wrapper">
										<div class="wpnlweb-color-input-group">
											<input type="color"
												name="wpnlweb_primary_color"
												id="wpnlweb_primary_color"
												value="<?php echo esc_attr($primary_color); ?>"
												class="wpnlweb-color-picker" />
											<input type="text"
												id="wpnlweb_primary_color_text"
												value="<?php echo esc_attr($primary_color); ?>"
												placeholder="#3b82f6"
												class="wpnlweb-color-text" />
										</div>
									</div>

									<div class="wpnlweb-preset-colors">
										<label class="wpnlweb-preset-label"><?php _e('Preset Colors', 'wpnlweb'); ?></label>
										<div class="wpnlweb-preset-grid">
											<?php foreach ($preset_colors as $color): ?>
												<button type="button"
													class="wpnlweb-preset-color <?php echo ($color === $primary_color) ? 'active' : ''; ?>"
													data-color="<?php echo esc_attr($color); ?>"
													style="background-color: <?php echo esc_attr($color); ?>"
													title="<?php echo esc_attr($color); ?>">
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>

							<!-- Custom CSS Tab -->
							<div id="custom-css-tab" class="wpnlweb-tab-content">
								<div class="wpnlweb-tab-header">
									<h2>
										<span class="wpnlweb-tab-icon">📝</span>
										<?php _e('Custom CSS', 'wpnlweb'); ?>
									</h2>
									<p><?php _e('Add custom CSS to further customize the appearance. This CSS will be applied to all WPNLWeb shortcodes.', 'wpnlweb'); ?></p>
								</div>

								<div class="wpnlweb-setting-group">
									<div class="wpnlweb-css-editor-header">
										<label class="wpnlweb-setting-label">
											<?php _e('Custom CSS', 'wpnlweb'); ?>
										</label>
										<div class="wpnlweb-css-actions">
											<button type="button" class="wpnlweb-button-secondary" id="wpnlweb-copy-example">
												📋 <?php _e('Copy Example', 'wpnlweb'); ?>
											</button>
											<button type="button" class="wpnlweb-button-secondary" id="wpnlweb-reset-css">
												🔄 <?php _e('Reset', 'wpnlweb'); ?>
											</button>
										</div>
									</div>

									<textarea name="wpnlweb_custom_css"
										id="wpnlweb_custom_css"
										rows="12"
										class="wpnlweb-css-editor"
										placeholder="<?php esc_attr_e('Add your custom CSS here...', 'wpnlweb'); ?>"><?php echo esc_textarea($custom_css); ?></textarea>

									<div class="wpnlweb-css-example">
										<p><?php _e('Add custom CSS to override default styles. Example:', 'wpnlweb'); ?></p>
										<pre class="wpnlweb-code-block"><code>.wpnlweb-search-container { border-radius: 20px; }
.wpnlweb-search-button { background: var(--wpnlweb-primary-color); }</code></pre>
									</div>

									<div class="wpnlweb-css-reference">
										<h3><?php _e('CSS Custom Properties Reference', 'wpnlweb'); ?></h3>
										<p><?php _e('You can use these CSS custom properties in your custom CSS to maintain consistency:', 'wpnlweb'); ?></p>

										<div class="wpnlweb-css-properties">
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-primary-color</code>
												<span class="wpnlweb-css-desc"><?php _e('Main brand color', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-primary-hover</code>
												<span class="wpnlweb-css-desc"><?php _e('Hover state color', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-bg-primary</code>
												<span class="wpnlweb-css-desc"><?php _e('Main background color', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-bg-secondary</code>
												<span class="wpnlweb-css-desc"><?php _e('Secondary background', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-text-primary</code>
												<span class="wpnlweb-css-desc"><?php _e('Main text color', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-text-secondary</code>
												<span class="wpnlweb-css-desc"><?php _e('Secondary text color', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-border-radius</code>
												<span class="wpnlweb-css-desc"><?php _e('Border radius', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-spacing-sm</code>
												<span class="wpnlweb-css-desc"><?php _e('Small spacing (12px)', 'wpnlweb'); ?></span>
											</div>
											<div class="wpnlweb-css-property">
												<code class="wpnlweb-css-var">--wpnlweb-spacing-md</code>
												<span class="wpnlweb-css-desc"><?php _e('Medium spacing (20px)', 'wpnlweb'); ?></span>
												<code class="wpnlweb-css-var">--wpnlweb-spacing-lg</code>
												<span class="wpnlweb-css-desc"><?php _e('Large spacing (30px)', 'wpnlweb'); ?></span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Live Preview Tab -->
							<div id="live-preview-tab" class="wpnlweb-tab-content">
								<div class="wpnlweb-tab-header">
									<h2>
										<span class="wpnlweb-tab-icon">👁️</span>
										<?php _e('Live Preview', 'wpnlweb'); ?>
									</h2>
									<p><?php _e('Test your WPNLWeb search interface with live functionality. This preview uses the actual API endpoint.', 'wpnlweb'); ?></p>
								</div>

								<div class="wpnlweb-setting-group">
									<label class="wpnlweb-setting-label">
										<?php _e('Shortcode Usage', 'wpnlweb'); ?>
									</label>

									<div class="wpnlweb-shortcode-display">
										<span class="wpnlweb-shortcode-label"><?php _e('Shortcode:', 'wpnlweb'); ?></span>
										<code class="wpnlweb-shortcode-code">[wpnlweb]</code>
									</div>

									<div class="wpnlweb-preview-section">
										<h3><?php _e('Live Functional Preview', 'wpnlweb'); ?></h3>
										<p class="wpnlweb-preview-description">
											<?php _e('This is a fully functional preview that connects to your site\'s content via the NLWeb API endpoint. Try searching for content on your site!', 'wpnlweb'); ?>
										</p>

										<div class="wpnlweb-preview-container">
											<div class="wpnlweb-preview-header">
												<?php _e('Live Preview', 'wpnlweb'); ?>
												<button type="button" id="wpnlweb-refresh-preview" class="wpnlweb-button-secondary wpnlweb-refresh-btn">
													🔄 <?php _e('Refresh Preview', 'wpnlweb'); ?>
												</button>
											</div>
											<div id="wpnlweb-live-preview" class="wpnlweb-live-preview">
												<div class="wpnlweb-preview-loading">
													<span class="wpnlweb-spinner"></span>
													<?php _e('Loading preview...', 'wpnlweb'); ?>
												</div>
											</div>
										</div>

										<div class="wpnlweb-preview-info">
											<h4><?php _e('Preview Information', 'wpnlweb'); ?></h4>
											<ul>
												<li><?php _e('This preview uses your current theme and color settings', 'wpnlweb'); ?></li>
												<li><?php _e('Search results come from your actual site content', 'wpnlweb'); ?></li>
												<li><?php _e('Changes to settings above will be reflected when you refresh the preview', 'wpnlweb'); ?></li>
											</ul>
										</div>
									</div>
								</div>
							</div>

							<!-- Save Button -->
							<div class="wpnlweb-form-actions">
								<button type="submit" class="wpnlweb-button-primary">
									💾 <?php _e('Save Settings', 'wpnlweb'); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Handle AJAX preview shortcode request
	 *
	 * @since    1.0.0
	 */
	public function handle_preview_shortcode()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'wpnlweb_admin_nonce')) {
			wp_die(__('Security check failed', 'wpnlweb'));
		}

		// Get settings from request (for real-time preview updates)
		$theme_mode = sanitize_text_field($_POST['theme_mode'] ?? get_option('wpnlweb_theme_mode', 'auto'));
		$primary_color = sanitize_text_field($_POST['primary_color'] ?? get_option('wpnlweb_primary_color', '#3b82f6'));
		$custom_css = wp_strip_all_tags($_POST['custom_css'] ?? get_option('wpnlweb_custom_css', ''));

		// Temporarily set options for preview
		$original_theme = get_option('wpnlweb_theme_mode');
		$original_color = get_option('wpnlweb_primary_color');
		$original_css = get_option('wpnlweb_custom_css');

		update_option('wpnlweb_theme_mode', $theme_mode);
		update_option('wpnlweb_primary_color', $primary_color);
		update_option('wpnlweb_custom_css', $custom_css);

		// Generate a working preview that's admin-friendly
		$preview_html = $this->generate_admin_preview($theme_mode, $primary_color, $custom_css);

		// Restore original options
		update_option('wpnlweb_theme_mode', $original_theme);
		update_option('wpnlweb_primary_color', $original_color);
		update_option('wpnlweb_custom_css', $original_css);

		// Return the preview HTML with inline styles and scripts
		wp_send_json_success(array(
			'html' => $preview_html,
			'settings' => array(
				'theme_mode' => $theme_mode,
				'primary_color' => $primary_color,
				'has_custom_css' => !empty($custom_css)
			)
		));
	}

	/**
	 * Generate admin-friendly preview HTML
	 *
	 * @since    1.0.0
	 * @param    string $theme_mode Theme mode setting
	 * @param    string $primary_color Primary color setting  
	 * @param    string $custom_css Custom CSS setting
	 * @return   string Preview HTML
	 */
	private function generate_admin_preview($theme_mode, $primary_color, $custom_css)
	{
		// Generate unique IDs for this preview instance
		$form_id = 'wpnlweb-preview-' . uniqid();
		$results_id = 'wpnlweb-results-' . uniqid();
		$nonce = wp_create_nonce('wpnlweb_search_nonce');

		// Calculate hover color
		$primary_hover = $this->adjust_color_brightness($primary_color, -20);

		// Start output buffering
		ob_start();
	?>
		<style>
			/* Inline styles for admin preview */
			#<?php echo $form_id; ?> {
				--wpnlweb-primary-color: <?php echo esc_attr($primary_color); ?>;
				--wpnlweb-primary-hover: <?php echo esc_attr($primary_hover); ?>;
			}

			.wpnlweb-preview-search-container {
				max-width: 600px;
				margin: 0 auto;
				padding: 20px;
				background: #ffffff;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
			}

			.wpnlweb-preview-search-form {
				margin: 0;
			}

			.wpnlweb-preview-input-wrapper {
				display: flex;
				gap: 12px;
				margin-bottom: 16px;
			}

			.wpnlweb-preview-search-input {
				flex: 1;
				padding: 12px 16px;
				border: 2px solid #e5e7eb;
				border-radius: 8px;
				font-size: 14px;
				color: #1f2937;
				background: #ffffff;
				transition: all 0.2s ease;
			}

			.wpnlweb-preview-search-input:focus {
				outline: none;
				border-color: var(--wpnlweb-primary-color);
				box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
			}

			.wpnlweb-preview-search-input::placeholder {
				color: #9ca3af;
			}

			.wpnlweb-preview-search-button {
				padding: 12px 20px;
				background: var(--wpnlweb-primary-color);
				color: #ffffff;
				border: none;
				border-radius: 8px;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
				transition: all 0.2s ease;
				white-space: nowrap;
			}

			.wpnlweb-preview-search-button:hover {
				background: var(--wpnlweb-primary-hover);
				transform: translateY(-1px);
				box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
			}

			.wpnlweb-preview-search-button:active {
				transform: translateY(0);
			}

			.wpnlweb-preview-loading {
				display: none;
				text-align: center;
				padding: 16px;
				color: #6b7280;
				font-style: italic;
			}

			.wpnlweb-preview-loading.show {
				display: block;
			}

			.wpnlweb-preview-spinner {
				display: inline-block;
				width: 16px;
				height: 16px;
				border: 2px solid #f3f4f6;
				border-top: 2px solid var(--wpnlweb-primary-color);
				border-radius: 50%;
				animation: wpnlweb-preview-spin 1s linear infinite;
				margin-right: 8px;
			}

			@keyframes wpnlweb-preview-spin {
				0% {
					transform: rotate(0deg);
				}

				100% {
					transform: rotate(360deg);
				}
			}

			.wpnlweb-preview-results {
				margin-top: 20px;
				border: 1px solid #e5e7eb;
				border-radius: 8px;
				background: #f9fafb;
				display: none;
			}

			.wpnlweb-preview-results.show {
				display: block;
			}

			.wpnlweb-preview-results-title {
				padding: 12px 16px;
				margin: 0;
				background: #ffffff;
				border-bottom: 1px solid #e5e7eb;
				font-size: 14px;
				font-weight: 600;
				color: #374151;
			}

			.wpnlweb-preview-results-content {
				padding: 16px;
				max-height: 300px;
				overflow-y: auto;
			}

			.wpnlweb-preview-result-item {
				background: #ffffff;
				border: 1px solid #e5e7eb;
				border-radius: 6px;
				padding: 16px;
				margin-bottom: 12px;
				transition: all 0.2s ease;
			}

			.wpnlweb-preview-result-item:last-child {
				margin-bottom: 0;
			}

			.wpnlweb-preview-result-item:hover {
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
				border-color: #d1d5db;
			}

			.wpnlweb-preview-result-title {
				margin: 0 0 8px 0;
				font-size: 16px;
				font-weight: 600;
			}

			.wpnlweb-preview-result-title a {
				color: #1f2937;
				text-decoration: none;
			}

			.wpnlweb-preview-result-title a:hover {
				color: var(--wpnlweb-primary-color);
			}

			.wpnlweb-preview-result-excerpt {
				margin: 0 0 8px 0;
				color: #4b5563;
				font-size: 14px;
				line-height: 1.5;
			}

			.wpnlweb-preview-result-meta {
				font-size: 12px;
				color: #9ca3af;
				border-top: 1px solid #f3f4f6;
				padding-top: 8px;
			}

			.wpnlweb-preview-no-results {
				text-align: center;
				padding: 40px 20px;
				color: #6b7280;
				font-style: italic;
			}

			.wpnlweb-preview-error {
				background: #fef2f2;
				color: #dc2626;
				border: 1px solid #fecaca;
				border-radius: 6px;
				padding: 12px 16px;
				margin: 16px 0;
				font-size: 14px;
			}

			/* Apply custom CSS if provided */
			<?php if (!empty($custom_css)): ?><?php echo wp_strip_all_tags($custom_css); ?><?php endif; ?>
		</style>

		<div class="wpnlweb-preview-search-container">
			<form id="<?php echo esc_attr($form_id); ?>" class="wpnlweb-preview-search-form">
				<div class="wpnlweb-preview-input-wrapper">
					<input
						type="text"
						name="wpnlweb_question"
						class="wpnlweb-preview-search-input"
						placeholder="Try searching for content on your site..."
						required />
					<button type="submit" class="wpnlweb-preview-search-button">
						🔍 Search
					</button>
				</div>
				<div id="loading-<?php echo esc_attr($form_id); ?>" class="wpnlweb-preview-loading">
					<span class="wpnlweb-preview-spinner"></span>
					Searching your content...
				</div>
				<input type="hidden" name="wpnlweb_nonce" value="<?php echo esc_attr($nonce); ?>" />
			</form>

			<div id="<?php echo esc_attr($results_id); ?>" class="wpnlweb-preview-results">
				<h3 class="wpnlweb-preview-results-title">Search Results</h3>
				<div class="wpnlweb-preview-results-content"></div>
			</div>
		</div>

		<script type="text/javascript">
			(function($) {
				'use strict';

				// Initialize search functionality for this preview
				$('#<?php echo esc_js($form_id); ?>').on('submit', function(e) {
					e.preventDefault();

					const $form = $(this);
					const $input = $form.find('.wpnlweb-preview-search-input');
					const $button = $form.find('.wpnlweb-preview-search-button');
					const $loading = $('#loading-<?php echo esc_js($form_id); ?>');
					const $results = $('#<?php echo esc_js($results_id); ?>');
					const $resultsContent = $results.find('.wpnlweb-preview-results-content');

					const question = $input.val().trim();
					if (!question) {
						alert('Please enter a search question');
						return;
					}

					// Show loading state
					$loading.addClass('show');
					$results.removeClass('show');
					$button.prop('disabled', true);

					// Make AJAX request to the actual search endpoint
					$.post('<?php echo admin_url('admin-ajax.php'); ?>', {
							action: 'wpnlweb_search',
							question: question,
							max_results: 5,
							wpnlweb_nonce: $form.find('[name="wpnlweb_nonce"]').val()
						})
						.done(function(response) {
							$loading.removeClass('show');
							$button.prop('disabled', false);

							if (response.success && response.data) {
								const results = response.data;
								let html = '';

								if (results.length > 0) {
									results.forEach(function(result) {
										html += '<div class="wpnlweb-preview-result-item">';
										html += '<h4 class="wpnlweb-preview-result-title">';
										html += '<a href="' + result.url + '" target="_blank">' + result.title + '</a>';
										html += '</h4>';
										html += '<p class="wpnlweb-preview-result-excerpt">' + result.excerpt + '</p>';
										html += '<div class="wpnlweb-preview-result-meta">';
										html += 'Published: ' + result.date + ' | Author: ' + result.author;
										html += '</div>';
										html += '</div>';
									});
								} else {
									html = '<div class="wpnlweb-preview-no-results">No results found for your search. Try different keywords.</div>';
								}

								$resultsContent.html(html);
								$results.addClass('show');
							} else {
								const errorMsg = response.data && response.data.message ? response.data.message : 'Search failed. Please try again.';
								$resultsContent.html('<div class="wpnlweb-preview-error">' + errorMsg + '</div>');
								$results.addClass('show');
							}
						})
						.fail(function(xhr, status, error) {
							console.error('Search AJAX Error:', status, error);
							$loading.removeClass('show');
							$button.prop('disabled', false);
							$resultsContent.html('<div class="wpnlweb-preview-error">Connection error. Please check your network and try again.</div>');
							$results.addClass('show');
						});
				});

			})(jQuery);
		</script>
<?php

		return ob_get_clean();
	}

	/**
	 * Adjust color brightness for hover effects
	 *
	 * @since    1.0.0
	 * @param    string $hex_color Hex color code
	 * @param    int    $percent   Percentage to adjust (-100 to 100)
	 * @return   string Adjusted hex color
	 */
	private function adjust_color_brightness($hex_color, $percent)
	{
		// Remove # if present
		$hex_color = ltrim($hex_color, '#');

		// Convert hex to decimal
		$r = hexdec(substr($hex_color, 0, 2));
		$g = hexdec(substr($hex_color, 2, 2));
		$b = hexdec(substr($hex_color, 4, 2));

		// Adjust brightness
		$r = max(0, min(255, $r + ($r * $percent / 100)));
		$g = max(0, min(255, $g + ($g * $percent / 100)));
		$b = max(0, min(255, $b + ($b * $percent / 100)));

		// Convert back to hex
		return sprintf('#%02x%02x%02x', $r, $g, $b);
	}

	/**
	 * Clear style caches when settings are updated
	 *
	 * @since    1.0.0
	 */
	public function clear_style_caches()
	{
		// Clear any object cache
		if (function_exists('wp_cache_flush')) {
			wp_cache_flush();
		}

		// If using a caching plugin, you might want to add hooks here
		do_action('wpnlweb_settings_updated');
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpnlweb-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpnlweb-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Add settings link to plugins page
	 *
	 * @since    1.0.0
	 * @param    array    $links    Array of existing plugin action links
	 * @return   array    Modified array of plugin action links
	 */
	public function add_settings_link($links)
	{
		$settings_link = '<a href="' . admin_url('admin.php?page=wpnlweb-settings') . '">' . __('Settings', 'wpnlweb') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
}
