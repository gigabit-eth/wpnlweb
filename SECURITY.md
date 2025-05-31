# Security Policy

## Supported Versions

We actively support the following versions of WPNLWeb with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security vulnerability in WPNLWeb, please report it to us responsibly.

### How to Report

1. **Email**: Send details to security@wpnlweb.sh
2. **Subject**: Include "WPNLWeb Security" in the subject line
3. **Include**:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Your contact information

### What to Expect

- **Acknowledgment**: We'll acknowledge receipt within 48 hours
- **Initial Assessment**: We'll provide an initial assessment within 5 business days
- **Updates**: We'll keep you informed of our progress
- **Resolution**: We aim to resolve critical issues within 30 days

### Responsible Disclosure

- Please do not publicly disclose the vulnerability until we've had a chance to address it
- We'll work with you to understand and resolve the issue
- We'll credit you in our security advisory (if desired)

### Security Best Practices

When using WPNLWeb:

1. **Keep Updated**: Always use the latest version
2. **Secure WordPress**: Ensure your WordPress installation is secure
3. **Rate Limiting**: Consider implementing rate limiting for the API endpoint
4. **HTTPS**: Always use HTTPS for API communications
5. **Input Validation**: The plugin sanitizes inputs, but additional validation is always good
6. **Access Control**: Consider who needs access to the admin settings

### Security Features

WPNLWeb includes several security features:

- **Input Sanitization**: All user inputs are sanitized using WordPress functions
- **Nonce Verification**: CSRF protection on all forms
- **Capability Checks**: Admin functions require proper WordPress capabilities
- **XSS Protection**: Output is properly escaped
- **SQL Injection Prevention**: Uses WordPress database abstraction layer

## Bug Bounty

Currently, we don't have a formal bug bounty program, but we appreciate responsible disclosure and will acknowledge security researchers who help improve WPNLWeb's security.

## Contact

For security-related questions or concerns:

- Email: security@wpnlweb.sh
- Website: https://wpnlweb.com/security

Thank you for helping keep WPNLWeb secure!
