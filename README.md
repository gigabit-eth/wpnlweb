# 🤖 WPNLWeb - WordPress Natural Language Web Plugin

<img src="https://wpnlweb.com/assets/banner-1544x500.png" alt="WPNLWeb Search Interface" />

[![WordPress](https://img.shields.io/wordpress/plugin/v/wpnlweb.svg)](https://wordpress.org/plugins/wpnlweb/)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)](https://php.net/)
[![WordPress](https://img.shields.io/badge/wordpress-%3E%3D5.0-blue)](https://wordpress.org/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests](https://img.shields.io/badge/tests-98.4%25-brightgreen)](PHASE2_COMPLETION_REPORT.md)

> Transform your WordPress site into a conversational interface for users and AI agents using Microsoft's NLWeb Protocol.

## 🚀 Features

- **🔌 NLWeb Protocol Implementation** - Standards-compliant REST API endpoint
- **🎯 Frontend Search Shortcode** - Natural language search for visitors
- **🤖 AI Agent Ready** - Compatible with ChatGPT, Claude, and other AI systems
- **📋 Schema.org Compliant** - Structured responses that AI agents understand
- **⚡ High Performance** - <500ms response times with caching
- **🎨 Modern UI** - Beautiful, responsive search interface
- **🔧 Developer Friendly** - Extensive hooks, filters, and customization options
- **🛡️ Security First** - Input sanitization, CORS headers, rate limiting
- **📱 Mobile Optimized** - Works seamlessly on all devices

## 📸 Screenshots

![WPNLWeb Search Interface](https://wpnlweb.com/assets/screenshot-1.png)
_Natural language search interface_

![Admin Dashboard](https://wpnlweb.com/assets/screenshot-2.png)
_Settings and configuration panel_

![API Response](https://wpnlweb.com/assets/screenshot-3.png)
_Schema.org compliant API responses_

## 🎯 Quick Start

### For End Users

1. **Install the Plugin**

   ```bash
   # From WordPress Admin
   Plugins > Add New > Search "WPNLWeb" > Install > Activate

   # Or download from WordPress.org
   wget https://downloads.wordpress.org/plugin/wpnlweb.zip
   ```

2. **Add Search to Any Page**

   ```php
   [wpnlweb placeholder="Ask anything about our site..." max_results="5"]
   ```

3. **Configure Settings**
   - Go to `Settings > WPNLWeb` in your WordPress admin
   - Customize colors, themes, and behavior
   - Test the API using the built-in interface

### For AI Agents

```bash
# Query your WordPress site via natural language
curl -X POST https://yoursite.com/wp-json/nlweb/v1/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "What products do you sell?"}'
```

## 🛠️ Development Setup

### Prerequisites

- **PHP 7.4+**
- **WordPress 5.0+**
- **Composer** (for development)
- **Node.js 18+** (for frontend development)

### Installation

```bash
# Clone the repository
git clone https://github.com/typewriter/wpnlweb.git
cd wpnlweb

# Install PHP dependencies
composer install

# Set up development environment
composer run dev-setup

# Run code quality checks
composer run lint
```

### Development Commands

```bash
# PHP Code Standards
composer run lint              # Check code standards
composer run lint-fix         # Auto-fix code standards
composer run check-syntax     # Check PHP syntax

# WordPress Development
wp plugin activate wpnlweb    # Activate plugin
wp plugin deactivate wpnlweb  # Deactivate plugin
wp plugin uninstall wpnlweb   # Uninstall plugin
```

## 📚 API Documentation

### REST Endpoint

**Endpoint:** `POST /wp-json/nlweb/v1/ask`

#### Request Format

```json
{
  "question": "What is this website about?",
  "context": {
    "post_type": ["post", "page"],
    "category": "tutorials",
    "limit": 10,
    "meta_query": {
      "featured": "yes"
    }
  }
}
```

#### Response Format

```json
{
  "@context": "https://schema.org",
  "@type": "SearchResultsPage",
  "query": "What is this website about?",
  "totalResults": 3,
  "processingTime": "0.245s",
  "items": [
    {
      "@type": "Article",
      "@id": "https://yoursite.com/about/",
      "name": "About Us",
      "description": "Learn about our company mission and values...",
      "url": "https://yoursite.com/about/",
      "datePublished": "2024-01-15T10:30:00Z",
      "dateModified": "2024-01-20T14:15:00Z",
      "author": {
        "@type": "Person",
        "name": "John Doe"
      },
      "keywords": ["about", "company", "mission"],
      "relevanceScore": 0.95
    }
  ]
}
```

### Shortcode Options

```php
[wpnlweb
  placeholder="Custom placeholder text..."
  button_text="Search Now"
  max_results="10"
  show_results="true"
  class="my-custom-class"
  theme="dark"
  show_metadata="true"
]
```

## 🎨 Customization

### CSS Variables

```css
:root {
  --wpnlweb-primary-color: #3b82f6;
  --wpnlweb-primary-hover: #2563eb;
  --wpnlweb-bg-primary: #ffffff;
  --wpnlweb-text-primary: #1f2937;
  --wpnlweb-border-radius: 8px;
  --wpnlweb-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
```

### WordPress Hooks

```php
// Modify search results
add_filter('wpnlweb_search_results', function($results, $query) {
    // Custom logic here
    return $results;
}, 10, 2);

// Customize API response
add_filter('wpnlweb_api_response', function($response, $question) {
    $response['custom_field'] = 'custom_value';
    return $response;
}, 10, 2);

// Add custom post types to search
add_filter('wpnlweb_searchable_post_types', function($post_types) {
    $post_types[] = 'product';
    $post_types[] = 'event';
    return $post_types;
});
```

### Theme Integration

```php
// In your theme's functions.php
function custom_wpnlweb_styling() {
    wp_add_inline_style('wpnlweb-public', '
        .wpnlweb-search-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .wpnlweb-search-form {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 2rem;
        }
    ');
}
add_action('wp_enqueue_scripts', 'custom_wpnlweb_styling');
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
composer run test

# Run specific test suites
composer run test:unit
composer run test:integration

# Test API endpoints
php debug-api-test.php
```

### Test Coverage

- ✅ **API Functionality**: 100% (26/26 tests)
- ✅ **WordPress.org Compliance**: 96.8% (61/63 tests)
- ✅ **Security**: 100% (All vulnerabilities resolved)
- ✅ **Performance**: Optimized (<500ms response time)

## 🔒 Security

### Implemented Protections

- **Input Sanitization**: All user inputs sanitized using WordPress functions
- **Output Escaping**: All outputs properly escaped
- **ABSPATH Protection**: Direct file access prevention
- **Nonce Verification**: CSRF protection for admin forms
- **Rate Limiting**: API endpoint protection
- **CORS Headers**: Controlled cross-origin access

### Reporting Security Issues

Please see [SECURITY.md](SECURITY.md) for our security policy and how to report vulnerabilities.

## 🌐 AI Agent Integration

### ChatGPT Integration

```javascript
// Custom GPT Instructions
You can query WordPress sites with WPNLWeb by sending POST requests to:
https://SITE_URL/wp-json/nlweb/v1/ask

Send questions in this format:
{
  "question": "What are your latest blog posts about AI?",
  "context": {
    "post_type": "post",
    "limit": 5
  }
}
```

### Claude/Anthropic Integration

```python
import requests

def query_wordpress_site(site_url, question):
    response = requests.post(
        f"{site_url}/wp-json/nlweb/v1/ask",
        json={"question": question},
        headers={"Content-Type": "application/json"}
    )
    return response.json()

# Usage
results = query_wordpress_site(
    "https://example.com",
    "What services do you offer?"
)
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Workflow

1. **Fork** the repository
2. **Clone** your fork locally
3. **Create** a feature branch: `git checkout -b feature/amazing-feature`
4. **Make** your changes and add tests
5. **Run** tests: `composer run test`
6. **Check** code standards: `composer run lint`
7. **Commit** your changes: `git commit -m 'Add amazing feature'`
8. **Push** to your fork: `git push origin feature/amazing-feature`
9. **Submit** a Pull Request

### Code Standards

- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Write comprehensive PHPDoc comments
- Include unit tests for new functionality
- Ensure all tests pass before submitting PR

## 📖 Documentation

- **[Installation Guide](INSTALL.txt)** - Detailed installation instructions
- **[Testing Guide](PHASE2_TESTING_GUIDE.md)** - Comprehensive testing procedures
- **[API Reference](docs/api.md)** - Complete API documentation
- **[Hooks Reference](docs/hooks.md)** - WordPress hooks and filters
- **[Customization Guide](docs/customization.md)** - Theme and styling options

## 🗺️ Roadmap

### Version 1.1 (Planned)

- [ ] Advanced search filters and faceting
- [ ] Real-time search suggestions
- [ ] Search analytics dashboard
- [ ] Multi-language support improvements

### Version 1.2 (Planned)

- [ ] Custom AI model integration
- [ ] Advanced caching mechanisms
- [ ] Elasticsearch integration
- [ ] GraphQL endpoint support

### Version 2.0 (Future)

- [ ] Machine learning-powered relevance scoring
- [ ] Voice search capabilities
- [ ] Advanced AI agent tools
- [ ] Enterprise features

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE.txt](LICENSE.txt) file for details.

## 🙏 Acknowledgments

- **Microsoft** for the NLWeb Protocol specification
- **WordPress Community** for coding standards and best practices
- **Schema.org** for structured data standards
- **Contributors** who have helped improve this plugin

## 📞 Support

- **Documentation**: [Official Docs](https://wpnlweb.com/docs)
- **WordPress.org**: [Plugin Support Forum](https://wordpress.org/support/plugin/wpnlweb/)
- **GitHub Issues**: [Report Bugs](https://github.com/typewriter/wpnlweb/issues)
- **Email**: [team@typewriter.sh](mailto:team@typewriter.sh)

---

<div align="center">

**[Website](https://wpnlweb.com)** • **[Documentation](https://wpnlweb.com/docs)** • **[WordPress.org](https://wordpress.org/plugins/wpnlweb/)** • **[Support](https://wordpress.org/support/plugin/wpnlweb/)**

Made with ❤️ by [TypeWriter](https://typewriter.sh)

</div>
