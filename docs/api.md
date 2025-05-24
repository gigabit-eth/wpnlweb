# 📚 WPNLWeb API Reference

Complete documentation for the WPNLWeb Plugin REST API and integration methods.

## 🚀 Quick Start

The WPNLWeb plugin provides a RESTful API endpoint that allows AI agents and applications to query WordPress content using natural language.

**Base URL:** `https://yoursite.com/wp-json/nlweb/v1/`

## 🔌 REST API Endpoints

### POST /ask

Ask natural language questions about your WordPress content.

**Endpoint:** `POST /wp-json/nlweb/v1/ask`

#### Authentication

- **Public Access:** No authentication required
- **CORS Support:** Enabled for AI agents and browser applications
- **Rate Limiting:** Not implemented (consider adding for production)

#### Request Format

```json
{
  "question": "string (required)",
  "context": {
    "post_type": "string|array (optional)",
    "category": "string (optional)",
    "limit": "integer (optional, default: 10)",
    "meta_query": "object (optional)"
  }
}
```

#### Request Parameters

| Parameter            | Type         | Required | Description                                                    |
| -------------------- | ------------ | -------- | -------------------------------------------------------------- |
| `question`           | string       | Yes      | Natural language question to ask                               |
| `context`            | object       | No       | Additional query context and filters                           |
| `context.post_type`  | string/array | No       | WordPress post type(s) to search (default: `["post", "page"]`) |
| `context.category`   | string       | No       | Category slug to filter by                                     |
| `context.limit`      | integer      | No       | Maximum number of results (default: 10)                        |
| `context.meta_query` | object       | No       | WordPress meta query parameters                                |

#### Response Format

The API returns Schema.org compliant responses:

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

#### Response Fields

| Field                    | Type    | Description                           |
| ------------------------ | ------- | ------------------------------------- |
| `@context`               | string  | Schema.org context URL                |
| `@type`                  | string  | Schema.org type (SearchResultsPage)   |
| `query`                  | string  | Original question asked               |
| `totalResults`           | integer | Number of results found               |
| `processingTime`         | string  | Time taken to process request         |
| `items`                  | array   | Array of search results               |
| `items[].@type`          | string  | Schema.org type (Article, Page, etc.) |
| `items[].@id`            | string  | Canonical URL of the content          |
| `items[].name`           | string  | Title of the content                  |
| `items[].description`    | string  | Excerpt or summary                    |
| `items[].url`            | string  | Public URL                            |
| `items[].datePublished`  | string  | Publication date (ISO 8601)           |
| `items[].dateModified`   | string  | Last modified date (ISO 8601)         |
| `items[].author`         | object  | Author information                    |
| `items[].keywords`       | array   | Extracted keywords                    |
| `items[].relevanceScore` | float   | Relevance score (0-1)                 |

#### Error Responses

```json
{
  "code": "missing_question",
  "message": "Question parameter required",
  "data": {
    "status": 400
  }
}
```

Common error codes:

- `missing_question` - No question parameter provided
- `invalid_context` - Invalid context parameters
- `search_failed` - Internal search error

## 🤖 AI Agent Integration

### ChatGPT Integration

```javascript
// Custom GPT Instructions
const wpnlwebQuery = async (siteUrl, question, context = {}) => {
  const response = await fetch(`${siteUrl}/wp-json/nlweb/v1/ask`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      question: question,
      context: context,
    }),
  });

  return response.json();
};

// Usage
const results = await wpnlwebQuery(
  "https://example.com",
  "What are your latest blog posts?",
  { post_type: "post", limit: 5 }
);
```

### Claude/Anthropic Integration

```python
import requests

class WPNLWebClient:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')

    def ask(self, question, context=None):
        """Query WordPress site via natural language"""
        endpoint = f"{self.base_url}/wp-json/nlweb/v1/ask"

        payload = {"question": question}
        if context:
            payload["context"] = context

        response = requests.post(
            endpoint,
            json=payload,
            headers={"Content-Type": "application/json"}
        )

        return response.json()

# Usage
client = WPNLWebClient("https://example.com")
results = client.ask(
    "What services do you offer?",
    context={"post_type": "service", "limit": 10}
)
```

### curl Examples

