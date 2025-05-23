<?php

/**
 * Plugin Name: WordPress NLWeb Server
 * Description: Minimal NLWeb protocol implementation for WordPress
 * Version: 1.0.0
 */

class WP_NLWeb_Server
{

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('rest_api_init', [$this, 'add_cors_support']);
    }

    /**
     * Register the /ask endpoint
     */
    public function register_routes()
    {
        register_rest_route('nlweb/v1', '/ask', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_ask'],
            'permission_callback' => '__return_true', // Adjust as needed
        ]);
    }

    /**
     * Add CORS support for AI agents
     */
    public function add_cors_support()
    {
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', function ($value) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            return $value;
        });
    }

    /**
     * Main NLWeb /ask endpoint handler
     */
    public function handle_ask($request)
    {
        // Add CORS headers for AI agents/browsers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

        $question = $request->get_param('question');
        $context = $request->get_param('context') ?? [];

        if (empty($question)) {
            return new WP_Error('missing_question', 'Question parameter required', ['status' => 400]);
        }

        // Process the natural language query
        $results = $this->process_query($question, $context);

        // Format response according to NLWeb spec
        return $this->format_nlweb_response($results, $question);
    }

    /**
     * Process natural language query into WordPress results
     */
    private function process_query($question, $context = [])
    {
        // Simple keyword extraction (you'd enhance this with LLM/vector search)
        $keywords = $this->extract_keywords($question);

        // Build WordPress query
        $query_args = [
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => implode(' ', $keywords),
        ];

        // Enhance with context if provided
        if (!empty($context['post_type'])) {
            $query_args['post_type'] = $context['post_type'];
        }

        if (!empty($context['category'])) {
            $query_args['category_name'] = $context['category'];
        }

        $query = new WP_Query($query_args);

        return $query->posts;
    }

    /**
     * Format response according to NLWeb/Schema.org standards
     */
    private function format_nlweb_response($posts, $question)
    {
        $items = [];

        foreach ($posts as $post) {
            $items[] = [
                '@type' => 'Article', // Could be dynamic based on post type
                '@id' => get_permalink($post->ID),
                'name' => $post->post_title,
                'description' => wp_trim_words($post->post_content, 30),
                'url' => get_permalink($post->ID),
                'datePublished' => $post->post_date,
                'author' => [
                    '@type' => 'Person',
                    'name' => get_the_author_meta('display_name', $post->post_author)
                ]
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'query' => $question,
            'totalResults' => count($items),
            'items' => $items
        ];
    }

    /**
     * Simple keyword extraction (enhance with NLP)
     */
    private function extract_keywords($question)
    {
        // Remove common words, extract meaningful terms
        $stop_words = ['what', 'where', 'when', 'how', 'is', 'are', 'the', 'a', 'an'];
        $words = explode(' ', strtolower($question));

        return array_filter($words, function ($word) use ($stop_words) {
            return !in_array($word, $stop_words) && strlen($word) > 2;
        });
    }
}

/**
 * Optional: MCP Server compatibility
 */
class WP_NLWeb_MCP_Server
{

    public function __construct()
    {
        add_action('wp_ajax_nopriv_mcp_ask', [$this, 'handle_mcp_ask']);
        add_action('wp_ajax_mcp_ask', [$this, 'handle_mcp_ask']);
    }

    public function handle_mcp_ask()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if ($input['method'] === 'ask') {
            $question = $input['params']['question'];

            // Reuse the NLWeb server logic
            $server = new WP_NLWeb_Server();
            $request = new WP_REST_Request('POST');
            $request->set_param('question', $question);

            $response = $server->handle_ask($request);

            wp_send_json([
                'jsonrpc' => '2.0',
                'id' => $input['id'],
                'result' => $response
            ]);
        }

        wp_die();
    }
}

// Bootstrap
new WP_NLWeb_Server();
new WP_NLWeb_MCP_Server();

/**
 * Optional: Enhanced query processor with vector search
 */
class WP_NLWeb_Enhanced_Query
{

    private $vector_store;

    public function __construct($vector_config = [])
    {
        // Initialize your vector store (Qdrant, etc.)
        // $this->vector_store = new VectorStore($vector_config);
    }

    public function semantic_search($question, $limit = 10)
    {
        // Convert question to embedding
        // Search vector store
        // Return relevant post IDs
        // Fall back to keyword search if vector search unavailable

        return $this->keyword_fallback($question, $limit);
    }

    private function keyword_fallback($question, $limit)
    {
        // Your existing keyword-based search
        $query = new WP_Query([
            's' => $question,
            'posts_per_page' => $limit
        ]);

        return $query->posts;
    }
}
