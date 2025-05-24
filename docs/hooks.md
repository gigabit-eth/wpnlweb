# 🔗 WPNLWeb Hooks Reference

Complete reference for all WordPress filters and actions provided by the WPNLWeb plugin.

## 🎯 Overview

WPNLWeb provides numerous hooks to customize functionality, modify search results, customize styling, and extend the plugin's capabilities. All hooks follow WordPress coding standards and conventions.

## 🔧 Filters

### Search & Content Filters

#### `wpnlweb_search_results`

Modify search results before they are returned to the user.

**Usage:**

```php
add_filter('wpnlweb_search_results', 'customize_search_results', 10, 2);

function customize_search_results($results, $query) {
    // Add custom logic to modify results
    foreach ($results as &$result) {
        $result['custom_field'] = 'custom_value';
    }
    return $results;
}
```

**Parameters:**

- `$results` (array) - Array of search result posts
- `$query` (string) - Original search query

**Return:** Modified array of results

---

#### `wpnlweb_api_response`

Customize the final API response before it's sent to the client.

**Usage:**

```php
add_filter('wpnlweb_api_response', 'customize_api_response', 10, 2);

function customize_api_response($response, $question) {
    // Add custom metadata
    $response['custom_metadata'] = array(
        'site_name' => get_bloginfo('name'),
        'timestamp' => current_time('c'),
        'version' => WPNLWEB_VERSION
    );
    return $response;
}
```

**Parameters:**

- `$response` (array) - Schema.org formatted response
- `$question` (string) - Original question asked

**Return:** Modified response array

---

#### `wpnlweb_searchable_post_types`

Add or remove post types from the search.

**Usage:**

```php
add_filter('wpnlweb_searchable_post_types', 'add_custom_post_types');

function add_custom_post_types($post_types) {
    $post_types[] = 'product';
    $post_types[] = 'event';
    $post_types[] = 'testimonial';
    return $post_types;
}
```

**Parameters:**

- `$post_types` (array) - Current searchable post types

**Return:** Modified array of post types

**Default Post Types:** `['post', 'page']`

---

#### `wpnlweb_search_query_args`

Modify WordPress query arguments before search execution.

**Usage:**

```php
add_filter('wpnlweb_search_query_args', 'customize_query_args', 10, 2);

function customize_query_args($args, $question) {
    // Add meta query for featured content
    $args['meta_query'] = array(
        array(
            'key' => 'featured',
            'value' => 'yes',
            'compare' => '='
        )
    );

    // Boost certain post types
    if (strpos($question, 'product') !== false) {
        $args['post_type'] = array('product');
    }

    return $args;
}
```

**Parameters:**

- `$args` (array) - WP_Query arguments
- `$question` (string) - Search question

**Return:** Modified query arguments

---

#### `wpnlweb_extract_keywords`

Customize keyword extraction from natural language questions.

**Usage:**

```php
add_filter('wpnlweb_extract_keywords', 'custom_keyword_extraction', 10, 2);

function custom_keyword_extraction($keywords, $question) {
    // Add domain-specific keyword processing
    $domain_keywords = array(
        'ecommerce' => array('buy', 'purchase', 'order', 'cart'),
        'blog' => array('article', 'post', 'read', 'latest'),
        'service' => array('consultation', 'hire', 'contact')
    );

    foreach ($domain_keywords as $domain => $terms) {
        foreach ($terms as $term) {
            if (strpos(strtolower($question), $term) !== false) {
                $keywords[] = $domain;
                break;
            }
        }
    }

    return array_unique($keywords);
}
```

**Parameters:**

- `$keywords` (array) - Extracted keywords
- `$question` (string) - Original question

**Return:** Modified keywords array

### Styling & UI Filters

#### `wpnlweb_custom_css`

Add custom CSS to the search interface.

**Usage:**

```php
add_filter('wpnlweb_custom_css', 'add_custom_search_styles');

function add_custom_search_styles($css) {
    $custom_css = '
        .wpnlweb-search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .wpnlweb-search-input {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
        }
    ';

    return $css . $custom_css;
}
```

**Parameters:**

- `$css` (string) - Existing CSS

**Return:** Modified CSS string

---

#### `wpnlweb_primary_color`

Customize the primary color used throughout the interface.

**Usage:**

```php
add_filter('wpnlweb_primary_color', function($color) {
    return '#e74c3c'; // Custom red color
});
```

**Parameters:**

- `$color` (string) - Current primary color (hex)

**Return:** New color (hex format)