```bash
# Basic question
curl -X POST https://example.com/wp-json/nlweb/v1/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "What is this website about?"}'

# With context filters
curl -X POST https://example.com/wp-json/nlweb/v1/ask \
  -H "Content-Type: application/json" \
  -d '{
    "question": "Show me recent tutorials",
    "context": {
      "post_type": "post",
      "category": "tutorials",
      "limit": 5
    }
  }'

# Search specific post types
curl -X POST https://example.com/wp-json/nlweb/v1/ask \
  -H "Content-Type: application/json" \
  -d '{
    "question": "What products do you sell?",
    "context": {
      "post_type": ["product", "service"],
      "limit": 10
    }
  }'
```

## 🔄 AJAX Endpoints

### WordPress AJAX Handler

**Endpoint:** `admin-ajax.php?action=wpnlweb_search`

Used internally by the frontend shortcode for same-origin requests.

#### Parameters

| Parameter       | Type    | Required | Description                   |
| --------------- | ------- | -------- | ----------------------------- |
| `action`        | string  | Yes      | Must be `wpnlweb_search`      |
| `question`      | string  | Yes      | The question to ask           |
| `max_results`   | integer | No       | Maximum results (default: 10) |
| `wpnlweb_nonce` | string  | Yes      | WordPress nonce for security  |

#### Response

Returns the same Schema.org format as the REST API but wrapped in a WordPress AJAX response.

## 🚦 Rate Limiting & Performance

### Current Implementation

- **No Rate Limiting:** Currently not implemented
- **Caching:** Not implemented (consider adding)
- **Response Time:** Typically < 500ms

### Recommended Production Settings

```php
// Add to your theme's functions.php or plugin
add_filter('wpnlweb_rate_limit', function($limit) {
    return 60; // 60 requests per hour per IP
});

add_filter('wpnlweb_cache_timeout', function($timeout) {
    return 300; // Cache for 5 minutes
});
```

## 🔒 Security Considerations

### CORS Headers

The plugin automatically adds CORS headers for AI agent compatibility:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST, GET, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
```

### Input Sanitization

All inputs are sanitized using WordPress functions:

- `sanitize_text_field()` for question parameter
- `intval()` for numeric parameters
- `array_map()` for array sanitization

### Recommended Security Enhancements

```php
// Restrict access to specific domains
add_filter('wpnlweb_allowed_origins', function($origins) {
    return ['https://youragent.com', 'https://trusted-ai.com'];
});

// Add API key authentication
add_filter('wpnlweb_require_auth', '__return_true');
```

## 🧪 Testing & Debugging

### Test the API

```bash
# Test connectivity
curl -I https://yoursite.com/wp-json/nlweb/v1/ask

# Test basic functionality
curl -X POST https://yoursite.com/wp-json/nlweb/v1/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "test"}' \
  | jq .
```

### Debug Mode

Add to `wp-config.php`:

```php
define('WPNLWEB_DEBUG', true);
```

This enables additional logging and error reporting.

### WordPress Debug Info

```php
// Check if plugin is active
if (class_exists('Wpnlweb_Server')) {
    echo "WPNLWeb plugin is active";
}

// Test API endpoint programmatically
$request = new WP_REST_Request('POST');
$request->set_param('question', 'test question');

$server = new Wpnlweb_Server('wpnlweb', WPNLWEB_VERSION);
$response = $server->handle_ask($request);
```

## 📊 Response Examples

### Successful Search

```json
{
  "@context": "https://schema.org",
  "@type": "SearchResultsPage",
  "query": "latest blog posts about WordPress",
  "totalResults": 3,
  "processingTime": "0.182s",
  "items": [
    {
      "@type": "Article",
      "@id": "https://example.com/wordpress-security-guide/",
      "name": "Complete WordPress Security Guide 2024",
      "description": "Learn the essential security practices to protect your WordPress site from threats and vulnerabilities...",
      "url": "https://example.com/wordpress-security-guide/",
      "datePublished": "2024-01-20T10:00:00Z",
      "dateModified": "2024-01-21T14:30:00Z",
      "author": {
        "@type": "Person",
        "name": "Jane Smith"
      }
    }
  ]
}
```

### Empty Results

```json
{
  "@context": "https://schema.org",
  "@type": "SearchResultsPage",
  "query": "nonexistent content",
  "totalResults": 0,
  "processingTime": "0.045s",
  "items": []
}
```

### Error Response

```json
{
  "code": "missing_question",
  "message": "Question parameter required",
  "data": {
    "status": 400
  }
}
```

## 🔗 Related Documentation

- [Hooks Reference](hooks.md) - WordPress filters and actions
- [Customization Guide](customization.md) - Theming and styling
- [WordPress REST API](https://developer.wordpress.org/rest-api/) - Official WordPress REST API docs
- [Schema.org](https://schema.org/) - Structured data standards
