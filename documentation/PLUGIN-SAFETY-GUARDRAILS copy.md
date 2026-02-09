# ScubaGPT Plugin Safety Guardrails

## 🎯 GOAL

Implement multiple layers of protection so the ScubaGPT plugin installation can **never crash the entire WordPress site**.

---

## 🛡️ MULTI-LAYER SAFETY SYSTEM

The ScubaGPT plugin implements a comprehensive 5-layer safety system to prevent site crashes and provide graceful error handling:

### Layer 1: Pre-Installation Validation ✅
### Layer 2: Safe Loading with Error Handling ✅
### Layer 3: Graceful Degradation ✅
### Layer 4: Automatic Recovery ✅
### Layer 5: Emergency Shutdown System ✅

---

## LAYER 1: PRE-INSTALLATION VALIDATION

### Safe File Loader Class

The `ScubaGPT_Safe_Loader` class validates all file operations before loading them:

**Location:** `scubagpt-chatbot.php` (lines 27-89)

**Features:**
- Validates file existence before requiring
- Catches and logs load errors
- Tracks missing files and errors separately
- Provides validation status checks

**Methods:**
- `require_file($file_path, $file_description)` - Safely require a file
- `get_missing_files()` - Get list of missing files
- `get_load_errors()` - Get list of load errors
- `can_activate()` - Check if plugin can safely activate

### Safe File Loading Implementation

All plugin dependencies are loaded through the safe loader:

```php
private function includes() {
    $loader = 'ScubaGPT_Safe_Loader';
    
    // Define all required files
    $files = array(
        array(SCUBAGPT_PLUGIN_DIR . 'includes/class-scubagpt-admin.php', 'Admin handler', true),
        array(SCUBAGPT_PLUGIN_DIR . 'includes/class-scubagpt-api.php', 'API handler', true),
        array(SCUBAGPT_PLUGIN_DIR . 'includes/class-scubagpt-pinecone-api.php', 'Pinecone API', false),
        array(SCUBAGPT_PLUGIN_DIR . 'includes/class-scubagpt-aipower-integration.php', 'AI Power integration', false),
        array(SCUBAGPT_PLUGIN_DIR . 'includes/class-scubagpt-chat.php', 'Chat handler', true),
        array(SCUBAGPT_PLUGIN_DIR . 'includes/class-scubagpt-rest.php', 'REST API', true),
    );
    
    $critical_failed = false;
    
    foreach ($files as $file) {
        list($path, $description, $critical) = $file;
        $loaded = $loader::require_file($path, $description);
        
        if (!$loaded && $critical) {
            $critical_failed = true;
        }
    }
    
    return !$critical_failed;
}
```

**Critical Files:** Must load successfully for plugin to function
**Optional Files:** Can fail without preventing plugin operation

---

## LAYER 2: SAFE ACTIVATION WITH ERROR HANDLING

### Activation Hook with Try-Catch

The activation process is wrapped in comprehensive error handling:

**Location:** `scubagpt-chatbot.php` (lines 293-360)

**Features:**
- Try-catch wrapper around entire activation
- System requirements validation
- Database table creation verification
- Detailed error logging
- Automatic plugin deactivation on failure
- User-friendly error messages

### System Requirements Check

**Location:** `scubagpt-chatbot.php` (lines 408-454)

**Validated Requirements:**

1. **PHP Version:** 8.0 or higher
2. **WordPress Version:** 6.0 or higher
3. **PHP Extensions:** curl, json, mbstring, mysqli
4. **Memory Limit:** Minimum 64MB
5. **Write Permissions:** Upload directory must be writable

**Error Storage:**
All requirement errors are stored in `scubagpt_requirement_errors` option for display to admin.

### Database Table Verification

**Location:** `scubagpt-chatbot.php` (lines 361-407)

After creating tables with `dbDelta()`, the plugin verifies each table was successfully created:

