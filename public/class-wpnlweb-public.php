<?php

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
					<?php _e('Searching...', 'wpnlweb'); ?>
				</div>
				<?php wp_nonce_field('wpnlweb_search_nonce', 'wpnlweb_nonce'); ?>
			</form>

			<?php if ($atts['show_results'] === 'true'): ?>
				<div id="<?php echo esc_attr($results_id); ?>" class="wpnlweb-search-results" style="display: none;">
					<h3 class="wpnlweb-results-title"><?php _e('Search Results', 'wpnlweb'); ?></h3>
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
					ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
					nonce: '<?php echo wp_create_nonce('wpnlweb_search_nonce'); ?>'
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
			wp_die(__('Security check failed', 'wpnlweb'));
		}

		// Get the question
		$question = sanitize_text_field($_POST['question']);
		$max_results = intval($_POST['max_results']) ?: 10;

		if (empty($question)) {
			wp_send_json_error(array('message' => __('Please enter a question', 'wpnlweb')));
		}

		// Use the existing NLWeb server logic
		if (class_exists('WP_NLWeb_Server')) {

			// Create a proper WP_REST_Request object
			$request = new WP_REST_Request('POST');
			$request->set_param('question', $question);
			$request->set_param('context', array('limit' => $max_results));

			// Get the server instance and handle the request
			$server = new WP_NLWeb_Server();
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
			wp_send_json_error(array('message' => __('Search functionality not available', 'wpnlweb')));
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
				sprintf(__('No results found for "%s"', 'wpnlweb'), esc_html($question)) .
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
