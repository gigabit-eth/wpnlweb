=== WPNLWeb ===
Contributors: wpnlweb
Donate link: https://wpnlweb.com/donate
Tags: ai, nlweb, artificial intelligence, natural language, nlp
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

AI-power your WordPress site. Natural language search meets the agentic web.

== Description ==

**WPNLWeb** turns your WordPress website into a conversational interface for both users and AI agents. It implements Microsoft's NLWeb protocol, making your site's content accessible via natural language queries through both REST API endpoints and an easy-to-use frontend shortcode.

= Key Features =

* **NLWeb Protocol Implementation** - Standards-compliant REST API endpoint `/wp-json/nlweb/v1/ask`
* **Frontend Search Shortcode** - Add `[wpnlweb]` to any page for visitor search functionality
* **Schema.org Compliant Responses** - Structured data that AI agents understand
* **MCP Server Compatibility** - Works with Model Context Protocol systems
* **WordPress Integration** - Native support for all post types, taxonomies, and custom fields
* **AI Agent Ready** - CORS headers and proper formatting for ChatGPT, Claude, and other AI systems
* **Admin Dashboard** - Settings, analytics, and test interface
* **Performance Optimized** - <500ms response times with caching support

= How It Works =

1. **For AI Agents**: Your site becomes queryable via natural language through the REST API endpoint
2. **For Website Visitors**: Add the `[wpnlweb]` shortcode to any page for an interactive search experience
3. **For Developers**: Extend and customize the search functionality with WordPress hooks and filters

= Use Cases =

* **Customer Support**: Let visitors ask questions and get instant answers from your knowledge base
* **Content Discovery**: Help users find relevant content using natural language
* **AI Agent Integration**: Make your site accessible to ChatGPT, Claude, and other AI systems
* **Documentation Sites**: Enable natural language search through technical documentation
* **E-commerce**: Help customers find products by describing what they need

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "WPNLWeb"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Upload the `wpnlweb` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

= Setup =

1. Go to Settings > WPNLWeb in your admin panel
2. Configure your settings (rate limits, API keys, etc.)
3. Test the endpoint using the built-in test interface
4. Add the `[wpnlweb]` shortcode to any page where you want search functionality

== Usage ==

= Shortcode Usage =

Add natural language search to any page or post:

**Basic Usage:**
`[wpnlweb]`

**Customized:**
`[wpnlweb placeholder="Search our knowledge base..." button_text="Find Answers" max_results="5"]`

**With Custom Styling:**
`[wpnlweb class="my-custom-search" show_results="true"]`

= Shortcode Attributes =

* `placeholder` - Custom placeholder text for the search input (default: "Ask a question about this site...")
* `button_text` - Custom text for the search button (default: "Search")
* `max_results` - Maximum number of results to display, 1-50 (default: 10)
* `show_results` - Whether to show results on the same page, true/false (default: true)
* `class` - Additional CSS class for custom styling (default: "wpnlweb-search-form")

= Theme Customization =

**Modern Light Theme:** WPNLWeb now features a beautiful, modern light theme by default with:

* Clean white backgrounds with subtle shadows
* Professional blue color scheme
* Smooth animations and hover effects
* Responsive design for all devices
* Automatic dark mode support based on user preferences

**Admin Settings:** Go to Settings > WPNLWeb to customize:

* Theme mode (Auto, Light, Dark)
* Primary color picker
* Custom CSS editor with syntax reference

**CSS Custom Properties:** Easily customize colors using CSS variables:

```css
:root {
  --wpnlweb-primary-color: #3b82f6;    /* Main brand color */
  --wpnlweb-primary-hover: #2563eb;    /* Hover state */
  --wpnlweb-bg-primary: #ffffff;       /* Background */
  --wpnlweb-text-primary: #1f2937;     /* Text color */
  --wpnlweb-border-radius: 8px;        /* Rounded corners */
}
```

**Developer Hooks:** Use filters to customize programmatically:

```php
// Change primary color
add_filter('wpnlweb_primary_color', function() {
    return '#ff6b6b'; // Custom red
});

// Add custom CSS
add_filter('wpnlweb_custom_css', function($css) {
    return $css . '.wpnlweb-search-container { max-width: 800px; }';
});
```

