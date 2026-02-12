# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.0.x   | :white_check_mark: |
| 1.9.x   | :x:                |
| < 1.9   | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability in GD Chatbot, please report it responsibly.

### How to Report

1. **Do NOT** create a public GitHub issue for security vulnerabilities
2. Email details to: [security contact email]
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Any suggested fixes

### What to Expect

- Acknowledgment within 48 hours
- Assessment within 1 week
- Fix timeline communicated
- Credit in release notes (if desired)

## Security Best Practices

### For Site Administrators

1. **API Keys**: Store API keys securely
   - Use WordPress options (encrypted if possible)
   - Never expose in client-side code
   - Consider using wp-config.php constants for production

2. **User Access**: Restrict plugin settings access
   - Only administrators should configure API keys
   - Review user capabilities regularly

3. **Updates**: Keep the plugin updated
   - Security patches are released as needed
   - Enable auto-updates if appropriate

### Built-in Security Measures

The plugin implements these security measures:

1. **Input Sanitization**: All user input is sanitized
2. **Output Escaping**: All output is properly escaped
3. **Nonce Verification**: All AJAX requests use nonces
4. **Capability Checks**: Admin functions check user capabilities
5. **Prepared Statements**: Database queries use `$wpdb->prepare()`

## Known Security Considerations

1. **API Keys**: Stored in WordPress options table
   - Consider using constants in wp-config.php for additional security
   - Restrict database access appropriately

2. **Conversation Logs**: Chat history stored in database
   - May contain sensitive information
   - Consider implementing log retention policies

3. **External API Calls**: Plugin makes calls to:
   - Anthropic Claude API
   - Tavily Search API (if enabled)
   - Pinecone API (if enabled)
   
   Ensure your server allows these connections and review data transmission.

## Changelog

Security-related changes are documented in CHANGELOG.md with `[Security]` tags.
