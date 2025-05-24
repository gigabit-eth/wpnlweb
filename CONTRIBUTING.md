# 🤝 Contributing to WPNLWeb

Thank you for your interest in contributing to WPNLWeb! This guide will help you get started with contributing to our WordPress Natural Language Web plugin.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Guidelines](#contributing-guidelines)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Submitting Changes](#submitting-changes)
- [Issue Reporting](#issue-reporting)
- [Community](#community)

## 🤝 Code of Conduct

This project and everyone participating in it is governed by our commitment to creating a welcoming, inclusive environment. By participating, you agree to:

- **Be respectful** and considerate in all interactions
- **Be collaborative** and help others learn and grow
- **Be patient** with newcomers and different skill levels
- **Focus on constructive feedback** and solutions
- **Respect different viewpoints** and experiences

Report any unacceptable behavior to [team@typewriter.sh](mailto:team@typewriter.sh).

## 🚀 Getting Started

### Prerequisites

Before you begin, ensure you have:

- **PHP 7.4 or higher**
- **WordPress 5.0 or higher** (local development environment)
- **Composer** for dependency management
- **Git** for version control
- **Code editor** with PHP support (VS Code, PhpStorm, etc.)

### Development Tools

We recommend these tools for the best development experience:

- **[Local by Flywheel](https://localwp.com/)** or **[XAMPP](https://www.apachefriends.org/)** for local WordPress
- **[WP-CLI](https://wp-cli.org/)** for WordPress command-line operations
- **[PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)** for code standards
- **[Composer](https://getcomposer.org/)** for PHP dependencies

## 🛠️ Development Setup

### 1. Fork and Clone

```bash
# Fork the repository on GitHub, then clone your fork
git clone https://github.com/YOUR_USERNAME/wpnlweb.git
cd wpnlweb

# Add the original repository as upstream
git remote add upstream https://github.com/gigabit-eth/wpnlweb.git
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Set up development environment
composer run dev-setup
```

### 3. Set Up WordPress Environment

```bash
# If using WP-CLI (recommended)
wp core download
wp config create --dbname=wpnlweb_dev --dbuser=root --dbpass=password
wp core install --url=wpnlweb.local --title="WPNLWeb Dev" --admin_user=admin --admin_password=password --admin_email=dev@example.com

# Symlink the plugin to WordPress
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/wpnlweb

# Activate the plugin
wp plugin activate wpnlweb
```

### 4. Verify Setup

```bash
# Check code standards
composer run lint

# Check PHP syntax
composer run check-syntax

# Test API endpoint (adjust URL as needed)
curl -X POST http://wpnlweb.local/wp-json/nlweb/v1/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "test"}'
```

## 📝 Contributing Guidelines

### Types of Contributions

We welcome various types of contributions:

- **🐛 Bug Reports** - Help us identify and fix issues
- **✨ Feature Requests** - Suggest new functionality
- **📚 Documentation** - Improve guides, comments, and examples
- **🔧 Code Improvements** - Optimize performance, refactor code
- **🧪 Tests** - Add or improve test coverage
- **🌐 Translations** - Help make WPNLWeb available in more languages

### Before You Start

1. **Check existing issues** to avoid duplicating work
2. **Discuss major changes** in an issue before implementing
3. **Follow our coding standards** and best practices
4. **Write tests** for new functionality
5. **Update documentation** as needed

## 📏 Coding Standards

### WordPress Standards

We follow the [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

```php
<?php
/**
 * Example of properly formatted PHP code
 *
 * @package Wpnlweb
 * @since   1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Example class following WordPress standards
 */
class Wpnlweb_Example {

    /**
     * Class property with proper documentation
     *
     * @var string
     */
    private $property_name;

    /**
     * Constructor with proper documentation
     *
     * @param string $param Example parameter.
     */
    public function __construct( $param ) {
        $this->property_name = sanitize_text_field( $param );
    }

    /**
     * Method with proper documentation
     *
     * @param array $args Method arguments.
     * @return array Modified arguments.
     */
    public function example_method( $args = array() ) {
        $defaults = array(
            'option_one' => 'default_value',
            'option_two' => 123,
        );

        $args = wp_parse_args( $args, $defaults );

        // Process and return
        return apply_filters( 'wpnlweb_example_method', $args );
    }
}
```

### Code Quality Rules

1. **Security First**

   ```php
   // ✅ Good - Sanitize input
   $user_input = sanitize_text_field( $_POST['input'] );

   // ✅ Good - Escape output
   echo esc_html( $user_data );

   // ✅ Good - Use nonces for forms
   wp_nonce_field( 'wpnlweb_action', 'wpnlweb_nonce' );
   ```

2. **WordPress Integration**

   ```php
   // ✅ Good - Use WordPress functions
   $posts = get_posts( $args );

   // ✅ Good - Use hooks appropriately
   add_action( 'init', array( $this, 'initialize' ) );
   add_filter( 'wpnlweb_results', array( $this, 'modify_results' ) );
   ```

3. **Performance**
   ```php
   // ✅ Good - Cache expensive operations
   $cache_key = 'wpnlweb_results_' . md5( $query );
   $results = get_transient( $cache_key );
   if ( false === $results ) {
       $results = $this->expensive_operation( $query );
       set_transient( $cache_key, $results, HOUR_IN_SECONDS );
   }
   ```

### File Organization

```
wpnlweb/
├── wpnlweb.php                 # Main plugin file
├── includes/                   # Core functionality
│   ├── class-wpnlweb.php      # Main plugin class
│   ├── class-wpnlweb-server.php
│   └── ...
├── admin/                      # Admin interface
│   ├── class-wpnlweb-admin.php
│   └── partials/
├── public/                     # Frontend functionality
│   ├── class-wpnlweb-public.php
│   └── js/
└── languages/                  # Translation files
```

## 🧪 Testing

### Running Tests

```bash
# Check all code standards
composer run lint

# Fix auto-fixable issues
composer run lint-fix

# Check only errors (ignore warnings)
composer run lint-errors-only

# Test syntax of all PHP files
composer run check-syntax

# Test API functionality
php debug-api-test.php
```

### Writing Tests

1. **Test API Endpoints**

   ```php
   /**
    * Test the NLWeb API endpoint
    */
   public function test_nlweb_api_endpoint() {
       $request = new WP_REST_Request( 'POST', '/nlweb/v1/ask' );
       $request->set_body_params( array(
           'question' => 'test question',
       ) );

       $response = rest_do_request( $request );
       $this->assertEquals( 200, $response->get_status() );
   }
   ```

2. **Test Shortcodes**
   ```php
   /**
    * Test shortcode output
    */
   public function test_wpnlweb_shortcode() {
       $output = do_shortcode( '[wpnlweb]' );
       $this->assertStringContains( 'wpnlweb-search-form', $output );
   }
   ```

### Test Coverage Requirements

- **New features** must include tests
- **Bug fixes** should include regression tests
- **API changes** require updated endpoint tests
- **Shortcode changes** need frontend tests

## 📤 Submitting Changes

### Pull Request Process

1. **Create a Feature Branch**

   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/issue-number-description
   ```

2. **Make Your Changes**

   - Follow coding standards
   - Add tests for new functionality
   - Update documentation as needed
   - Test thoroughly

3. **Commit Your Changes**

   ```bash
   # Stage your changes
   git add .

   # Commit with descriptive message
   git commit -m "Add: Natural language query caching

   - Implement Redis-based caching for API responses
   - Add cache invalidation on content updates
   - Include cache statistics in admin dashboard
   - Fixes #123"
   ```

4. **Update Your Branch**

   ```bash
   # Fetch latest changes from upstream
   git fetch upstream
   git rebase upstream/main
   ```

5. **Run Final Checks**

   ```bash
   # Ensure code meets standards
   composer run lint

   # Test functionality
   php debug-api-test.php
   ```

6. **Push and Create PR**

   ```bash
   git push origin feature/your-feature-name
   ```

   Then create a pull request on GitHub with:

   - Clear description of changes
   - Reference to related issues
   - Screenshots (if UI changes)
   - Test instructions

### Commit Message Format

Use this format for commit messages:

```
Type: Brief description

- Detailed explanation of changes
- Why the change was made
- Any breaking changes
- Related issue numbers

Fixes #123
```

**Types:**

- `Add:` New features
- `Fix:` Bug fixes
- `Update:` Changes to existing features
- `Remove:` Removed features
- `Docs:` Documentation changes
- `Test:` Test additions or changes
- `Refactor:` Code restructuring

### Pull Request Checklist

Before submitting, ensure:

- [ ] Code follows WordPress PHP standards
- [ ] All tests pass (`composer run lint`)
- [ ] New features include tests
- [ ] Documentation is updated
- [ ] No debugging code left in
- [ ] Backwards compatibility maintained
- [ ] Security best practices followed
- [ ] Performance impact considered

## 🐛 Issue Reporting

### Bug Reports

When reporting bugs, include:

1. **WordPress version**
2. **PHP version**
3. **Plugin version**
4. **Steps to reproduce**
5. **Expected behavior**
6. **Actual behavior**
7. **Error messages** (if any)
8. **Browser/environment** details

### Bug Report Template

```markdown
**WordPress Version:** 6.6
**PHP Version:** 8.1
**Plugin Version:** 1.0.0

**Steps to Reproduce:**

1. Go to Settings > WPNLWeb
2. Click "Test API"
3. Error appears

**Expected:** API test should return results
**Actual:** 500 error returned

**Error Message:**
```

Fatal error: Call to undefined function...

```

**Additional Context:**
Using Local by Flywheel on macOS
```

### Feature Requests

For feature requests, provide:

1. **Use case** - Why is this needed?
2. **Proposed solution** - How should it work?
3. **Alternatives** - What other options exist?
4. **Additional context** - Screenshots, examples, etc.

## 🌟 Recognition

Contributors will be recognized in:

- **Plugin credits** (wpnlweb.php header)
- **CONTRIBUTORS.md** file
- **Release notes** for significant contributions
- **Plugin directory** acknowledgments

### Types of Recognition

- **Code Contributors** - Direct code contributions
- **Documentation Contributors** - Improve guides and docs
- **Community Contributors** - Help with support and discussions
- **Testing Contributors** - Bug reports and testing
- **Translation Contributors** - Help with internationalization

## 🗣️ Community

### Communication Channels

- **GitHub Issues** - Bug reports and feature requests
- **GitHub Discussions** - General questions and ideas
- **Email** - [team@typewriter.sh](mailto:team@typewriter.sh)
- **WordPress.org Forum** - User support

### Getting Help

- **WordPress Development** - [WordPress Developer Resources](https://developer.wordpress.org/)
- **PHP Best Practices** - [PHP: The Right Way](https://phptherightway.com/)
- **Git Workflow** - [Atlassian Git Tutorials](https://www.atlassian.com/git/tutorials)

### Contributing Levels

**🥉 Bronze Contributors**

- Fix typos and small bugs
- Improve documentation
- Report detailed bug reports

**🥈 Silver Contributors**

- Add new features
- Improve test coverage
- Help with code reviews

**🥇 Gold Contributors**

- Architectural improvements
- Security enhancements
- Mentoring other contributors

## ❓ Questions?

Don't hesitate to ask! We're here to help:

- **Technical Questions** - Create a GitHub Discussion
- **Process Questions** - Email [team@typewriter.sh](mailto:team@typewriter.sh)
- **Ideas and Feedback** - Open a GitHub Issue

---

Thank you for contributing to WPNLWeb! Your efforts help make WordPress more accessible to AI agents and provide better search experiences for users worldwide. 🎉
