# ScubaGPT Safety Guardrails - Quick Reference

## 🎯 What It Does

Prevents the ScubaGPT plugin from ever crashing your WordPress site, no matter what goes wrong.

---

## 🛡️ 5 Protection Layers

| Layer | What It Protects Against | Result |
|-------|-------------------------|--------|
| **1. Pre-Installation** | Missing files, corrupted files | Clean error, plugin won't activate |
| **2. Safe Activation** | Wrong PHP version, database errors | Clear message, automatic deactivation |
| **3. Graceful Degradation** | Partial failures | Plugin stops safely, shows instructions |
| **4. Auto-Recovery** | Missing tables, minor issues | Silent automatic repair |
| **5. Emergency Shutdown** | Fatal errors (3x) | Auto-disable, prevent site crash |

---

## 🚨 Error Types You Might See

### 1. "Failed to Load"
**Cause:** Missing or corrupted plugin files  
**Solution:** Re-upload complete plugin package  
**Site Impact:** None - site works normally

### 2. "Activation Failed"
**Cause:** PHP version, WordPress version, or missing extensions  
**Solution:** Check requirements notice, upgrade if needed  
**Site Impact:** None - site works normally

### 3. "Emergency Shutdown"
**Cause:** Plugin had 3 fatal errors  
**Solution:** Click "Clear Errors and Reset", then reinstall  
**Site Impact:** None - plugin auto-disabled itself

### 4. "Health Check Warning"
**Cause:** Auto-repair couldn't fix an issue  
**Solution:** Follow instructions in notice  
**Site Impact:** Minimal - plugin may have reduced functionality

---

## ⚡ Quick Recovery Steps

### If Plugin Won't Activate

1. Read the error message carefully
2. Check system requirements:
   - PHP 8.0 or higher
   - WordPress 6.0 or higher
   - Required extensions: curl, json, mbstring, mysqli
3. Re-upload complete plugin package if files missing
4. Try activating again

### If Emergency Shutdown Triggered

1. Note the error details shown
2. Click "Clear Errors and Reset" button
3. Delete plugin folder
4. Upload fresh plugin package
5. Activate again

### If Health Warning Appears

1. Read list of issues
2. Dismiss notice if issues are warnings only
3. For critical issues, reinstall plugin
4. Check WordPress error logs for details

---

## 🔧 Admin Tools

### Clear Errors Button
**Location:** Emergency Shutdown notice  
**What It Does:** Resets all error counters and stored errors  
**When To Use:** Before attempting to reactivate after emergency shutdown

### Dismiss Button
**Location:** Most warning notices  
**What It Does:** Hides the notice  
**When To Use:** After reading and noting the information

---

## 📊 System Requirements

| Requirement | Minimum | Checked At |
|------------|---------|------------|
| PHP Version | 8.0 | Activation |
| WordPress Version | 6.0 | Activation |
| PHP Extensions | curl, json, mbstring, mysqli | Activation |
| Memory Limit | 64MB | Activation |
| Upload Directory | Writable | Activation |
| Critical Files | All present | Activation + Hourly |
| Database Tables | All created | Activation + Hourly |

---

## 🔍 Behind The Scenes

### What Runs Automatically

**On Activation:**
- ✅ System requirements check
- ✅ File validation
- ✅ Database table creation
- ✅ Table verification

**Every Hour (while plugin active):**
- ✅ Critical files check
- ✅ Database tables check
- ✅ Auto-repair if issues found

**On Every Page Load:**
- ✅ Fatal error detection (only if error occurs)

**On Plugin Deactivation:**
- ✅ Error counter reset

---

## 📝 Options Stored

The plugin stores error information in WordPress options:

- `scubagpt_activation_status` - Current status
- `scubagpt_error_count` - Fatal error counter
- `scubagpt_fatal_error` - Last fatal error details
- `scubagpt_emergency_shutdown` - Shutdown reason
- `scubagpt_health_issues` - Current health problems

**Cleared by:** "Clear Errors and Reset" button

---

## ⚠️ What To Do If...

### Site Shows White Screen
**This should never happen with safety guardrails**, but if it does:
1. Access site via FTP/SSH
2. Rename plugin folder: `scubagpt-chatbot` → `scubagpt-chatbot-disabled`
3. Site will immediately recover
4. Check error logs
5. Report issue with log details

### Plugin Keeps Deactivating
**Cause:** Hitting 3 fatal errors repeatedly  
**Solution:**
1. Check error logs for actual error
2. Fix underlying issue (usually hosting/environment)
3. Clear errors
4. Try clean install

### Can't Clear Errors
**Rare, but possible if options won't save:**
1. Via WP-CLI:
   ```bash
   wp option delete scubagpt_activation_error
   wp option delete scubagpt_emergency_shutdown
   wp option delete scubagpt_fatal_error
   wp option delete scubagpt_error_count
   ```
2. Or via phpMyAdmin:
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE 'scubagpt_%error%';
   ```

---

## 📖 Full Documentation

**Detailed Guide:** `PLUGIN-SAFETY-GUARDRAILS.md`  
**Implementation Summary:** `SAFETY-GUARDRAILS-IMPLEMENTATION-SUMMARY.md`  
**This File:** `SAFETY-GUARDRAILS-QUICK-REFERENCE.md`

---

## 💡 Key Takeaways

1. ✅ **Your site is protected** - Plugin cannot crash your WordPress site
2. ✅ **Clear error messages** - You'll always know what went wrong
3. ✅ **Easy recovery** - One-click tools to fix most issues
4. ✅ **Automatic repairs** - Many issues fix themselves
5. ✅ **No technical knowledge required** - Step-by-step instructions provided

---

## 🆘 Need Help?

1. **Check error logs** at `/wp-content/debug.log`
2. **Read the error message** carefully - it contains specific instructions
3. **Try the suggested solution** first
4. **Check full documentation** in `PLUGIN-SAFETY-GUARDRAILS.md`
5. **Contact support** with error log details if issue persists

---

**Version:** 1.1.0  
**Last Updated:** January 7, 2026  
**Protection Level:** Maximum