```php
// Verify tables were created
$tables_exist = 
    $wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") === $conversations_table &&
    $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") === $messages_table &&
    $wpdb->get_var("SHOW TABLES LIKE '$query_stats_table'") === $query_stats_table;

if (!$tables_exist) {
    error_log('ScubaGPT: Failed to create one or more database tables');
    return false;
}
```

**Database Tables:**
- `scubagpt_conversations` - Chat conversation storage
- `scubagpt_messages` - Individual message storage
- `scubagpt_query_stats` - Query statistics and AI Power usage

---

## LAYER 3: GRACEFUL DEGRADATION

### Activation Status Check

The plugin checks its activation status before initialization:

```php
public function init() {
    // Check activation status
    $status = get_option('scubagpt_activation_status');
    
    if ($status === 'failed') {
        // Only load admin notice functionality
        add_action('admin_notices', array($this, 'show_activation_error'));
        return;
    }
    
    // Check for emergency shutdown
    $shutdown = get_option('scubagpt_emergency_shutdown');
    if ($shutdown) {
        add_action('admin_notices', array($this, 'show_emergency_shutdown_notice'));
        return;
    }
    
    // Normal initialization continues...
}
```

### Admin Notice System

**Five types of admin notices:**

#### 1. Load Error Notice
Shown when required files cannot be loaded.

**Location:** `scubagpt-chatbot.php` (lines 530-571)

**Displays:**
- List of missing files
- List of load errors
- Step-by-step resolution instructions

#### 2. Activation Error Notice
Shown when activation fails.

**Location:** `scubagpt-chatbot.php` (lines 573-631)

**Displays:**
- Error message
- Missing required files
- System requirement failures
- Recovery instructions

#### 3. Emergency Shutdown Notice
Shown when plugin auto-disables after repeated fatal errors.

**Location:** `scubagpt-chatbot.php` (lines 633-679)

**Displays:**
- Shutdown reason
- Last fatal error details
- Recovery instructions
- "Clear Errors and Reset" button

#### 4. Health Check Warning Notice
Shown when automated repairs fail.

**Location:** `scubagpt-chatbot.php` (lines 789-825)

**Displays:**
- List of issues that couldn't be repaired
- Recommended actions

#### 5. Errors Cleared Success Notice
Shown after administrator clears all errors.

**Location:** `scubagpt-chatbot.php` (lines 873-881)

**Displays:**
- Confirmation message
- Next steps

---

## LAYER 4: AUTOMATIC RECOVERY

### Health Check System

**Location:** `scubagpt-chatbot.php` (lines 681-747)

**Features:**
- Runs once per hour (via transient)
- Checks critical files exist
- Verifies database tables exist
- Validates API keys (warning level)
- Stores detected issues

**Triggered:** `admin_init` hook (line 113)

**Check Frequency:** Once per hour (cached in transient)

### Automatic Repair System

**Location:** `scubagpt-chatbot.php` (lines 749-787)

When critical issues are detected, the plugin attempts automatic repair:

**Repairable Issues:**
- Missing database tables (recreated via `create_tables()`)

**Non-Repairable Issues:**
- Missing files (requires manual plugin reinstall)

**Repair Results:**
- Logged to `scubagpt_last_repair_attempt` option
- Failed repairs trigger admin notice
- Success logged silently

---

## LAYER 5: EMERGENCY SHUTDOWN SYSTEM

### Fatal Error Handler

**Location:** `scubagpt-chatbot.php` (lines 487-528)

**How It Works:**

1. **Shutdown Handler Registration**
   ```php
   register_shutdown_function(array($this, 'handle_shutdown'));
   ```

2. **Fatal Error Detection**
   - Checks `error_get_last()` on shutdown
   - Validates error is from plugin directory
   - Filters for fatal error types: E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR

3. **Error Counter**
   - Increments `scubagpt_error_count` option
   - Stores error details in `scubagpt_fatal_error` option

4. **Automatic Deactivation**
   - After 3 fatal errors, plugin auto-deactivates
   - Stores shutdown reason in `scubagpt_emergency_shutdown` option
   - Logs emergency shutdown to error log

