# GD Chatbot - Theme Guide

## Overview

The GD Chatbot includes two complete visual themes that can be easily switched between.

---

## Available Themes

### 1. Grateful Dead Psychedelic Theme (Default in v2.0.6+)

**File:** `public/css/gd-theme.css`  
**Class:** `gd-theme-grateful-dead`

#### Visual Features
- **Steal Your Face** skull icons (☠️)
- **Dancing Bears** animated typing indicator (🐻)
- **Roses** decorative elements (🌹)
- **Lightning Bolts** for dynamic effects (⚡)
- **Psychedelic Gradients** inspired by classic GD posters

#### Color Palette
```css
--gd-red: #DC143C;           /* Roses red */
--gd-blue: #4169E1;          /* Lightning blue */
--gd-purple: #8B008B;        /* Psychedelic purple */
--gd-orange: #FF6B35;        /* Sunset orange */
--gd-yellow: #FFD700;        /* Golden sun */
--gd-green: #228B22;         /* Forest green */
```

#### Typography
- **Header**: Permanent Marker (retro poster style)
- **Body**: Concert One (bold, readable)
- **Alt**: Righteous (psychedelic flair)

#### Animations
- Pulsing glow on toggle button
- Rotating Steal Your Face skull
- Dancing bears typing indicator
- Sliding psychedelic background patterns
- Lightning bolt streaming cursor

#### Best For
- Grateful Dead fan sites
- Music-related content
- Retro/vintage aesthetics
- Bold, eye-catching designs
- Sites wanting maximum personality

---

### 2. Professional Theme (Default in v2.0.5 and earlier)

**File:** `public/css/professional-theme.css`  
**Class:** `gd-theme-professional`

#### Visual Features
- Clean, modern design
- Inspired by dead.net official website
- Subtle animations
- Professional color scheme
- Minimal decorative elements

#### Color Palette
```css
--pro-primary: #DC143C;      /* Crimson red */
--pro-dark: #1a1a1a;         /* Deep black */
--pro-light: #f8f9fa;        /* Light gray */
--pro-accent: #2c3e50;       /* Slate blue */
```

#### Typography
- System fonts for maximum compatibility
- Clean, readable sans-serif
- Professional spacing

#### Animations
- Subtle fade-ins
- Smooth transitions
- Minimal motion

#### Best For
- Professional/business sites
- Clean, modern aesthetics
- Accessibility-focused sites
- Sites preferring subtlety
- Corporate environments

---

## Switching Themes

### Method 1: Edit Code (Permanent)

**File:** `gd-chatbot/public/class-chatbot-public.php`

**Lines 47-62:**

```php
// FOR GRATEFUL DEAD THEME (Current Default):
wp_enqueue_style(
    'gd-chatbot-theme',
    GD_CHATBOT_PLUGIN_URL . 'public/css/gd-theme.css',
    array('gd-chatbot-public'),
    GD_CHATBOT_VERSION
);

// FOR PROFESSIONAL THEME:
// Uncomment these lines and comment out the above
// wp_enqueue_style(
//     'gd-chatbot-theme-professional',
//     GD_CHATBOT_PLUGIN_URL . 'public/css/professional-theme.css',
//     array('gd-chatbot-public'),
//     GD_CHATBOT_VERSION
// );
```

**Line 176:**

```php
// FOR GRATEFUL DEAD THEME:
$container_class = 'gd-chatbot-container gd-theme-grateful-dead';

// FOR PROFESSIONAL THEME:
// $container_class = 'gd-chatbot-container gd-theme-professional';
```

### Method 2: Use Specific Version

- **v2.0.6+**: Grateful Dead theme
- **v2.0.5 and earlier**: Professional theme

Just install the version with your preferred theme.

### Method 3: Custom CSS (Override)

Add custom CSS in WordPress:
1. Appearance → Customize → Additional CSS
2. Add your overrides:

```css
/* Override specific GD theme colors */
.gd-chatbot-container.gd-theme-grateful-dead {
    --gd-red: #YOUR_COLOR;
}

/* Or switch to professional theme via CSS */
.gd-chatbot-container {
    /* Add professional theme overrides */
}
```

---

## Theme Comparison

| Feature | Grateful Dead | Professional |
|---------|--------------|--------------|
| **Visual Style** | Psychedelic, bold | Clean, modern |
| **Colors** | 6+ vibrant colors | 3-4 subtle colors |
| **Animations** | Multiple, prominent | Minimal, subtle |
| **Icons** | Emojis (☠️🐻🌹⚡) | SVG icons |
| **Fonts** | Retro display fonts | System fonts |
| **Border Style** | Gradient, bold | Solid, subtle |
| **Best For** | Fan sites, bold brands | Business, professional |
| **Accessibility** | Good (with reduced motion) | Excellent |
| **File Size** | 623 lines | 450 lines |
| **Load Time** | Slightly slower (fonts) | Faster |

---

## Customization Tips

### Adjusting Colors

Both themes use CSS custom properties (variables) that can be overridden:

