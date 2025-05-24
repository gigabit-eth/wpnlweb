# 🎨 WPNLWeb Customization Guide

Complete guide for customizing the appearance, behavior, and integration of the WPNLWeb plugin.

## 🚀 Quick Start

WPNLWeb is designed to be highly customizable and integrate seamlessly with any WordPress theme. This guide covers everything from basic styling to advanced theme integration.

## 🎯 Customization Overview

### What You Can Customize

- **Visual Design** - Colors, fonts, spacing, animations
- **Layout & Structure** - Search form layout, results display
- **Functionality** - Search behavior, result formatting
- **Integration** - Theme compatibility, custom post types
- **User Experience** - Interactions, responsiveness

### Customization Methods

1. **CSS Variables** - Quick color and spacing changes
2. **WordPress Filters** - Functional customization via hooks
3. **Custom CSS** - Advanced styling overrides
4. **Template Overrides** - Complete layout control
5. **Theme Integration** - Deep integration with your theme

## 🎨 CSS Customization

### CSS Variables

WPNLWeb uses CSS custom properties for easy theming:

```css
:root {
  /* Colors */
  --wpnlweb-primary-color: #3b82f6;
  --wpnlweb-primary-hover: #2563eb;
  --wpnlweb-secondary-color: #1f2937;
  --wpnlweb-background-color: #ffffff;
  --wpnlweb-text-color: #1f2937;
  --wpnlweb-border-color: #e5e7eb;
  --wpnlweb-error-color: #ef4444;
  --wpnlweb-success-color: #10b981;

  /* Layout */
  --wpnlweb-border-radius: 8px;
  --wpnlweb-box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --wpnlweb-max-width: 600px;

  /* Typography */
  --wpnlweb-font-family: inherit;
  --wpnlweb-font-size: 16px;
  --wpnlweb-line-height: 1.5;

  /* Spacing */
  --wpnlweb-spacing-xs: 0.25rem;
  --wpnlweb-spacing-sm: 0.5rem;
  --wpnlweb-spacing-md: 1rem;
  --wpnlweb-spacing-lg: 1.5rem;
  --wpnlweb-spacing-xl: 2rem;

  /* Transitions */
  --wpnlweb-transition-duration: 0.2s;
  --wpnlweb-transition-timing: ease-in-out;
}
```

### Quick Color Themes

#### Dark Theme

```css
:root {
  --wpnlweb-primary-color: #60a5fa;
  --wpnlweb-primary-hover: #3b82f6;
  --wpnlweb-background-color: #1f2937;
  --wpnlweb-text-color: #f9fafb;
  --wpnlweb-border-color: #374151;
}
```

#### Elegant Theme

```css
:root {
  --wpnlweb-primary-color: #8b5cf6;
  --wpnlweb-primary-hover: #7c3aed;
  --wpnlweb-background-color: #fafaf9;
  --wpnlweb-text-color: #292524;
  --wpnlweb-border-radius: 12px;
  --wpnlweb-box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}
```

#### Minimal Theme

```css
:root {
  --wpnlweb-primary-color: #000000;
  --wpnlweb-primary-hover: #333333;
  --wpnlweb-background-color: #ffffff;
  --wpnlweb-border-radius: 0;
  --wpnlweb-box-shadow: none;
  --wpnlweb-border-color: #000000;
}
```

### Component-Specific Styling

#### Search Container

```css
.wpnlweb-search-container {
  background: var(--wpnlweb-background-color);
  border-radius: var(--wpnlweb-border-radius);
  box-shadow: var(--wpnlweb-box-shadow);
  padding: var(--wpnlweb-spacing-lg);
  max-width: var(--wpnlweb-max-width);
  margin: 0 auto;
}

/* Custom container styling */
.wpnlweb-search-container.custom-style {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  padding: 2rem;
  backdrop-filter: blur(10px);
}
```

#### Search Form

```css
.wpnlweb-search-form {
  display: flex;
  flex-direction: column;
  gap: var(--wpnlweb-spacing-md);
}

.wpnlweb-search-input-wrapper {
  display: flex;
  gap: var(--wpnlweb-spacing-sm);
  align-items: stretch;
}

.wpnlweb-search-input {
  flex: 1;
  padding: var(--wpnlweb-spacing-md);
  border: 1px solid var(--wpnlweb-border-color);
  border-radius: var(--wpnlweb-border-radius);
  font-size: var(--wpnlweb-font-size);
  font-family: var(--wpnlweb-font-family);
  background: var(--wpnlweb-background-color);
  color: var(--wpnlweb-text-color);
  transition: all var(--wpnlweb-transition-duration) var(
      --wpnlweb-transition-timing
    );
}

.wpnlweb-search-input:focus {
  outline: none;
  border-color: var(--wpnlweb-primary-color);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
```