### Error Counter Reset

The error counter is automatically reset when:
- Administrator manually deactivates the plugin
- Administrator clicks "Clear Errors and Reset"

---

## ADMIN ACTIONS

### Clear Errors Action

**URL:** `admin.php?action=scubagpt_clear_errors&_wpnonce=...`

**Location:** `scubagpt-chatbot.php` (lines 845-870)

**Clears:**
- `scubagpt_activation_error`
- `scubagpt_emergency_shutdown`
- `scubagpt_fatal_error`
- `scubagpt_error_count`
- `scubagpt_requirement_errors`
- `scubagpt_health_issues`
- `scubagpt_repair_failed`
- `scubagpt_last_repair_attempt`
- `scubagpt_last_health_check` transient

**Resets:** `scubagpt_activation_status` to 'reset'

**Requires:** `activate_plugins` capability

---

## OPTIONS REFERENCE

### Status Options

| Option Name | Values | Purpose |
|------------|--------|---------|
| `scubagpt_activation_status` | success, failed, reset | Current activation state |
| `scubagpt_activation_time` | MySQL timestamp | When activation succeeded |
| `scubagpt_error_count` | Integer (0-3) | Fatal error counter |

### Error Storage Options

| Option Name | Type | Purpose |
|------------|------|---------|
| `scubagpt_activation_error` | Array | Last activation error details |
| `scubagpt_fatal_error` | Array | Last fatal error details |
| `scubagpt_emergency_shutdown` | Array | Emergency shutdown details |
| `scubagpt_requirement_errors` | Array | System requirement failures |
| `scubagpt_health_issues` | Array | Detected health check issues |
| `scubagpt_repair_failed` | Boolean | Auto-repair failure flag |
| `scubagpt_last_repair_attempt` | Array | Last repair attempt details |

### Transients

| Transient Name | Duration | Purpose |
|---------------|----------|---------|
| `scubagpt_last_health_check` | 1 hour | Prevents excessive health checks |

---

## ERROR FLOW DIAGRAMS

### Activation Flow

```
Plugin Activation Triggered
         ↓
   Check Requirements
         ↓
    ┌────┴────┐
    ↓         ↓
  PASS      FAIL → Store Error → Deactivate → Show Error Notice
    ↓
Load Dependencies
    ↓
 ┌───┴────┐
 ↓        ↓
PASS     FAIL → Store Error → Deactivate → Show Error Notice
 ↓
Create Tables
 ↓
┌──┴───┐
↓      ↓
PASS  FAIL → Store Error → Deactivate → Show Error Notice
↓
Set Defaults
↓
Flush Rules
↓
Mark Success
↓
Plugin Active
```

### Fatal Error Flow

```
Fatal Error Occurs
       ↓
Shutdown Handler
       ↓
Check if from Plugin Directory
       ↓
   ┌───┴────┐
   ↓        ↓
  YES       NO → Ignore
   ↓
Store Error Details
   ↓
Increment Error Counter
   ↓
Check Counter
   ↓
┌──────┴───────┐
↓              ↓
< 3 Errors   ≥ 3 Errors
   ↓              ↓
Continue     Auto-Deactivate
   ↓              ↓
Wait for      Emergency Shutdown
Next Error    Notice Shown
```

### Health Check Flow

```
Admin Page Load (every hour)
          ↓
    Check Transient
          ↓
      ┌───┴────┐
      ↓        ↓
   Cached   Expired
      ↓        ↓
    Skip   Run Check
              ↓
        Check Files
              ↓
       Check Tables
              ↓
        Check APIs
              ↓
     Any Issues?
          ↓
      ┌───┴────┐
      ↓        ↓
    YES       NO
      ↓        ↓
  Attempt   Clear
   Repair    Issues
      ↓
  Critical?
      ↓
  ┌───┴────┐
  ↓        ↓
YES       NO
  ↓        ↓
Show    Log Only
Notice
```

---

## TESTING THE GUARDRAILS

