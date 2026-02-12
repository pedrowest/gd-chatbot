# WordPress Page HTML Files

These HTML files are ready to paste directly into WordPress pages in Text/HTML mode.

## Files Included

### 1. user-guide.html
**Purpose:** Comprehensive user guide for all features  
**Content:** Complete guide from basics to advanced features  
**Features:**
- Getting started guide
- Basic and advanced usage
- Music streaming features
- Connecting streaming services
- Tips & tricks
- Troubleshooting
- FAQ section
- Sample conversations

**Recommended Page:** "User Guide" or "How to Use"

---

### 2. quick-start.html
**Purpose:** Quick Start Guide for new users  
**Content:** 5-minute setup, configuration, testing, and troubleshooting  
**Features:**
- Step-by-step installation
- API configuration guide
- Archive.org sync instructions
- Streaming services setup (optional)
- Testing checklist
- Troubleshooting tips

**Recommended Page:** "Quick Start Guide" or "Getting Started"

---

### 3. accuracy-systems.html
**Purpose:** Detailed explanation of the 8-layer accuracy system  
**Content:** How the chatbot ensures accurate Grateful Dead information  
**Features:**
- Multi-layer architecture diagram
- Detailed explanation of each layer
- Statistics and metrics
- Knowledge base structure
- Disambiguation system
- Quality assurance

**Recommended Page:** "Accuracy Systems" or "How It Works"

---

### 4. version-history.html
**Purpose:** Complete version history with release notes  
**Content:** All versions from v1.7.1 to v2.2.0  
**Features:**
- Timeline of releases
- Detailed release notes for each version
- Feature comparison table
- Evolution of features
- Roadmap for future versions

**Recommended Page:** "Version History" or "Release Notes"

---

## How to Use

### Step 1: Create WordPress Page
1. Log in to WordPress admin
2. Go to **Pages → Add New**
3. Give the page a title (e.g., "Quick Start Guide")

### Step 2: Switch to Text/HTML Mode
1. Click the **three dots** (⋮) in the top right
2. Select **"Code editor"** or **"Edit as HTML"**
3. Or use the **Text** tab (in Classic Editor)

### Step 3: Paste HTML Content
1. Open the HTML file in a text editor
2. Copy **all content** (Ctrl+A, Ctrl+C)
3. Paste into the WordPress page editor
4. Click **"Publish"** or **"Update"**

### Step 4: Preview and Adjust
1. Click **"Preview"** to see the page
2. Verify formatting looks correct
3. Make any adjustments if needed

---

## Styling Notes

### Built-in CSS
All files include complete CSS styling in `<style>` tags. No external CSS required.

### Responsive Design
All pages are fully responsive and mobile-friendly.

### Color Scheme
- Primary Blue: #3b82f6
- Success Green: #10b981
- Warning Orange: #f59e0b
- Error Red: #ef4444

### Typography
Uses system fonts for fast loading:
- -apple-system (macOS/iOS)
- BlinkMacSystemFont (macOS)
- Segoe UI (Windows)
- Roboto (Android)
- Fallback: sans-serif

---

## Customization

### Changing Colors
Find and replace color codes in the `<style>` section:
- `#3b82f6` → Your primary color
- `#10b981` → Your success color
- `#f59e0b` → Your warning color

### Changing Fonts
Update the `font-family` in the main container style:
```css
font-family: 'Your Font', -apple-system, sans-serif;
```

### Adding Your Logo
Add an `<img>` tag at the top of the content:
```html
<img src="your-logo.png" alt="Logo" style="max-width: 200px; margin-bottom: 20px;">
```

### Updating Links
Replace placeholder links with your actual URLs:
- Documentation links
- Support contact info
- Website URL

---

## WordPress Compatibility

### Tested With
- WordPress 5.0+
- WordPress 6.0+
- Gutenberg Editor
- Classic Editor

### Compatible With
- All modern WordPress themes
- Page builders (Elementor, Beaver Builder, etc.)
- Custom CSS plugins

### Not Required
- No plugins needed
- No external CSS files
- No JavaScript dependencies

---

## Maintenance

### Updating Content
1. Edit the HTML file in a text editor
2. Copy the updated content
3. Paste into WordPress page (replace old content)
4. Publish/Update

### Version Updates
When releasing new versions:
1. Update `version-history.html` with new release
2. Update version badges in other files
3. Update statistics if changed

---

## Best Practices

### SEO
- Add meta descriptions in WordPress page settings
- Use descriptive page titles
- Add alt text to any images you add

### Performance
- HTML is optimized for fast loading
- No external resources required
- Minimal CSS (inline for speed)

### Accessibility
- Semantic HTML structure
- Color contrast meets WCAG standards
- Keyboard navigation supported

---

## Support

### Issues
If you encounter formatting issues:
1. Verify you're in Text/HTML mode
2. Check for WordPress theme conflicts
3. Try disabling page builder plugins temporarily
4. Clear browser cache

### Questions
Contact IT Influentials support for assistance.

---

## File Sizes

- `user-guide.html`: ~45KB
- `quick-start.html`: ~25KB
- `accuracy-systems.html`: ~30KB
- `version-history.html`: ~28KB

**Total:** ~128KB (very lightweight)

---

## License

These HTML files are part of the GD Chatbot plugin package.  
**License:** GPL-2.0+

---

**Created:** February 12, 2026  
**Version:** 2.2.0  
**For:** GD Chatbot WordPress Plugin