#### Search Button

```css
.wpnlweb-search-button {
  padding: var(--wpnlweb-spacing-md) var(--wpnlweb-spacing-lg);
  background: var(--wpnlweb-primary-color);
  color: white;
  border: none;
  border-radius: var(--wpnlweb-border-radius);
  font-size: var(--wpnlweb-font-size);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--wpnlweb-transition-duration) var(
      --wpnlweb-transition-timing
    );
  white-space: nowrap;
}

.wpnlweb-search-button:hover {
  background: var(--wpnlweb-primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
```

#### Results Display

```css
.wpnlweb-search-results {
  margin-top: var(--wpnlweb-spacing-lg);
  animation: fadeIn 0.3s ease-in-out;
}

.wpnlweb-results-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: var(--wpnlweb-spacing-md);
  color: var(--wpnlweb-text-color);
}

.wpnlweb-result-item {
  padding: var(--wpnlweb-spacing-md);
  border: 1px solid var(--wpnlweb-border-color);
  border-radius: var(--wpnlweb-border-radius);
  margin-bottom: var(--wpnlweb-spacing-md);
  transition: all var(--wpnlweb-transition-duration) var(
      --wpnlweb-transition-timing
    );
}

.wpnlweb-result-item:hover {
  box-shadow: var(--wpnlweb-box-shadow);
  transform: translateY(-1px);
}

.wpnlweb-result-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: var(--wpnlweb-spacing-sm);
}

.wpnlweb-result-title a {
  color: var(--wpnlweb-primary-color);
  text-decoration: none;
}

.wpnlweb-result-title a:hover {
  text-decoration: underline;
}

.wpnlweb-result-excerpt {
  color: var(--wpnlweb-text-color);
  line-height: var(--wpnlweb-line-height);
  margin-bottom: var(--wpnlweb-spacing-sm);
}

.wpnlweb-result-meta {
  font-size: 0.875rem;
  color: #6b7280;
  display: flex;
  gap: var(--wpnlweb-spacing-md);
}
```

#### Loading States

```css
.wpnlweb-loading {
  display: flex;
  align-items: center;
  gap: var(--wpnlweb-spacing-sm);
  padding: var(--wpnlweb-spacing-md);
  color: var(--wpnlweb-text-color);
}

.wpnlweb-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid var(--wpnlweb-border-color);
  border-top-color: var(--wpnlweb-primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

## 🔧 WordPress Customization

### Using WordPress Filters

#### Custom CSS via Filters

```php
// Add to your theme's functions.php
add_filter('wpnlweb_custom_css', 'my_wpnlweb_styles');

