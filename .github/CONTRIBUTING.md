# Contributing to GD Chatbot

Thank you for your interest in contributing to GD Chatbot!

## Getting Started

1. Fork the repository
2. Clone your fork locally
3. Create a feature branch from `main`
4. Make your changes
5. Test thoroughly
6. Submit a pull request

## Development Setup

### Requirements
- PHP 7.4 or higher
- WordPress 5.9 or higher
- A local WordPress development environment

### Installation for Development
1. Clone the repository into your WordPress `wp-content/plugins/` directory
2. Rename the `plugin/` directory to `gd-chatbot/`
3. Activate the plugin in WordPress admin

### Building Releases
```bash
cd scripts
./build-release.sh [version]
```

## Coding Standards

### PHP
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use `gd_chatbot_` prefix for all functions
- Use `GD_Chatbot_` or `GD_` prefix for all classes
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize all input: `sanitize_text_field()`, etc.
- Use nonces for form submissions
- Use prepared statements for database queries

### JavaScript
- Use vanilla JavaScript (no jQuery required)
- Follow WordPress JavaScript coding standards
- Use `gdChatbot` namespace for global objects

### CSS
- Use `.gd-chatbot-` prefix for all class names
- Support both light and dark themes
- Ensure responsive design

## File Structure

```
plugin/
├── gd-chatbot.php          # Main plugin file
├── admin/                   # Admin interface
│   ├── class-admin-settings.php
│   ├── css/
│   └── js/
├── includes/               # Core classes
│   ├── class-chat-handler.php
│   ├── class-claude-api.php
│   └── ...
├── public/                 # Frontend
│   ├── class-chatbot-public.php
│   ├── css/
│   └── js/
└── context/                # Knowledge base
```

## Testing

Before submitting a PR:

1. **Activation Test**: Plugin activates without errors
2. **Deactivation Test**: Plugin deactivates cleanly
3. **Shortcode Test**: `[gd_chatbot]` renders correctly
4. **Widget Test**: Floating widget works
5. **Admin Test**: Settings page functions properly
6. **API Test**: Claude API integration works (if keys configured)
7. **Browser Test**: Test in Chrome, Safari, Firefox

## Submitting Changes

### Pull Request Process
1. Update documentation if needed
2. Update CHANGELOG.md with your changes
3. Ensure all tests pass
4. Submit PR against `main` branch
5. Wait for review

### Commit Messages
Use clear, descriptive commit messages:
- `Add: new feature description`
- `Fix: bug description`
- `Update: what was updated`
- `Refactor: what was refactored`
- `Docs: documentation changes`

## Questions?

If you have questions about contributing, please open an issue for discussion.

## License

By contributing, you agree that your contributions will be licensed under the GPL-2.0+ license.