```css
.gd-chatbot-container {
    /* Override primary color */
    --iti-chat-primary: #YOUR_COLOR;
    
    /* Override background */
    --iti-chat-bg: #YOUR_BG;
    
    /* Override text color */
    --iti-chat-text: #YOUR_TEXT;
}
```

### Disabling Animations

For accessibility or performance:

```css
.gd-chatbot-container * {
    animation: none !important;
    transition: none !important;
}
```

Or use the built-in reduced motion support (automatically respects user preferences).

### Custom Fonts

```css
.gd-chatbot-container {
    font-family: 'Your Font', sans-serif;
}
```

### Adjusting Size

```css
.gd-chatbot-container {
    --iti-chat-width: 500px;
    --iti-chat-height: 700px;
}
```

---

## Creating a Custom Theme

### Step 1: Create CSS File

Create `public/css/my-custom-theme.css`:

```css
.gd-chatbot-container.gd-theme-custom {
    /* Your custom variables */
    --custom-primary: #YOUR_COLOR;
    
    /* Your custom styles */
}

.gd-chatbot-container.gd-theme-custom .gd-chatbot-header {
    background: var(--custom-primary);
    /* More styles */
}

/* Continue styling all components */
```

### Step 2: Enqueue CSS

Edit `public/class-chatbot-public.php`:

```php
wp_enqueue_style(
    'gd-chatbot-theme-custom',
    GD_CHATBOT_PLUGIN_URL . 'public/css/my-custom-theme.css',
    array('gd-chatbot-public'),
    GD_CHATBOT_VERSION
);
```

### Step 3: Update Container Class

```php
$container_class = 'gd-chatbot-container gd-theme-custom';
```

### Step 4: Test

Clear cache and test on multiple devices!

---

## Theme Components

Both themes style these components:

1. **Container** - Overall wrapper
2. **Header** - Title and action buttons
3. **Messages Area** - Chat history display
4. **Message Bubbles** - User and assistant messages
5. **Avatars** - User and assistant icons
6. **Typing Indicator** - "Bot is typing" animation
7. **Input Area** - Message input and send button
8. **Toggle Button** - Floating widget trigger
9. **Scrollbar** - Custom styled scrollbar
10. **Sources** - Web search results display

---

## Responsive Behavior

Both themes include responsive breakpoints:

```css
@media (max-width: 768px) {
    /* Tablet adjustments */
}

@media (max-width: 480px) {
    /* Mobile adjustments */
}
```

---

## Dark Mode Support

Both themes support dark mode via system preferences:

```css
@media (prefers-color-scheme: dark) {
    .gd-chatbot-container {
        /* Dark mode overrides */
    }
}
```

---

## Accessibility Features

Both themes include:
- ✅ High contrast ratios
- ✅ Keyboard navigation support
- ✅ Screen reader friendly
- ✅ Reduced motion support
- ✅ Focus indicators
- ✅ ARIA labels

---

## Performance Considerations

### Grateful Dead Theme
- **Pros**: Engaging, memorable, on-brand
- **Cons**: More CSS, external fonts, more animations
- **Load Time**: +50-100ms (font loading)

### Professional Theme
- **Pros**: Fast loading, system fonts, minimal CSS
- **Cons**: Less distinctive, fewer visual effects
- **Load Time**: Baseline

---

## Browser Support

Both themes support:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

---

## Troubleshooting

### Theme Not Applying

1. **Clear browser cache** (Ctrl+F5)
2. **Clear WordPress cache** (if using caching plugin)
3. **Check CSS is loading** (F12 → Network tab)
4. **Verify class name** in HTML inspector
5. **Check for CSS conflicts** with other plugins

### Fonts Not Loading (GD Theme)

1. Check Google Fonts CDN is accessible
2. Verify no Content Security Policy blocking fonts
3. Check network tab for font loading errors
4. Consider self-hosting fonts if needed

### Animations Not Working

1. Check `prefers-reduced-motion` setting
2. Verify JavaScript is enabled
3. Check for CSS conflicts
4. Inspect element for animation classes

---

## Best Practices

1. **Choose theme based on brand** - GD for music/fan sites, Professional for business
2. **Test on multiple devices** - Both themes are responsive but test your use case
3. **Consider accessibility** - Professional theme may be better for accessibility-focused sites
4. **Monitor performance** - GD theme has more assets, monitor load times
5. **Customize thoughtfully** - Override variables rather than rewriting entire theme
6. **Document changes** - Keep notes on any customizations
7. **Test after updates** - Verify theme still works after plugin updates

---

## Support

**Questions about themes?**
- Email: peter@it-influentials.com
- Include: Theme name, WordPress version, browser, screenshot

**Want to contribute a theme?**
- Follow the custom theme creation steps above
- Submit via email with documentation
- Include responsive and accessibility features

---

**Last Updated:** January 12, 2026  
**Current Default:** Grateful Dead Psychedelic Theme  
**Available Themes:** 2 (Grateful Dead, Professional)
