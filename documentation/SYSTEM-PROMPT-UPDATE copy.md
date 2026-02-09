# ScubaGPT System Prompt Update

## Overview

Updated the default system prompt for ScubaGPT chatbot to provide more comprehensive guidelines for dive travel planning, safety, and response formatting.

**Date:** January 7, 2026  
**Version:** 1.1.0  
**File Modified:** `scubagpt-chatbot/includes/class-scubagpt-admin.php`

---

## What Changed

### Previous System Prompt
The previous prompt was structured with sections for:
- Role and background
- User context and experience levels
- Critical safety rules
- Dive site response format
- Formatting guidelines
- What to avoid

### New System Prompt
The updated prompt maintains the same core principles but reorganizes and expands the guidelines with clearer structure and additional requirements.

---

## Key Enhancements

### 1. Role Definition
**Enhanced clarity:**
- Expert travel planner for recreational divers
- Rescue diver and master diver certified
- 10+ years as dive guide
- Expert in coral reef ecology
- Expert in fish and coral identification
- Studied hyperbaric medicine

### 2. Purpose Expansion
**Explicitly lists information types provided:**
- Dive sites
- Dive locations
- Dive shops
- Dive resorts
- Dive boat operators
- Dive gear
- Underwater photos
- Dive site maps
- Marine life spotted at dive sites
- Best time of year to dive

### 3. Enhanced Safety Rules
**9 Non-Negotiable Rules:**

1. **Accuracy & Honesty** - Never invent coordinates, depths, or species
2. **Species Identification** - Always provide confidence levels and explain uncertainty
3. **Medical Clearance** - Direct users to physicians/dive medical specialists
4. **Conservation & Legal Compliance** - Never omit protected-area rules or permit requirements
5. **Data Currency** - Always indicate dates and uncertainty for old data
6. **Admit Uncertainty** - Say "I don't know" when appropriate
7. **No Gas Planning** - Never include gas planning information
8. **No Booking Services** - Never offer to book operators, liveaboards, or resorts
9. **No Reddit Content** - Do not use content from Reddit.com

### 4. Recommendation Guidelines
**New explicit guidance:**
- Don't recommend specific operators/resorts/products unless specifically asked
- Always provide attributes that might appeal to the user

### 5. Dive Site Response Structure
**Enhanced formatting requirements:**

**Site Name & Location**
- Name of dive site
- Country of dive site

**Dive Characteristics**
- Difficulty & entry types
- Depth

**Marine Life**
- Marine life spotted
- Current reef health (when available)

**Conditions & Safety**
- Information about currents
- Latitude and Longitude (when available)
- Google Maps link if coordinates available

**Operators & Services**
- Dive guides, operators, resorts, or liveaboards (if available)

**Formatting Requirements**
- Bulleted lists with emojis
- Cite up to 3 sources for each dive site, liveaboard, resort, or dive center
- Main topics in bolded text in larger type
- Paragraph breaks before main topics

### 6. News and Sources Section (NEW)
**Three new requirements:**
1. Tell user about relevant recent news related to their request
2. Provide a list of all URLs in response with heading "Links:"
3. Provide up to 10 linked URLs for homepages where users can find related information

### 7. Conversation Memory (NEW)
**Explicit instruction:**
- When responding to follow-up questions, remember previous responses unless asked not to

---

## Technical Implementation

### Location
`scubagpt-chatbot/includes/class-scubagpt-admin.php`

### Method
`get_default_system_prompt()`

### Usage
This method is called in two places:
1. When building augmented prompts in chat processing
2. When rendering the System Prompt settings page in admin

### How It's Applied
```php
// In class-scubagpt-chat.php
private function build_augmented_prompt($context_parts) {
    $base_prompt = get_option('scubagpt_system_prompt', '');
    if (empty($base_prompt)) {
        $admin = new ScubaGPT_Admin();
        $base_prompt = $admin->get_default_system_prompt();
    }
    
    // ... context is appended to base prompt
}
```

### Customization
Administrators can customize the system prompt via:
- WordPress Admin > ScubaGPT > System Prompt
- Editable in large textarea
- "Reset to Default" button available
- Changes saved to `scubagpt_system_prompt` option

---

## Benefits of Update

### 1. Clarity
- More explicit instructions
- Clearer section organization
- Easier to understand requirements

### 2. Safety
- Stronger emphasis on not fabricating data
- Explicit prohibition on species ID overconfidence
- Clear medical clearance guidelines
- Conservation rules enforcement

### 3. User Experience
- Required news and related links
- Consistent formatting with emojis
- Source citations mandated
- Google Maps links for dive sites

### 4. Quality Control
- No Reddit content allowed
- Currency of data must be indicated
- Confidence levels required for uncertain information
- Multiple source citations when available

### 5. Conversation Flow
- Explicit conversation memory instruction
- Better follow-up question handling
- Context awareness across messages

---

## Comparison Table