**Default:** `#3b82f6`

---

#### `wpnlweb_secondary_color`

Customize the secondary color.

**Usage:**

```php
add_filter('wpnlweb_secondary_color', function($color) {
    return '#2c3e50';
});
```

**Parameters:**

- `$color` (string) - Current secondary color

**Return:** New secondary color

**Default:** `#1f2937`

---

#### `wpnlweb_background_color`

Customize the background color.

**Usage:**

```php
add_filter('wpnlweb_background_color', function($color) {
    return '#f8f9fa';
});
```

**Default:** `#ffffff`

---

#### `wpnlweb_text_color`

Customize the text color.

**Usage:**

```php
add_filter('wpnlweb_text_color', function($color) {
    return '#2c3e50';
});
```

**Default:** `#1f2937`

---

#### `wpnlweb_border_radius`

Customize border radius for UI elements.

**Usage:**

```php
add_filter('wpnlweb_border_radius', function($radius) {
    return '12px';
});
```

**Default:** `8px`

### Security & Performance Filters

#### `wpnlweb_rate_limit`

Set rate limiting for API requests.

**Usage:**

```php
add_filter('wpnlweb_rate_limit', function($limit) {
    return 30; // 30 requests per hour per IP
});
```

**Parameters:**

- `$limit` (int) - Requests per hour

**Return:** New rate limit

**Default:** No rate limiting

---

#### `wpnlweb_allowed_origins`

Restrict CORS origins for enhanced security.

**Usage:**

```php
add_filter('wpnlweb_allowed_origins', function($origins) {
    return array(
        'https://youragent.com',
        'https://trusted-ai-service.com',
        'https://yourapp.com'
    );
});
```

**Parameters:**

- `$origins` (array) - Allowed origin URLs

**Return:** Modified origins array

**Default:** `['*']` (all origins allowed)

---

#### `wpnlweb_cache_timeout`

Set cache timeout for search results.

**Usage:**

```php
add_filter('wpnlweb_cache_timeout', function($timeout) {
    return 600; // 10 minutes
});
```

**Parameters:**

- `$timeout` (int) - Cache timeout in seconds

**Return:** New timeout value

**Default:** No caching implemented

---

#### `wpnlweb_require_auth`

Enable authentication requirement for API access.

**Usage:**

```php
add_filter('wpnlweb_require_auth', '__return_true');

// Then handle authentication
add_filter('wpnlweb_authenticate_request', function($authenticated, $request) {
    $api_key = $request->get_header('X-API-Key');
    return $api_key === 'your-secret-key';
}, 10, 2);
```

**Default:** `false`

### Shortcode Filters

#### `wpnlweb_shortcode_defaults`

Customize default shortcode attributes.

**Usage:**

```php
add_filter('wpnlweb_shortcode_defaults', function($defaults) {
    $defaults['placeholder'] = 'Ask me anything about our services...';
    $defaults['button_text'] = 'Find Answer';
    $defaults['max_results'] = '8';
    return $defaults;
});
```

**Parameters:**

- `$defaults` (array) - Default attribute values

**Return:** Modified defaults

---

#### `wpnlweb_result_template`

Customize the HTML template for search results.

**Usage:**

```php
add_filter('wpnlweb_result_template', function($template, $result) {
    return '
        <div class="custom-result">
            <h3><a href="' . esc_url($result['url']) . '">' . esc_html($result['name']) . '</a></h3>
            <p class="excerpt">' . esc_html($result['description']) . '</p>
            <div class="meta">
                <span class="date">' . esc_html($result['datePublished']) . '</span>
                <span class="author">by ' . esc_html($result['author']['name']) . '</span>
            </div>
        </div>
    ';
}, 10, 2);
```

**Parameters:**

- `$template` (string) - Current template HTML
- `$result` (array) - Individual search result data

**Return:** Modified template HTML

## 🎬 Actions

### `wpnlweb_settings_updated`

Triggered when plugin settings are saved in the admin.

**Usage:**

```php
add_action('wpnlweb_settings_updated', 'handle_settings_update');

function handle_settings_update() {
    // Clear caches
    wp_cache_flush();

    // Update external services
    update_search_index();

    // Log the update
    error_log('WPNLWeb settings updated at ' . current_time('mysql'));
}
```

---

### `wpnlweb_search_performed`

Triggered after each search is performed.

**Usage:**

