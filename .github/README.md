# GitHub Configuration

This directory contains GitHub-specific configuration files for the GD Chatbot project.

## Contents

### Issue Templates (`ISSUE_TEMPLATE/`)
- `bug_report.md` - Template for bug reports
- `feature_request.md` - Template for feature requests

### Workflows (`workflows/`)
- Currently no automated workflows configured

## Repository Settings

If this repository is pushed to GitHub, configure:

1. **Branch Protection** (main branch)
   - Require pull request reviews
   - Require status checks to pass

2. **Secrets** (if using workflows)
   - No secrets currently required

3. **Topics/Tags**
   - wordpress-plugin
   - chatbot
   - claude-ai
   - grateful-dead

## Future Workflow Ideas

- Automated plugin ZIP creation on release tags
- PHP linting on pull requests
- WordPress coding standards check