### Test 1: Missing Critical File

**Steps:**
1. Rename `includes/class-scubagpt-chat.php` to `class-scubagpt-chat.php.bak`
2. Try to activate plugin

**Expected Result:**
- Plugin fails to activate
- Clear error message shown
- Missing file listed: `class-scubagpt-chat.php`
- Site remains fully functional
- Plugin automatically deactivated

**Verification:**
```bash
# Check error log
tail -f /path/to/error.log | grep ScubaGPT
```

### Test 2: PHP Version Requirement

**Steps:**
1. Modify `check_requirements()` to require PHP 9.0
2. Try to activate plugin

**Expected Result:**
- Activation fails with clear message
- Current PHP version displayed
- Required version displayed
- Site remains functional

### Test 3: Database Table Creation Failure

**Steps:**
1. Temporarily revoke CREATE TABLE permissions for WordPress database user
2. Try to activate plugin

**Expected Result:**
- Activation fails
- Database error message shown
- Plugin deactivated
- No partial tables left

**Cleanup:**
```sql
-- After test, check for any orphaned tables
SHOW TABLES LIKE 'wp_scubagpt_%';
-- Drop any found manually
```

### Test 4: Fatal Error Recovery

**Steps:**
1. Introduce intentional fatal error in plugin code
2. Access any WordPress page
3. Repeat 3 times

**Expected Result:**
- First error: Logged, plugin continues
- Second error: Logged, plugin continues
- Third error: Plugin auto-deactivates
- Emergency shutdown notice shown
- Site remains accessible

**Introduce Test Error:**
```php
// Add to init() method temporarily
throw new Exception('Test fatal error');
```

### Test 5: Missing Database Table Recovery

**Steps:**
1. Activate plugin successfully
2. Manually drop a database table
3. Trigger health check by clearing transient
4. Visit admin page

**Expected Result:**
- Health check detects missing table
- Auto-repair recreates table
- No error notice shown (silent recovery)
- Plugin continues functioning

**SQL:**
```sql
-- Drop table
DROP TABLE wp_scubagpt_conversations;
```

**Trigger Health Check:**
```php
delete_transient('scubagpt_last_health_check');
```

### Test 6: Clear Errors Function

**Steps:**
1. Cause an activation error
2. Note the error displayed
3. Click "Clear Errors and Reset" button
4. Try activating again

**Expected Result:**
- All error options cleared
- Success message shown
- Can attempt activation again
- No old errors displayed

---

## RECOVERY PROCEDURES

### Scenario 1: Plugin Won't Activate

**Symptoms:**
- Activation fails immediately
- Error message displayed
- Plugin automatically deactivated

**Solutions:**

1. **Check Requirements**
   - Verify PHP version: `php -v`
   - Verify WordPress version
   - Check required extensions: `php -m`

2. **Verify Complete Upload**
   - Check all files present in `/wp-content/plugins/scubagpt-chatbot/`
   - Re-upload complete plugin package if files missing

3. **Check Permissions**
   - Upload directory must be writable
   - Plugin directory must be readable

4. **Clear Errors**
   - Use "Clear Errors and Reset" button
   - Try activating again

### Scenario 2: Emergency Shutdown Triggered

**Symptoms:**
- Plugin automatically deactivated
- "Emergency Shutdown" notice shown
- Error counter at 3

**Solutions:**

1. **Check Error Logs**
   ```bash
   grep "ScubaGPT Fatal Error" /path/to/error.log
   ```

2. **Review Last Error**
   - Check emergency shutdown notice for details
   - Note file and line number

3. **Reinstall Plugin**
   - Delete plugin folder
   - Upload fresh copy
   - Do not activate yet

4. **Clear All Errors**
   - Click "Clear Errors and Reset"
   - Verify errors cleared
   - Try activating again

### Scenario 3: Health Check Failures

**Symptoms:**
- Warning notice about failed repairs
- List of issues that couldn't be fixed
- Plugin still active but degraded