= API Usage =

**Endpoint:** `https://yoursite.com/wp-json/nlweb/v1/ask`

**Method:** POST

**Request Body:**
```json
{
  "question": "What is this website about?",
  "context": {
    "post_type": "post",
    "category": "tutorials",
    "limit": 10
  }
}
```

**Response:**
```json
{
  "@context": "https://schema.org",
  "@type": "SearchResultsPage",
  "query": "What is this website about?",
  "totalResults": 3,
  "items": [
    {
      "@type": "Article",
      "@id": "https://yoursite.com/about/",
      "name": "About Us",
      "description": "Learn about our company mission...",
      "url": "https://yoursite.com/about/",
      "datePublished": "2024-01-15",
      "author": {
        "@type": "Person",
        "name": "John Doe"
      }
    }
  ]
}
```

= Integration with AI Agents =

To connect your site with ChatGPT or other AI agents:

1. Share your endpoint URL: `https://yoursite.com/wp-json/nlweb/v1/ask`
2. Instruct the AI to send POST requests with natural language questions
3. The AI will receive structured, searchable responses about your content

== Frequently Asked Questions ==

= How do I add search to my website? =

Simply add the `[wpnlweb]` shortcode to any page, post, or widget area where you want the search functionality to appear.

= Is this compatible with my theme? =

Yes! The shortcode is designed to work with any WordPress theme. The search form uses responsive CSS that adapts to your theme's styling.

= How do AI agents like ChatGPT use this? =

AI agents can send natural language questions to your `/wp-json/nlweb/v1/ask` endpoint and receive structured responses. This makes your website's content accessible to AI systems.

= Can I customize the search results? =

Yes! You can customize the shortcode appearance using the available attributes, add custom CSS classes, and use WordPress hooks to modify the search behavior.

= Does this work with custom post types? =

Absolutely! WPNLWeb works with all WordPress post types, including custom post types, pages, and any content created by other plugins.

= Is this secure? =

Yes! The plugin includes input sanitization, XSS protection, rate limiting, and optional API key authentication. All WordPress security best practices are followed.

= Will this slow down my website? =

No! The plugin is performance-optimized with response times under 500ms. Assets are only loaded when the shortcode is used, and the API endpoint is cached.

= Can I track usage? =

Yes! The admin dashboard includes usage statistics showing total queries, response times, and success rates.

== Screenshots ==

1. Admin dashboard showing plugin status, endpoint URL, and usage statistics
2. Settings page with configuration options and test interface
3. Frontend search shortcode in action on a website
4. Search results displayed in a clean, responsive format
5. Test interface showing JSON API response for developers

== Changelog ==

= 1.0.0 =
* Initial release
* NLWeb protocol implementation with REST API endpoint
* Frontend search shortcode with configurable attributes
* Schema.org compliant JSON responses
* MCP server compatibility
* Admin dashboard with settings and analytics
* Performance optimization and caching
* Security features including rate limiting
* WordPress.org compliance and standards

== Upgrade Notice ==

= 1.0.0 =
Initial release of WPNLWeb. Transform your WordPress site into an AI-accessible endpoint with natural language search capabilities.

== Developer Information ==

= Hooks and Filters =

The plugin provides several hooks for customization:

* `wpnlweb_search_results` - Filter search results before display
* `wpnlweb_query_args` - Modify WP_Query arguments
* `wpnlweb_response_format` - Customize API response format
* `wpnlweb_shortcode_attributes` - Filter shortcode attributes

= Technical Specifications =

* **Response Time:** <500ms average
* **Concurrent Requests:** Supports 100+ simultaneous requests
* **Memory Usage:** <64MB additional overhead
* **Database Queries:** <5 per request
* **Caching:** WordPress object cache integration
* **Security:** Input sanitization, rate limiting, optional API authentication

= System Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher
* mod_rewrite enabled (for pretty permalinks)

For technical support and documentation, visit [wpnlweb.com](https://wpnlweb.com)