# How to Get the Debug Log

The plugin is creating fatal errors. To diagnose the issue, I need to see the error log.

## Option 1: Via FTP/File Manager
1. Connect to your WordPress site via FTP or cPanel File Manager
2. Navigate to: `/wp-content/uploads/debug-log-manager/`
3. Find the most recent `.log` file
4. Download it and share the contents

## Option 2: Via WordPress Admin (if you have a debug log plugin)
1. Go to WordPress Admin
2. Look for "Debug Log Manager" or similar in the menu
3. View the most recent log
4. Copy the error messages

## Option 3: Check Standard WordPress Debug Log
If the debug-log-manager folder doesn't exist, check:
- `/wp-content/debug.log`

## What I'm Looking For

The error will likely look something like:

```
[09-Jan-2026 12:34:56 UTC] PHP Fatal error: ...
[09-Jan-2026 12:34:56 UTC] GD Chatbot Debug: ...
```

Please copy the entire error message including:
- The timestamp
- The error type (Fatal error, Warning, etc.)
- The file path and line number
- Any stack trace
- Any "GD Chatbot Debug" messages

This will help me identify exactly what's failing during plugin activation.