function my_wpnlweb_styles($css) {
    $custom_css = '
        .wpnlweb-search-container {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .wpnlweb-search-input {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
        }

        .wpnlweb-search-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 1rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    ';

    return $css . $custom_css;
}
```

#### Color Customization

```php
// Primary brand color
add_filter('wpnlweb_primary_color', function($color) {
    return '#e74c3c'; // Your brand red
});

// Secondary color
add_filter('wpnlweb_secondary_color', function($color) {
    return '#2c3e50'; // Dark blue-gray
});

// Background color
add_filter('wpnlweb_background_color', function($color) {
    return '#f8f9fa'; // Light gray
});
```

### Shortcode Customization

#### Default Shortcode Options

```php
[wpnlweb
  placeholder="Ask anything about our site..."
  button_text="Search Now"
  max_results="10"
  show_results="true"
  class="my-custom-class"
  theme="dark"
  show_metadata="true"
]
```

#### Custom Shortcode Defaults

```php
add_filter('wpnlweb_shortcode_defaults', function($defaults) {
    $defaults['placeholder'] = 'How can we help you today?';
    $defaults['button_text'] = 'Find Answers';
    $defaults['max_results'] = '8';
    $defaults['class'] = 'my-brand-search';
    return $defaults;
});
```

## 🏗️ Theme Integration

### Full Theme Integration Example

```php
// In your theme's functions.php
class MyTheme_WPNLWeb_Integration {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_filter('wpnlweb_custom_css', array($this, 'theme_styles'));
        add_filter('wpnlweb_primary_color', array($this, 'brand_color'));
        add_filter('wpnlweb_result_template', array($this, 'custom_result_template'), 10, 2);
    }

    public function enqueue_styles() {
        // Ensure our theme styles load after WPNLWeb styles
        wp_enqueue_style(
            'mytheme-wpnlweb',
            get_template_directory_uri() . '/css/wpnlweb-integration.css',
            array('wpnlweb-public'),
            wp_get_theme()->get('Version')
        );
    }

    public function brand_color($color) {
        // Use theme customizer value if available
        return get_theme_mod('primary_color', '#your-default-color');
    }

    public function theme_styles($css) {
        // Get theme fonts and spacing
        $font_family = get_theme_mod('body_font', 'inherit');
        $primary_color = get_theme_mod('primary_color', '#3b82f6');

        $theme_css = "
            .wpnlweb-search-container {
                font-family: {$font_family};
                background: var(--theme-background, #ffffff);
                border: 1px solid var(--theme-border, #e5e7eb);
                box-shadow: var(--theme-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
            }

            .wpnlweb-search-button {
                background: {$primary_color};
                font-family: var(--theme-heading-font, inherit);
            }

            .wpnlweb-result-item {
                background: var(--theme-card-background, #ffffff);
                border: 1px solid var(--theme-card-border, #e5e7eb);
            }
        ";

        return $css . $theme_css;
    }

    public function custom_result_template($template, $result) {
        // Use theme's card layout
        return '
            <article class="theme-card wpnlweb-result-item">
                <header class="card-header">
                    <h3 class="card-title">
                        <a href="' . esc_url($result['url']) . '">' . esc_html($result['name']) . '</a>
                    </h3>
                </header>
                <div class="card-content">
                    <p class="card-excerpt">' . esc_html($result['description']) . '</p>
                </div>
                <footer class="card-footer">
                    <span class="post-date">' . esc_html($result['datePublished']) . '</span>
                    <span class="post-author">by ' . esc_html($result['author']['name']) . '</span>
                </footer>
            </article>
        ';
    }
}

new MyTheme_WPNLWeb_Integration();
```

### Advanced Theme Integration

#### Custom Post Type Integration

```php
// Add custom post types to search
add_filter('wpnlweb_searchable_post_types', function($post_types) {
    // Add your theme's custom post types
    $theme_post_types = array('portfolio', 'testimonial', 'service');
    return array_merge($post_types, $theme_post_types);
});

// Customize results for different post types
add_filter('wpnlweb_search_results', function($results, $query) {
    foreach ($results as &$result) {
        switch ($result->post_type) {
            case 'portfolio':
                $result->featured_image = get_the_post_thumbnail_url($result->ID, 'medium');
                $result->portfolio_category = get_the_terms($result->ID, 'portfolio_category');
                break;

            case 'testimonial':
                $result->client_name = get_post_meta($result->ID, 'client_name', true);
                $result->rating = get_post_meta($result->ID, 'rating', true);
                break;

            case 'service':
                $result->price_range = get_post_meta($result->ID, 'price_range', true);
                $result->service_features = get_post_meta($result->ID, 'features', true);
                break;
        }
    }
    return $results;
}, 10, 2);
```

#### Theme Customizer Integration

```php
// Add WPNLWeb options to theme customizer
add_action('customize_register', function($wp_customize) {
    // Add section
    $wp_customize->add_section('wpnlweb_styling', array(
        'title' => 'Search Interface',
        'priority' => 130,
    ));

    // Search form style
    $wp_customize->add_setting('wpnlweb_form_style', array(
        'default' => 'rounded',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('wpnlweb_form_style', array(
        'label' => 'Search Form Style',
        'section' => 'wpnlweb_styling',
        'type' => 'select',
        'choices' => array(
            'rounded' => 'Rounded',
            'square' => 'Square',
            'pill' => 'Pill Shape',
        ),
    ));

    // Enable dark mode
    $wp_customize->add_setting('wpnlweb_dark_mode', array(
        'default' => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));

    $wp_customize->add_control('wpnlweb_dark_mode', array(
        'label' => 'Enable Dark Mode',
        'section' => 'wpnlweb_styling',
        'type' => 'checkbox',
    ));
});

// Apply customizer settings
add_filter('wpnlweb_custom_css', function($css) {
    $form_style = get_theme_mod('wpnlweb_form_style', 'rounded');
    $dark_mode = get_theme_mod('wpnlweb_dark_mode', false);

    $custom_css = '';

    // Form style
    switch ($form_style) {
        case 'square':
            $custom_css .= '.wpnlweb-search-input, .wpnlweb-search-button { border-radius: 0; }';
            break;
        case 'pill':
            $custom_css .= '.wpnlweb-search-input, .wpnlweb-search-button { border-radius: 50px; }';
            break;
    }

    // Dark mode
    if ($dark_mode) {
        $custom_css .= '
            .wpnlweb-search-container {
                background: #1f2937;
                color: #f9fafb;
                border-color: #374151;
            }
            .wpnlweb-search-input {
                background: #374151;
                color: #f9fafb;
                border-color: #4b5563;
            }
        ';
    }

    return $css . $custom_css;
});
```

## 📱 Responsive Design

### Mobile-First Approach

```css
/* Mobile First (default) */
.wpnlweb-search-container {
  padding: var(--wpnlweb-spacing-md);
  margin: var(--wpnlweb-spacing-md);
}

.wpnlweb-search-input-wrapper {
  flex-direction: column;
  gap: var(--wpnlweb-spacing-sm);
}

.wpnlweb-search-button {
  width: 100%;
  padding: var(--wpnlweb-spacing-md);
}

/* Tablet */
@media (min-width: 768px) {
  .wpnlweb-search-container {
    padding: var(--wpnlweb-spacing-lg);
    margin: var(--wpnlweb-spacing-lg) auto;
  }

  .wpnlweb-search-input-wrapper {
    flex-direction: row;
    align-items: stretch;
  }

  .wpnlweb-search-button {
    width: auto;
    min-width: 120px;
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .wpnlweb-search-container {
    padding: var(--wpnlweb-spacing-xl);
  }

  .wpnlweb-result-item {
    display: flex;
    align-items: flex-start;
    gap: var(--wpnlweb-spacing-md);
  }

  .wpnlweb-result-content {
    flex: 1;
  }

  .wpnlweb-result-meta {
    min-width: 200px;
    text-align: right;
  }
}
```

### Touch-Friendly Design

```css
/* Larger touch targets for mobile */
@media (max-width: 767px) {
  .wpnlweb-search-input {
    min-height: 48px;
    font-size: 16px; /* Prevents zoom on iOS */
  }

  .wpnlweb-search-button {
    min-height: 48px;
    font-size: 16px;
  }

  .wpnlweb-result-item {
    padding: var(--wpnlweb-spacing-lg);
    margin-bottom: var(--wpnlweb-spacing-lg);
  }
}
```

## ⚡ Performance Optimization

### CSS Optimization

```php
// Conditionally load styles only when shortcode is used
add_filter('wpnlweb_enqueue_styles_condition', function($should_enqueue) {
    global $post;

    // Only load on pages that use the shortcode
    if (is_singular() && has_shortcode($post->post_content, 'wpnlweb')) {
        return true;
    }

    // Or on specific pages
    if (is_page(array('search', 'help', 'faq'))) {
        return true;
    }

    return false;
});

// Inline critical CSS
add_action('wp_head', function() {
    if (is_page('search')) {
        echo '<style>
            .wpnlweb-search-container {
                background: #ffffff;
                border-radius: 8px;
                padding: 1.5rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
        </style>';
    }
});
```

## 🎨 Pre-Built Themes

### Corporate Theme

```css
.wpnlweb-corporate-theme {
  --wpnlweb-primary-color: #1e40af;
  --wpnlweb-secondary-color: #1f2937;
  --wpnlweb-background-color: #f8fafc;
  --wpnlweb-border-radius: 4px;
  --wpnlweb-font-family: "Inter", sans-serif;
}

.wpnlweb-corporate-theme .wpnlweb-search-container {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border: 1px solid #cbd5e1;
}

.wpnlweb-corporate-theme .wpnlweb-search-button {
  background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
```

### Creative Theme

```css
.wpnlweb-creative-theme {
  --wpnlweb-primary-color: #8b5cf6;
  --wpnlweb-secondary-color: #ec4899;
  --wpnlweb-background-color: #faf5ff;
  --wpnlweb-border-radius: 20px;
}

.wpnlweb-creative-theme .wpnlweb-search-container {
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.wpnlweb-creative-theme .wpnlweb-search-container::before {
  content: "";
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
  animation: float 20s infinite linear;
}

@keyframes float {
  0% {
    transform: translateX(-100px) translateY(-100px);
  }
  100% {
    transform: translateX(100px) translateY(100px);
  }
}
```

### Minimalist Theme

```css
.wpnlweb-minimal-theme {
  --wpnlweb-primary-color: #000000;
  --wpnlweb-secondary-color: #666666;
  --wpnlweb-background-color: #ffffff;
  --wpnlweb-border-radius: 0;
  --wpnlweb-box-shadow: none;
}

.wpnlweb-minimal-theme .wpnlweb-search-container {
  border: 2px solid #000000;
  background: transparent;
}

.wpnlweb-minimal-theme .wpnlweb-search-input {
  border: none;
  border-bottom: 1px solid #000000;
  border-radius: 0;
  background: transparent;
}

.wpnlweb-minimal-theme .wpnlweb-search-button {
  background: #000000;
  border-radius: 0;
  border: none;
  font-weight: 400;
  text-transform: lowercase;
}
```

## 🔗 Related Documentation

- [API Reference](api.md) - Complete API documentation
- [Hooks Reference](hooks.md) - WordPress filters and actions
- [WordPress Theme Development](https://developer.wordpress.org/themes/) - Official WordPress theme docs
- [CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/--*) - MDN CSS variables documentation