| Feature | Previous Prompt | New Prompt | Improvement |
|---------|----------------|------------|-------------|
| Role definition | Narrative style | Bullet points | ✅ Clearer |
| Safety rules | 4 rules | 9 rules | ✅ More comprehensive |
| Species ID guidance | Basic | Confidence levels required | ✅ More accurate |
| Data currency | Mentioned | Explicit requirement | ✅ Enforced |
| News requirement | Not mentioned | Explicitly required | ✅ Added |
| URL links | Not structured | "Links:" section + 10 homepages | ✅ Better organization |
| Conversation memory | Implicit | Explicit instruction | ✅ Clearer |
| Reddit content | Not mentioned | Explicitly prohibited | ✅ Better quality |
| Formatting | General | Specific (emojis, bold, breaks) | ✅ Consistent output |
| Source citations | Suggested | Required (up to 3) | ✅ More credible |

---

## Impact on Responses

### Before Update
Response might include:
- General dive site information
- Some safety notes
- Occasional sources
- Basic formatting

### After Update
Response will include:
- **Structured dive site information** with all required fields
- **Explicit uncertainty** when data is old or unavailable
- **Confidence levels** for species identifications
- **Required source citations** (up to 3 per site)
- **Consistent emoji formatting** in bullet lists
- **Recent news** related to query
- **"Links:" section** with all URLs used
- **Up to 10 homepage URLs** for further research
- **Google Maps links** when coordinates available
- **Conversation context** maintained across follow-ups

---

## User Benefits

### 1. Safety
- More accurate information
- Better understanding of data limitations
- Proper medical guidance direction
- Conservation awareness

### 2. Planning
- Recent news about destinations
- Multiple research links provided
- Clear dive site characteristics
- Operator information when available

### 3. Trust
- Source citations required
- Confidence levels provided
- Uncertainty acknowledged
- No fabricated data

### 4. Usability
- Consistent formatting with emojis
- Organized sections
- Easy-to-scan bullet points
- Google Maps integration

---

## Testing Recommendations

### Test Scenarios

#### 1. Dive Site Query
**Test:** "Tell me about the Blue Hole in Belize"

**Expected Response:**
- ✅ Site name and country
- ✅ Coordinates with Google Maps link
- ✅ Depth and difficulty
- ✅ Marine life with current reef health
- ✅ Current information
- ✅ Operators/resorts (if available)
- ✅ Emoji-formatted bullet lists
- ✅ Bold main topics
- ✅ Source citations (up to 3)
- ✅ Recent news (if any)
- ✅ "Links:" section
- ✅ Up to 10 homepage URLs

#### 2. Species Identification
**Test:** "What fish is this?" (with unclear description)

**Expected Response:**
- ✅ Possible species mentioned
- ✅ Confidence level stated (e.g., "Low confidence")
- ✅ Explanation of uncertainty
- ✅ Request for more details if needed
- ✅ No fabricated definitive answer

#### 3. Medical Question
**Test:** "Can I dive with asthma?"

**Expected Response:**
- ✅ General information about asthma and diving
- ✅ Clear direction to consult dive physician
- ✅ Mention of DAN or dive medical specialists
- ✅ No definitive clearance given
- ✅ Safety emphasized

#### 4. Old Data Test
**Test:** "What's the reef health at XYZ site?"

**Expected Response (if data is old):**
- ✅ Data provided with date
- ✅ Note that data may be outdated
- ✅ Suggestion to verify with recent sources
- ✅ No fabricated current status

#### 5. Follow-up Question
**Test:** First ask about Egypt diving, then "What about the Red Sea sites?"

**Expected Response:**
- ✅ Remembers previous Egypt context
- ✅ Provides Red Sea specifics
- ✅ Maintains conversation coherence

---

## Admin Notes

### Resetting to Default
If an administrator customized the system prompt and wants to revert:
1. Go to **ScubaGPT > System Prompt**
2. Click **"Reset to Default"** button
3. Click **"Save Changes"**

### Customization Tips
Administrators can customize the prompt but should maintain:
- Safety rules (critical for liability)
- Accuracy requirements (critical for user trust)
- Formatting guidelines (for consistent UX)
- Source citation requirements (for credibility)

### Backup Recommendation
Before customizing, administrators should:
1. Copy the default prompt to a text file
2. Make incremental changes
3. Test thoroughly before deploying

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | January 2026 | Initial system prompt (previous) |
| 1.1.0 | January 7, 2026 | Comprehensive update with 9 safety rules, news requirement, links sections, and enhanced formatting |

---

## Related Documentation

- **Main README:** `README.md`
- **Safety Guardrails:** `PLUGIN-SAFETY-GUARDRAILS.md`
- **Admin Settings:** WordPress Admin > ScubaGPT > System Prompt
- **Source File:** `scubagpt-chatbot/includes/class-scubagpt-admin.php`

---

## Summary

The updated system prompt provides more comprehensive, clearer, and safer guidelines for ScubaGPT responses. It emphasizes accuracy, safety, proper formatting, and user value through enhanced structure, explicit requirements, and additional features like news updates and organized link sections.

**Key Improvements:**
- ✅ 9 comprehensive safety rules
- ✅ Required news and links sections
- ✅ Explicit formatting guidelines
- ✅ Source citation requirements
- ✅ Conversation memory instruction
- ✅ Better organization and clarity

**Result:** More consistent, safer, and more useful responses for recreational scuba divers planning their diving adventures.

---

**Last Updated:** January 7, 2026  
**Status:** ✅ Implemented  
**Impact:** All new chats use updated prompt  
**Backward Compatibility:** Existing customizations preserved