```php
add_action('wpnlweb_search_performed', 'track_search_analytics', 10, 3);

function track_search_analytics($question, $results_count, $processing_time) {
    // Track search analytics
    $analytics_data = array(
        'question' => $question,
        'results_count' => $results_count,
        'processing_time' => $processing_time,
        'timestamp' => current_time('mysql'),
        'user_ip' => $_SERVER['REMOTE_ADDR']
    );

    // Send to analytics service or save to database
    save_search_analytics($analytics_data);
}
```

**Parameters:**

- `$question` (string) - Search question
- `$results_count` (int) - Number of results found
- `$processing_time` (float) - Time taken to process (seconds)

---

### `wpnlweb_api_request_start`

Triggered at the beginning of each API request.

**Usage:**

```php
add_action('wpnlweb_api_request_start', 'log_api_request');

function log_api_request($request) {
    error_log(sprintf(
        'WPNLWeb API request: %s from %s',
        $request->get_param('question'),
        $_SERVER['REMOTE_ADDR']
    ));
}
```

**Parameters:**

- `$request` (WP_REST_Request) - WordPress REST request object

---

### `wpnlweb_api_request_end`

Triggered at the end of each API request.

**Usage:**

```php
add_action('wpnlweb_api_request_end', 'log_api_response', 10, 2);

function log_api_response($response, $request) {
    $processing_time = microtime(true) - $GLOBALS['wpnlweb_request_start'];

    error_log(sprintf(
        'WPNLWeb API response: %d results in %f seconds',
        $response['totalResults'],
        $processing_time
    ));
}
```

**Parameters:**

- `$response` (array) - API response data
- `$request` (WP_REST_Request) - Original request

## 💡 Usage Examples

### Complete Customization Example

```php
// Add to your theme's functions.php or custom plugin

class WPNLWeb_Customizations {

    public function __construct() {
        // Search customization
        add_filter('wpnlweb_searchable_post_types', array($this, 'add_post_types'));
        add_filter('wpnlweb_search_results', array($this, 'enhance_results'), 10, 2);

        // Styling
        add_filter('wpnlweb_primary_color', array($this, 'brand_color'));
        add_filter('wpnlweb_custom_css', array($this, 'custom_styles'));

        // Analytics
        add_action('wpnlweb_search_performed', array($this, 'track_searches'), 10, 3);

        // Security
        add_filter('wpnlweb_allowed_origins', array($this, 'restrict_origins'));
    }

    public function add_post_types($post_types) {
        return array_merge($post_types, array('product', 'service', 'testimonial'));
    }

    public function enhance_results($results, $query) {
        foreach ($results as &$result) {
            // Add featured image
            if (has_post_thumbnail($result->ID)) {
                $result->featured_image = get_the_post_thumbnail_url($result->ID, 'medium');
            }

            // Add custom fields
            $result->custom_data = array(
                'views' => get_post_meta($result->ID, 'views', true),
                'rating' => get_post_meta($result->ID, 'rating', true),
            );
        }
        return $results;
    }

    public function brand_color($color) {
        return '#e74c3c'; // Your brand color
    }

    public function custom_styles($css) {
        return $css . '
            .wpnlweb-search-container {
                font-family: "Helvetica Neue", sans-serif;
                max-width: 600px;
                margin: 0 auto;
            }
        ';
    }

    public function track_searches($question, $count, $time) {
        // Send to your analytics platform
        wp_remote_post('https://analytics.yoursite.com/track', array(
            'body' => json_encode(array(
                'event' => 'wpnlweb_search',
                'question' => $question,
                'results' => $count,
                'time' => $time
            ))
        ));
    }

    public function restrict_origins($origins) {
        return array(
            'https://yoursite.com',
            'https://youragent.com'
        );
    }
}

new WPNLWeb_Customizations();
```

### E-commerce Integration Example

```php
// Enhance for WooCommerce integration
add_filter('wpnlweb_searchable_post_types', function($post_types) {
    $post_types[] = 'product';
    return $post_types;
});

add_filter('wpnlweb_search_results', function($results, $query) {
    foreach ($results as &$result) {
        if ($result->post_type === 'product') {
            $product = wc_get_product($result->ID);
            if ($product) {
                $result->price = $product->get_price_html();
                $result->in_stock = $product->is_in_stock();
                $result->product_url = $product->get_permalink();
            }
        }
    }
    return $results;
}, 10, 2);
```

## 🔗 Related Documentation

- [API Reference](api.md) - Complete API documentation
- [Customization Guide](customization.md) - Theming and styling
- [WordPress Plugin API](https://developer.wordpress.org/plugins/hooks/) - Official WordPress hooks documentation
