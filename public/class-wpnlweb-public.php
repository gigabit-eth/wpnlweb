<?php

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
	die;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://typewriter.sh
 * @since      1.0.0
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and implements the frontend search shortcode
 * for natural language querying of WordPress content.
 *
 * @package    Wpnlweb
 * @subpackage Wpnlweb/public
 * @author     TypeWriter <team@typewriter.sh>
 */
class Wpnlweb_Public
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
	 * Track if shortcode assets have been enqueued
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool    $shortcode_assets_enqueued
	 */
	private $shortcode_assets_enqueued = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Register shortcode
		add_action('init', array($this, 'register_shortcode'));

		// Register AJAX handlers
		add_action('wp_ajax_wpnlweb_search', array($this, 'handle_ajax_search'));
		add_action('wp_ajax_nopriv_wpnlweb_search', array($this, 'handle_ajax_search'));
	}

	/**
	 * Register the [wpnlweb] shortcode
	 *
	 * @since    1.0.0
	 */
	public function register_shortcode()
	{
		add_shortcode('wpnlweb', array($this, 'render_search_shortcode'));
	}

	/**
	 * Render the search shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string   HTML output for the search form
	 */
	public function render_search_shortcode($atts)
	{

		// Parse shortcode attributes
		$atts = shortcode_atts(array(
			'placeholder' => __('Ask a question about this site...', 'wpnlweb'),
			'button_text' => __('Search', 'wpnlweb'),
			'show_results' => 'true',
			'max_results' => '10',
			'class' => 'wpnlweb-search-form'
		), $atts, 'wpnlweb');

		// Enqueue assets only when shortcode is used
		$this->enqueue_shortcode_assets();

		// Generate unique form ID
		$form_id = 'wpnlweb-form-' . uniqid();
		$results_id = 'wpnlweb-results-' . uniqid();

		// Build the search form HTML
		ob_start();
?>
		<div class="wpnlweb-search-container <?php echo esc_attr($atts['class']); ?>">
			<form id="<?php echo esc_attr($form_id); ?>" class="wpnlweb-search-form" method="post">
				<div class="wpnlweb-search-input-wrapper">
					<input
						type="text"
						name="wpnlweb_question"
						class="wpnlweb-search-input"
						placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
						required />
					<button type="submit" class="wpnlweb-search-button">
						<?php echo esc_html($atts['button_text']); ?>
					</button>
				</div>
				<div class="wpnlweb-loading" style="display: none;">
					<span class="wpnlweb-spinner"></span>
					<?php esc_html_e('Searching...', 'wpnlweb'); ?>
				</div>
				<?php wp_nonce_field('wpnlweb_search_nonce', 'wpnlweb_nonce'); ?>
			</form>

			<?php if ($atts['show_results'] === 'true'): ?>
				<div id="<?php echo esc_attr($results_id); ?>" class="wpnlweb-search-results" style="display: none;">
					<h3 class="wpnlweb-results-title"><?php esc_html_e('Search Results', 'wpnlweb'); ?></h3>
					<div class="wpnlweb-results-content"></div>
				</div>
			<?php endif; ?>

			<script type="text/javascript">
				// Pass data to JavaScript
				window.wpnlweb_data = window.wpnlweb_data || {};
				window.wpnlweb_data['<?php echo esc_js($form_id); ?>'] = {
					form_id: '<?php echo esc_js($form_id); ?>',
					results_id: '<?php echo esc_js($results_id); ?>',
					max_results: <?php echo intval($atts['max_results']); ?>,
					show_results: <?php echo $atts['show_results'] === 'true' ? 'true' : 'false'; ?>,
					ajax_url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
					nonce: '<?php echo esc_js(wp_create_nonce('wpnlweb_search_nonce')); ?>'
				};
			</script>
		</div>
<?php
		return ob_get_clean();
	}

	/**
	 * Handle AJAX search requests from frontend
	 *
	 * @since    1.0.0
	 */
	public function handle_ajax_search()
	{

		// Verify nonce
		if (! wp_verify_nonce($_POST['wpnlweb_nonce'], 'wpnlweb_search_nonce')) {
			wp_die(esc_html__('Security check failed', 'wpnlweb'));
		}

		// Get the question
		$question = sanitize_text_field($_POST['question']);
		$max_results = intval($_POST['max_results']) ?: 10;

		if (empty($question)) {
			wp_send_json_error(array('message' => esc_html__('Please enter a question', 'wpnlweb')));
		}

		// Use the existing NLWeb server logic
		if (class_exists('Wpnlweb_Server')) {

			// Create a proper WP_REST_Request object
			$request = new WP_REST_Request('POST');
			$request->set_param('question', $question);
			$request->set_param('context', array('limit' => $max_results));

			// Get the server instance and handle the request
			$server = new Wpnlweb_Server('wpnlweb', WPNLWEB_VERSION);
			$response = $server->handle_ask($request);

			// Check if response is WP_Error
			if (is_wp_error($response)) {
				wp_send_json_error(array('message' => $response->get_error_message()));
			}

			// Extract posts from the Schema.org response for frontend formatting
			$posts = array();
			if (isset($response['items']) && is_array($response['items'])) {
				// Convert Schema.org items back to post-like objects for consistent formatting
				foreach ($response['items'] as $item) {
					if (isset($item['@id'])) {
						$post_id = url_to_postid($item['@id']);
						if ($post_id) {
							$post = get_post($post_id);
							if ($post) {
								$posts[] = $post;
							}
						}
					}
				}
			}

			// Format results for frontend display
			$html_results = $this->format_search_results($posts, $question);

			wp_send_json_success(array(
				'html' => $html_results,
				'count' => count($posts)
			));
		} else {
			wp_send_json_error(array('message' => esc_html__('Search functionality not available', 'wpnlweb')));
		}
	}

	/**
	 * Format search results for frontend display
	 *
	 * @since    1.0.0
	 * @param    array     $posts     Array of post objects
	 * @param    string    $question  Original search question
	 * @return   string    HTML formatted results
	 */
	private function format_search_results($posts, $question)
	{

		if (empty($posts)) {
			return '<p class="wpnlweb-no-results">' .
				/* translators: %s is the search query entered by the user */
				sprintf(esc_html__('No results found for "%s"', 'wpnlweb'), esc_html($question)) .
				'</p>';
		}

		$html = '<div class="wpnlweb-results-list">';

		foreach ($posts as $post) {
			$html .= '<article class="wpnlweb-result-item">';
			$html .= '<h4 class="wpnlweb-result-title">';
			$html .= '<a href="' . esc_url(get_permalink($post->ID)) . '">';
			$html .= esc_html($post->post_title);
			$html .= '</a>';
			$html .= '</h4>';

			$html .= '<div class="wpnlweb-result-excerpt">';
			$html .= '<p>' . esc_html(wp_trim_words($post->post_content, 30)) . '</p>';
			$html .= '</div>';

			$html .= '<div class="wpnlweb-result-meta">';
			$html .= '<span class="wpnlweb-result-date">' .
				get_the_date('', $post->ID) .
				'</span>';
			$html .= '<span class="wpnlweb-result-author"> by ' .
				get_the_author_meta('display_name', $post->post_author) .
				'</span>';
			$html .= '</div>';

			$html .= '</article>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Enqueue shortcode-specific assets
	 *
	 * @since    1.0.0
	 */
	private function enqueue_shortcode_assets()
	{

		if ($this->shortcode_assets_enqueued) {
			return;
		}

		// Enqueue shortcode-specific CSS
		wp_enqueue_style(
			$this->plugin_name . '-shortcode',
			plugin_dir_url(__FILE__) . 'css/wpnlweb-shortcode.css',
			array(),
			$this->version,
			'all'
		);

		// Add custom CSS if available
		$this->add_custom_styles();

		// Enqueue shortcode-specific JavaScript
		wp_enqueue_script(
			$this->plugin_name . '-shortcode',
			plugin_dir_url(__FILE__) . 'js/wpnlweb-shortcode.js',
			array('jquery'),
			$this->version,
			true
		);

		$this->shortcode_assets_enqueued = true;
	}

	/**
	 * Add custom CSS styles from options and filters
	 *
	 * @since    1.0.0
	 */
	private function add_custom_styles()
	{
		// Get custom CSS from WordPress options (if admin settings exist)
		$custom_css = get_option('wpnlweb_custom_css', '');

		// Allow themes and other plugins to modify custom CSS
		$custom_css = apply_filters('wpnlweb_custom_css', $custom_css);

		// If custom CSS exists, add it inline
		if (!empty($custom_css)) {
			wp_add_inline_style($this->plugin_name . '-shortcode', wp_strip_all_tags($custom_css));
		}

		// Add theme-specific CSS variables for easy customization
		$theme_vars = $this->get_theme_css_variables();
		if (!empty($theme_vars)) {
			wp_add_inline_style($this->plugin_name . '-shortcode', $theme_vars);
		}
	}

	/**
	 * Generate CSS variables for theme integration
	 *
	 * @since    1.0.0
	 * @return   string    CSS variables for theme integration
	 */
	private function get_theme_css_variables()
	{
		// Get settings from admin options
		$primary_color = get_option('wpnlweb_primary_color', '#3b82f6');
		$theme_mode = get_option('wpnlweb_theme_mode', 'auto');

		// Allow themes to override these values via filters
		$primary_color = apply_filters('wpnlweb_primary_color', $primary_color);
		$secondary_color = apply_filters('wpnlweb_secondary_color', '#1f2937');
		$background_color = apply_filters('wpnlweb_background_color', '#ffffff');
		$text_color = apply_filters('wpnlweb_text_color', '#1f2937');
		$border_radius = apply_filters('wpnlweb_border_radius', '8px');

		// Generate hover and active colors based on primary color
		$primary_hover = $this->adjust_color_brightness($primary_color, -20);
		$primary_active = $this->adjust_color_brightness($primary_color, -40);

		$css = ":root {
			--wpnlweb-primary-color: {$primary_color};
			--wpnlweb-primary-hover: {$primary_hover};
			--wpnlweb-primary-active: {$primary_active};
			--wpnlweb-secondary-color: {$secondary_color};
			--wpnlweb-background-color: {$background_color};
			--wpnlweb-text-color: {$text_color};
			--wpnlweb-border-radius: {$border_radius};
		}";

		// Add forced theme mode if not auto
		if ($theme_mode === 'light') {
			// Force light mode by overriding dark mode styles with higher specificity
			$css .= "
			/* Force Light Mode Override */
			.wpnlweb-search-container.wpnlweb-search-container {
				background: #ffffff !important;
				color: #1f2937 !important;
				border-color: #e5e7eb !important;
				box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
			}
			.wpnlweb-search-container .wpnlweb-search-input.wpnlweb-search-input {
				background: #ffffff !important;
				border-color: #e5e7eb !important;
				color: #1f2937 !important;
			}
			.wpnlweb-search-container .wpnlweb-search-input:focus {
				border-color: {$primary_color} !important;
				box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
				background: #ffffff !important;
			}
			.wpnlweb-search-container .wpnlweb-search-input::placeholder {
				color: #9ca3af !important;
			}
			.wpnlweb-search-container .wpnlweb-search-button {
				background: linear-gradient(135deg, {$primary_color}, {$primary_hover}) !important;
				box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25) !important;
			}
			.wpnlweb-search-container .wpnlweb-search-button:hover {
				background: linear-gradient(135deg, {$primary_hover}, {$primary_active}) !important;
				box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35) !important;
			}
			.wpnlweb-search-container .wpnlweb-loading {
				color: #6b7280 !important;
			}
			.wpnlweb-search-container .wpnlweb-spinner {
				border-color: #f3f4f6 !important;
				border-top-color: {$primary_color} !important;
			}
			.wpnlweb-search-container .wpnlweb-search-results {
				border-top-color: #f3f4f6 !important;
			}
			.wpnlweb-search-container .wpnlweb-results-title {
				color: #1f2937 !important;
			}
			.wpnlweb-search-container .wpnlweb-result-item {
				background: #ffffff !important;
				border-color: #e5e7eb !important;
			}
			.wpnlweb-search-container .wpnlweb-result-item:hover {
				box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
				border-color: #d1d5db !important;
			}
			.wpnlweb-search-container .wpnlweb-result-title a {
				color: #1f2937 !important;
			}
			.wpnlweb-search-container .wpnlweb-result-title a:hover {
				color: {$primary_color} !important;
			}
			.wpnlweb-search-container .wpnlweb-result-excerpt {
				color: #4b5563 !important;
			}
			.wpnlweb-search-container .wpnlweb-result-meta {
				color: #9ca3af !important;
				border-top-color: #f3f4f6 !important;
			}
			.wpnlweb-search-container .wpnlweb-no-results {
				background: #f9fafb !important;
				color: #6b7280 !important;
				border-color: #e5e7eb !important;
			}
			.wpnlweb-search-container .wpnlweb-error {
				background: #fef2f2 !important;
				color: #dc2626 !important;
				border-color: #fecaca !important;
			}";
		} elseif ($theme_mode === 'dark') {
			// Force dark mode styles
			$css .= "
			/* Force Dark Mode Override */
			.wpnlweb-search-container.wpnlweb-search-container {
				background: #1f2937 !important;
				color: #f3f4f6 !important;
				border-color: #374151 !important;
				box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
			}
			.wpnlweb-search-container .wpnlweb-search-input.wpnlweb-search-input {
				background: #374151 !important;
				border-color: #4b5563 !important;
				color: #f3f4f6 !important;
			}
			.wpnlweb-search-container .wpnlweb-search-input:focus {
				border-color: #60a5fa !important;
				box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2) !important;
				background: #374151 !important;
			}
			.wpnlweb-search-container .wpnlweb-search-input::placeholder {
				color: #9ca3af !important;
			}
			.wpnlweb-search-container .wpnlweb-search-button {
				background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
				box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4) !important;
			}
			.wpnlweb-search-container .wpnlweb-search-button:hover {
				background: linear-gradient(135deg, #1d4ed8, #1e40af) !important;
				box-shadow: 0 4px 12px rgba(37, 99, 235, 0.5) !important;
			}
			.wpnlweb-search-container .wpnlweb-loading {
				color: #d1d5db !important;
			}
			.wpnlweb-search-container .wpnlweb-spinner {
				border-color: #4b5563 !important;
				border-top-color: #60a5fa !important;
			}
			.wpnlweb-search-container .wpnlweb-search-results {
				border-top-color: #374151 !important;
			}
			.wpnlweb-search-container .wpnlweb-results-title {
				color: #f3f4f6 !important;
			}
			.wpnlweb-search-container .wpnlweb-result-item {
				background: #374151 !important;
				border-color: #4b5563 !important;
			}
			.wpnlweb-search-container .wpnlweb-result-item:hover {
				box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
				border-color: #6b7280 !important;
			}
			.wpnlweb-search-container .wpnlweb-result-title a {
				color: #f3f4f6 !important;
			}
			.wpnlweb-search-container .wpnlweb-result-title a:hover {
				color: #60a5fa !important;
			}
			.wpnlweb-search-container .wpnlweb-result-excerpt {
				color: #d1d5db !important;
			}
			.wpnlweb-search-container .wpnlweb-result-meta {
				color: #9ca3af !important;
				border-top-color: #4b5563 !important;
			}
			.wpnlweb-search-container .wpnlweb-no-results {
				background: #374151 !important;
				color: #d1d5db !important;
				border-color: #4b5563 !important;
			}
			.wpnlweb-search-container .wpnlweb-error {
				background: #7f1d1d !important;
				color: #fca5a5 !important;
				border-color: #991b1b !important;
			}";
		}

		return $css;
	}

	/**
	 * Adjust color brightness for hover/active states
	 *
	 * @since    1.0.0
	 * @param    string    $hex_color    Hex color code
	 * @param    int       $percent      Percentage to adjust (-100 to 100)
	 * @return   string    Adjusted hex color
	 */
	private function adjust_color_brightness($hex_color, $percent)
	{
		// Remove # if present
		$hex_color = ltrim($hex_color, '#');

		// Convert to RGB
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
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpnlweb_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpnlweb_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpnlweb-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpnlweb_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpnlweb_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpnlweb-public.js', array('jquery'), $this->version, false);
	}
}