**Solutions:**

1. **Missing Files**
   - Cannot be auto-repaired
   - Must reinstall complete plugin package

2. **Missing Tables**
   - Usually auto-repaired
   - If repair fails, manually recreate:
   ```sql
   -- Run create_tables SQL manually
   ```

3. **Missing API Keys**
   - Warning only, not critical
   - Configure in ScubaGPT > Settings

---

## DEVELOPER NOTES

### Adding New Critical Files

When adding new critical files to the plugin:

1. **Add to includes() method**
   ```php
   array(SCUBAGPT_PLUGIN_DIR . 'includes/class-new-file.php', 'Description', true),
   ```

2. **Add to health check**
   ```php
   array(SCUBAGPT_PLUGIN_DIR . 'includes/class-new-file.php', 'Description'),
   ```

3. **Mark as critical** (third parameter = true) only if required for basic functionality

### Adding New Database Tables

When adding new tables:

1. **Add to create_tables() SQL**
2. **Add to table verification**
3. **Add to health check required_tables array**
4. **Update documentation**

### Custom Error Types

To add custom error types to the safety system:

1. **Store error details** using options API
2. **Add admin notice method**
3. **Add to clear_errors action**
4. **Document in this file**

### Extending Health Checks

To add new health checks:

1. **Add check in perform_health_check()**
2. **Define severity** (critical or warning)
3. **Add repair logic** in attempt_auto_repair() if applicable
4. **Create admin notice** if needed

---

## IMPACT ANALYSIS

### Before Safety Guardrails

**Risks:**
- Missing file → Fatal error → White screen of death
- Database error → Site crash
- PHP version mismatch → Site crash
- Fatal error → Site permanently broken
- No recovery mechanism

### After Safety Guardrails

**Protection:**
- ✅ Missing file → Clean error → Plugin deactivates → Site functional
- ✅ Database error → Clear message → Site functional
- ✅ PHP version mismatch → Requirements notice → Site functional
- ✅ Fatal error → Auto-recovery or shutdown → Site functional
- ✅ Health checks → Automatic repair → Silent recovery

### User Experience

**Before:**
- Technical error messages
- Site crashes possible
- Manual recovery required
- SSH/FTP access needed

**After:**
- Clear, actionable error messages
- Site never crashes
- Automatic recovery attempts
- Admin panel recovery tools
- No technical knowledge required

---

## VERSION HISTORY

| Version | Date | Changes |
|---------|------|---------|
| 1.1.0 | January 2026 | Initial implementation of all 5 safety layers |

---

## SUPPORT RESOURCES

### Error Log Locations

**Common Locations:**
- `/wp-content/debug.log` (if WP_DEBUG_LOG enabled)
- `/var/log/apache2/error.log` (Apache)
- `/var/log/nginx/error.log` (Nginx)
- Hosting control panel error logs

### Enable WordPress Debug Logging

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Database Access

If you need to manually check or repair database:

```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_scubagpt_%';

-- Check table structure
DESCRIBE wp_scubagpt_conversations;

-- Check for data
SELECT COUNT(*) FROM wp_scubagpt_conversations;

-- Drop and recreate if needed (will lose data)
DROP TABLE IF EXISTS wp_scubagpt_conversations;
-- Then reactivate plugin
```

### Getting Help

If safety guardrails aren't resolving your issue:

1. **Collect Information:**
   - Error messages from admin notices
   - Error log excerpts
   - PHP/WordPress versions
   - List of active plugins

2. **Check Options:**
   ```php
   // In WP-CLI or admin page
   get_option('scubagpt_activation_error');
   get_option('scubagpt_fatal_error');
   get_option('scubagpt_emergency_shutdown');
   ```

3. **Contact Support:**
   - Include all collected information
   - Describe steps that led to error
   - Note any customizations

---

**Last Updated:** January 7, 2026  
**Plugin Version:** 1.1.0  
**Status:** ✅ All 5 Safety Layers Implemented  
**Site Protection:** Maximum
