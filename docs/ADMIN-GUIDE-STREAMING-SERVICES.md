# Admin Guide: Configuring Streaming Services

**GD Chatbot v2.2.0+**

---

## Overview

As an administrator, you need to configure API credentials for each streaming service before users can connect their accounts. This guide walks you through the setup process for all 5 supported services.

---

## Prerequisites

- WordPress admin account with `manage_options` capability
- Developer accounts on the streaming services you want to enable
- Basic understanding of OAuth 2.0 (helpful but not required)

---

## Quick Start

1. Go to **GD Chatbot v2 → Settings → Streaming Services**
2. View the service status cards at the top
3. Click "Configure" for each service you want to enable
4. Enter API credentials
5. Click "Test Connection" to verify
6. Click "Save All Configurations"
7. Users can now connect their accounts!

---

## Service-by-Service Setup

### 🎵 Spotify

**1. Create a Spotify App**
- Go to [Spotify Developer Dashboard](https://developer.spotify.com/dashboard)
- Log in with your Spotify account
- Click "Create app"
- Fill in:
  - **App name:** GD Chatbot Music Integration
  - **App description:** WordPress chatbot music streaming
  - **Redirect URI:** (copy from settings page)
  - **APIs used:** Web API
- Click "Save"

**2. Get Credentials**
- Click on your new app
- Copy the **Client ID**
- Click "Show Client Secret"
- Copy the **Client Secret**

**3. Configure in WordPress**
- Go to **GD Chatbot v2 → Settings → Streaming Services**
- Scroll to "Spotify" section
- Paste Client ID and Client Secret
- Copy the Redirect URI shown
- Go back to Spotify Developer Dashboard
- Click "Edit Settings"
- Add the Redirect URI
- Click "Save"

**4. Test**
- Click "Test Connection" in WordPress
- Should show "✅ Configuration is valid!"

---

### 🍎 Apple Music

**Note:** Apple Music uses a different authentication method (JWT Developer Tokens).

**1. Enroll in Apple Developer Program**
- Go to [Apple Developer](https://developer.apple.com/)
- Enroll in the program ($99/year)
- Complete enrollment process

**2. Create a MusicKit Key**
- Go to [Certificates, Identifiers & Profiles](https://developer.apple.com/account/resources/authkeys/list)
- Click the "+" button
- Check "MusicKit"
- Enter a key name (e.g., "GD Chatbot MusicKit")
- Click "Continue" and "Register"
- Download the .p8 key file (you can only download once!)

**3. Generate Developer Token**
- Use Apple's token generator or create your own JWT
- Token format: Header + Payload + Signature
- Token is valid for 6 months
- See [Apple MusicKit Documentation](https://developer.apple.com/documentation/applemusicapi/generating_developer_tokens)

**4. Configure in WordPress**
- Go to **GD Chatbot v2 → Settings → Streaming Services**
- Scroll to "Apple Music" section
- Enter:
  - **Team ID:** Found in Apple Developer account
  - **Key ID:** From the MusicKit key you created
  - **Developer Token:** The JWT token you generated
- Click "Test Connection"

---

### 📺 YouTube Music

**1. Create a Google Cloud Project**
- Go to [Google Cloud Console](https://console.cloud.google.com/)
- Create a new project or select existing
- Name: "GD Chatbot Music Integration"

**2. Enable YouTube Data API v3**
- In the project, go to "APIs & Services → Library"
- Search for "YouTube Data API v3"
- Click "Enable"

**3. Create OAuth 2.0 Credentials**
- Go to "APIs & Services → Credentials"
- Click "Create Credentials → OAuth 2.0 Client ID"
- Configure consent screen if prompted:
  - User Type: External
  - App name: GD Chatbot
  - Support email: Your email
  - Scopes: Add YouTube readonly scope
- Choose "Web application"
- Add Redirect URI (copy from WordPress settings)
- Click "Create"

**4. Get Credentials**
- Copy the **Client ID**
- Copy the **Client Secret**

**5. Configure in WordPress**
- Go to **GD Chatbot v2 → Settings → Streaming Services**
- Scroll to "YouTube Music" section
- Paste Client ID and Client Secret
- Copy the Redirect URI
- Add it to Google Cloud Console (if not already added)
- Click "Test Connection"

---

### 📦 Amazon Music

**1. Create an Amazon Developer Account**
- Go to [Amazon Developer Console](https://developer.amazon.com/)
- Sign in or create account
- Complete developer profile

**2. Create a Security Profile**
- Go to "Login with Amazon → Security Profiles"
- Click "Create a New Security Profile"
- Enter:
  - **Security Profile Name:** GD Chatbot
  - **Security Profile Description:** WordPress music chatbot
  - **Consent Privacy Notice URL:** Your site's privacy policy
- Click "Save"

**3. Configure Security Profile**
- Click "Show Client ID and Client Secret"
- Copy both values
- Click "Web Settings"
- Add Allowed Return URLs (copy from WordPress settings)
- Click "Save"

**4. Configure in WordPress**
- Go to **GD Chatbot v2 → Settings → Streaming Services**
- Scroll to "Amazon Music" section
- Paste Client ID and Client Secret
- Click "Test Connection"

---

### 🌊 Tidal

**1. Apply for Tidal Developer Access**
- Go to [Tidal Developer Portal](https://developer.tidal.com/)
- Apply for API access
- Wait for approval (can take several days)

**2. Create an Application**
- Once approved, log in to developer portal
- Create a new application
- Enter:
  - **App name:** GD Chatbot
  - **Description:** WordPress music chatbot
  - **Redirect URI:** (copy from WordPress settings)

**3. Get Credentials**
- Copy the **Client ID**
- Copy the **Client Secret**

**4. Configure in WordPress**
- Go to **GD Chatbot v2 → Settings → Streaming Services**
- Scroll to "Tidal" section
- Paste Client ID and Client Secret
- Click "Test Connection"

---

## Configuration Checklist

Use this checklist to ensure each service is properly configured:

### Spotify
- [ ] Created Spotify Developer app
- [ ] Copied Client ID and Client Secret
- [ ] Added Redirect URI to Spotify app settings
- [ ] Entered credentials in WordPress
- [ ] Tested connection successfully
- [ ] Saved configuration

### Apple Music
- [ ] Enrolled in Apple Developer Program
- [ ] Created MusicKit key
- [ ] Generated developer token (JWT)
- [ ] Entered Team ID, Key ID, and token in WordPress
- [ ] Tested connection successfully
- [ ] Saved configuration

### YouTube Music
- [ ] Created Google Cloud project
- [ ] Enabled YouTube Data API v3
- [ ] Created OAuth 2.0 credentials
- [ ] Added Redirect URI to Google Cloud
- [ ] Entered credentials in WordPress
- [ ] Tested connection successfully
- [ ] Saved configuration

### Amazon Music
- [ ] Created Amazon Developer account
- [ ] Created Security Profile
- [ ] Copied Client ID and Client Secret
- [ ] Added Redirect URI to security profile
- [ ] Entered credentials in WordPress
- [ ] Tested connection successfully
- [ ] Saved configuration

### Tidal
- [ ] Applied for Tidal developer access
- [ ] Got approved
- [ ] Created Tidal application
- [ ] Copied Client ID and Client Secret
- [ ] Added Redirect URI to Tidal app
- [ ] Entered credentials in WordPress
- [ ] Tested connection successfully
- [ ] Saved configuration

---

## Monitoring Service Usage

### View Statistics

Go to **GD Chatbot v2 → Settings → Streaming Services** to see:
- Which services are configured
- How many users have connected each service
- Configuration status for each service

### Common Metrics

- **Connected Users:** Number of users who have authorized the service
- **Configuration Status:** Whether API credentials are set up
- **Service Availability:** Whether the service is ready for users

---

## Security Best Practices

### Protect Your Credentials

1. **Never share Client Secrets** - Keep them private
2. **Use environment variables** - Store in wp-config.php for production
3. **Rotate credentials periodically** - Update every 6-12 months
4. **Monitor usage** - Check for unusual activity
5. **Restrict access** - Only give admin access to trusted users

### Recommended wp-config.php Setup

For production sites, store credentials in `wp-config.php`:

```php
// Spotify
define('GD_SPOTIFY_CLIENT_ID', 'your_client_id_here');
define('GD_SPOTIFY_CLIENT_SECRET', 'your_client_secret_here');

// Apple Music
define('GD_APPLE_MUSIC_TEAM_ID', 'your_team_id_here');
define('GD_APPLE_MUSIC_KEY_ID', 'your_key_id_here');
define('GD_APPLE_MUSIC_TOKEN', 'your_jwt_token_here');

// YouTube Music
define('GD_YOUTUBE_CLIENT_ID', 'your_client_id_here');
define('GD_YOUTUBE_CLIENT_SECRET', 'your_client_secret_here');

// Amazon Music
define('GD_AMAZON_CLIENT_ID', 'your_client_id_here');
define('GD_AMAZON_CLIENT_SECRET', 'your_client_secret_here');

// Tidal
define('GD_TIDAL_CLIENT_ID', 'your_client_id_here');
define('GD_TIDAL_CLIENT_SECRET', 'your_client_secret_here');
```

Then modify the OAuth classes to check for constants first before checking options.

---

## Troubleshooting Configuration Issues

### Test Connection Fails

**Problem:** "Test Connection" shows an error.

**Solutions:**
1. Double-check Client ID and Client Secret (no extra spaces)
2. Verify Redirect URI is added to service's app settings
3. Check that the service's API is enabled
4. Ensure your server can make outbound HTTPS requests
5. Check PHP error logs for detailed error messages

### Users Can't Connect

**Problem:** Users see "Not configured by administrator".

**Solutions:**
1. Verify you saved the configuration
2. Check that credentials are entered correctly
3. Test the connection yourself
4. Clear WordPress cache (if using caching plugin)

### OAuth Redirect Fails

**Problem:** After authorization, users see an error page.

**Solutions:**
1. Verify Redirect URI exactly matches (including https://)
2. Check that WordPress permalinks are working
3. Ensure AJAX endpoint is accessible
4. Check for conflicting plugins

### Token Refresh Fails

**Problem:** Users' tokens expire and can't refresh.

**Solutions:**
1. Verify refresh tokens are being stored
2. Check that service supports refresh tokens
3. Ensure credentials haven't been revoked on service side
4. Ask users to reconnect

---

## API Rate Limits

Each service has rate limits. Monitor usage to avoid hitting limits:

### Spotify
- **Rate Limit:** 180 requests per minute per user
- **Recommendation:** Implement caching (already done)

### Apple Music
- **Rate Limit:** 20 requests per second
- **Recommendation:** Use transient caching

### YouTube Music
- **Rate Limit:** 10,000 units per day (search = 100 units)
- **Recommendation:** Cache searches aggressively

### Amazon Music
- **Rate Limit:** Varies by endpoint
- **Recommendation:** Monitor usage in developer console

### Tidal
- **Rate Limit:** 300 requests per minute
- **Recommendation:** Use caching for search results

---

## Maintenance

### Regular Tasks

**Monthly:**
- Check service status cards
- Review connected user counts
- Verify test connections still work
- Update Apple Music token (if expiring soon)

**Quarterly:**
- Review API usage and rate limits
- Check for service API updates
- Update documentation if needed

**Annually:**
- Rotate credentials for security
- Renew Apple Developer Program membership
- Review and optimize caching strategies

---

## Advanced Configuration

### Custom Scopes

If you need additional permissions, modify the OAuth handler classes:

```php
// Example: Add playlist access to Spotify
// In class-spotify-oauth.php:
$this->scopes = array(
    'user-read-private',
    'user-read-email',
    'playlist-read-private' // Add this
);
```

### Custom Search Parameters

Modify search methods in OAuth handlers to customize results:

```php
// Example: Increase result limit
$params = array(
    'q' => $query,
    'type' => 'track',
    'limit' => 50 // Increase from 20
);
```

---

## Getting Help

### Documentation
- User guide: `USER-GUIDE-STREAMING-SERVICES.md`
- Technical docs: `PHASE-4-COMPLETE.md`
- API docs: Each service's developer documentation

### Support Resources
- [Spotify Web API Docs](https://developer.spotify.com/documentation/web-api)
- [Apple MusicKit Docs](https://developer.apple.com/documentation/applemusicapi)
- [YouTube Data API Docs](https://developers.google.com/youtube/v3)
- [Amazon Music API Docs](https://developer.amazon.com/docs/login-with-amazon/documentation-overview.html)
- [Tidal API Docs](https://developer.tidal.com/)

---

**Last Updated:** February 12, 2026  
**Plugin Version:** 2.2.0  
**For Administrators Only**
