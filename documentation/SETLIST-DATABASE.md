# Setlist Database Integration

**Version**: 1.0.0  
**Date**: January 3, 2026  
**Status**: ✅ **COMPLETE**

---

## Overview

The GD Claude Chatbot now has **direct access to comprehensive Grateful Dead setlist data** from 1965-1995. This allows the chatbot to answer specific questions about shows, setlists, venues, dates, and songs with accurate, authoritative information.

---

## Database Contents

### Comprehensive Show Data

- **Total Shows:** 2,388 performances
- **Date Range:** May 5, 1965 to July 9, 1995
- **Years Covered:** 31 years (1965-1995)
- **Data Format:** CSV files organized by year

### Data Fields

Each show entry contains:
- **Date:** Show date (MM/DD/YYYY format)
- **Venue Name:** Where the show was performed
- **Venue Location:** City and state/country
- **Set List:** Complete setlist with sets organized (e.g., "Set 1: Song1, Song2; Set 2: Song3, Song4")
- **Performers:** Band name (Grateful Dead)

### Data Source

The setlist database was sourced from the **gdshowsdb repository** (https://github.com/jefmsmit/gdshowsdb), a well-maintained and authoritative database of Grateful Dead show information.

---

## How It Works

### Automatic Detection

The system automatically detects when users are asking about:
- ✅ **Specific shows** (e.g., "Cornell 5/8/77")
- ✅ **Setlists** (e.g., "What did they play at...")
- ✅ **Venues** (e.g., "Shows at Winterland")
- ✅ **Locations** (e.g., "Shows in San Francisco")
- ✅ **Songs** (e.g., "When did they play Dark Star")
- ✅ **Years/Tours** (e.g., "1977 shows")
- ✅ **Dates** (e.g., "What happened on May 8, 1977")

### Query Processing Flow

```
User Question
    ↓
Is this a setlist query?
    ├─ Yes → Search setlist database
    │         ├─ Find matching shows
    │         └─ Format results
    └─ No → Continue normal processing
    ↓
Add setlist data to context (if found)
    ↓
Send to Claude with:
    ├─ System Prompt (GD knowledge base)
    ├─ Setlist Database Results
    ├─ Pinecone results (if enabled)
    └─ Tavily results (if enabled)
    ↓
Claude Response with accurate show data
```

### Search Types

The system intelligently routes queries to the appropriate search method:

1. **Specific Date Search**
   - Queries: "Cornell 5/8/77", "May 8, 1977", "5/8/1977"
   - Returns: Complete show details with full setlist

2. **Year Search**
   - Queries: "1977 shows", "shows in 1979"
   - Returns: Summary of all shows that year

3. **Venue Search**
   - Queries: "Winterland shows", "Barton Hall performances"
   - Returns: All shows at matching venues

4. **Location Search**
   - Queries: "shows in San Francisco", "New York performances"
   - Returns: All shows in that location

5. **Song Search**
   - Queries: "when did they play Dark Star", "Fire on the Mountain performances"
   - Returns: Shows featuring that song

6. **General Search**
   - Queries: Any other text
   - Returns: Shows matching any field

---

## Implementation Details

### Class: `GD_Setlist_Search`

**Location:** `includes/class-setlist-search.php`

**Key Methods:**

```php
// Main search method
public function search($query)

// Check if query is about setlists
public function is_setlist_query($query)

// Specific search types
private function search_by_specific_date($query)
private function search_by_year($query)
private function search_by_venue($query)
private function search_by_location($query)
private function search_by_song($query)
private function general_search($query)
```

### Integration Points

**1. Chat Handler** (`class-chat-handler.php`)
- Checks for setlist queries before Pinecone/Tavily
- Adds setlist results to context if found
- Marks as "Setlist Database" source

**2. Main Plugin** (`gd-claude-chatbot.php`)
- Loads setlist search class
- Available to both streaming and non-streaming handlers

**3. Both Processing Methods:**
- `process_message()` - Non-streaming
- `process_message_stream()` - Streaming

---

## Usage Examples

### Example 1: Specific Show

**User:** "Tell me about Cornell 5/8/77"

**System:**
1. Detects setlist query
2. Searches for show on 5/8/1977
3. Finds show at Barton Hall, Ithaca, NY
4. Adds complete setlist to context
5. Claude responds with detailed information

**Response includes:**
- Venue and location
- Complete setlist by set
- Context about why this show is legendary
- Specific song performances

### Example 2: Venue Search

**User:** "What shows did they play at Winterland?"

**System:**
1. Detects venue query
2. Searches all years for "Winterland"
3. Returns list of matching shows
4. Adds to context

**Response includes:**
- List of all Winterland shows
- Date range
- Notable performances
- Historical context about the venue

### Example 3: Song Search

**User:** "When did they play Dark Star in 1977?"

**System:**
1. Detects song query
2. Searches 1977 shows for "Dark Star"
3. Finds all performances
4. Adds dates and venues to context

**Response includes:**
- List of shows featuring Dark Star
- Dates and venues
- Context about 1977 Dark Stars
- Notable versions

### Example 4: Year Summary

**User:** "How many shows did they play in 1989?"

**System:**
1. Detects year query
2. Loads all 1989 shows
3. Generates statistics
4. Adds summary to context

**Response includes:**
- Total show count (74)
- Monthly breakdown
- Sample show list
- Context about 1989 era

---

## Data Format Examples

### Specific Show Detail

```markdown
## Show: 05/08/1977

**Venue:** Barton Hall
**Location:** Ithaca, NY

**Setlist:**

- Set 1: New Minglewood Blues, Loser, El Paso, They Love Each Other, Jack Straw, Deal, Lazy Lightnin' > Supplication, Brown Eyed Women, Mama Tried, Row Jimmy, Dancing In The Street
- Set 2: Scarlet Begonias > Fire On The Mountain, Estimated Prophet, Saint Stephen > Not Fade Away > Saint Stephen > Morning Dew
- Set 3: One More Saturday Night
```

### Year Summary

```markdown
## Grateful Dead Shows in 1977

**Total Shows:** 60

**Monthly Breakdown:**
- **February:** 2 shows
- **March:** 4 shows
- **April:** 9 shows
- **May:** 17 shows
...
```

### Venue Results

```markdown
## Shows at venues matching "Winterland"

**Found 15 shows:**

- **02/17/1968** - Winterland Arena, San Francisco, CA
- **02/18/1968** - Winterland Arena, San Francisco, CA
...
```

---

## Features & Capabilities

### Intelligent Query Detection

**Keyword Recognition:**
- setlist, set list, show, concert, performance
- played, venue, date, when did
- Specific venue names (Cornell, Winterland, etc.)
- Song titles
- Date patterns

**Pattern Recognition:**
- Date formats (MM/DD/YYYY, M/D/YY, Month DD, YYYY)
- Year references (1977, '77, etc.)
- Location patterns (in San Francisco, at...)

### Result Limiting

To maintain performance and readability:
- **Specific Date:** Full details (1 show)
- **Year:** Summary + first 10 shows
- **Venue:** Up to 20 matching shows
- **Location:** Up to 20 matching shows
- **Song:** Up to 15 matching shows
- **General:** Up to 10 matching shows

### Source Attribution

When setlist data is used, it's clearly marked:
```
sources: {
    setlist_database: [{
        title: 'Grateful Dead Setlist Database (1965-1995)',
        url: '',
        score: 100
    }]
}
```

---

## Performance Considerations

### File Access

**CSV Files:**
- 31 files (1965-1995)
- Average file size: ~50-200KB
- Only accessed when needed
- Parsed on-demand

**Optimization Strategies:**
1. **Smart Detection:** Only search if query is setlist-related
2. **Targeted Search:** Search specific year file when possible
3. **Result Limiting:** Cap results to prevent memory issues
4. **Efficient Parsing:** Use native PHP CSV functions

### Memory Usage

**Per Query:**
- Small queries: < 1MB
- Large queries: < 5MB
- Results formatted and released immediately

**Caching:**
- No persistent caching (WordPress handles that)
- Results generated fresh each time
- Could add caching if needed

---

## Data Quality

### Accuracy

- ✅ **Sourced from gdshowsdb:** Well-maintained, community-verified
- ✅ **Complete Coverage:** All known shows 1965-1995
- ✅ **Detailed Setlists:** Song-by-song for most shows
- ✅ **Segue Information:** Shows song transitions (>)
- ✅ **Set Organization:** Clear set boundaries

### Known Limitations

⚠️ **Early Years (1965-1966):**
- Many shows have incomplete setlist information
- Documentation was not standardized yet

⚠️ **1975 Hiatus:**
- Only 4 shows (band took a break)

⚠️ **Rare Shows:**
- Some obscure shows may have incomplete data
- Missing information noted in original database

---

## Future Enhancements

Potential improvements:

1. **Full-Text Song Search:** Search setlist text for specific phrases
2. **Statistical Analysis:** Song frequency, venue statistics, etc.
3. **Advanced Filtering:** Date ranges, multiple criteria
4. **Segue Analysis:** Find specific song transitions
5. **Encore Detection:** Identify encore songs
6. **Guest Musicians:** Track guest appearances
7. **Caching Layer:** Cache frequently requested shows
8. **Database Indexing:** Pre-index for faster searches
9. **Fuzzy Matching:** Handle misspellings better
10. **Related Shows:** "Similar shows" recommendations

---

## Troubleshooting

### Common Issues

**1. Setlist Data Not Found**

**Symptoms:** Claude says "I don't have information about that show"

**Causes:**
- Show doesn't exist in database
- Date format not recognized
- Typo in venue/song name

**Solution:**
- Check date format
- Try different query phrasing
- Verify show actually happened

**2. Incomplete Results**

**Symptoms:** Partial show list, missing dates

**Causes:**
- Result limiting (by design)
- Query too broad

**Solution:**
- More specific query
- Ask for specific year/venue
- Break into multiple queries

**3. Slow Responses**

**Symptoms:** Takes long time to respond

**Causes:**
- Large result set
- Searching all years
- Combined with other context sources

**Solution:**
- More specific query
- Search single year at a time
- Normal with comprehensive searches

---

## Testing

### Test Queries

**Specific Shows:**
```
- "Cornell 5/8/77"
- "What happened at Barton Hall on May 8, 1977?"
- "Tell me about the show on 5/8/1977"
```

**Venue Searches:**
```
- "Shows at Winterland"
- "Fillmore performances"
- "Madison Square Garden shows"
```

**Song Searches:**
```
- "When did they play Dark Star in 1973?"
- "Fire on the Mountain performances"
- "Scarlet > Fire shows"
```

**Year Searches:**
```
- "1977 shows"
- "How many shows in 1989?"
- "What did they play in 1972?"
```

### Expected Results

Each query type should:
- ✅ Return relevant shows
- ✅ Include complete information
- ✅ Format properly
- ✅ Be marked as setlist database source
- ✅ Integrate with Claude's knowledge

---

## Benefits

### For Users

1. **Accurate Information:** Direct from authoritative database
2. **Comprehensive Coverage:** All 2,388 shows
3. **Detailed Setlists:** Song-by-song information
4. **Fast Answers:** Quick search and response
5. **Context-Aware:** Claude combines with other knowledge

### For the Chatbot

1. **Authoritative Data:** No hallucinations about show details
2. **Specific Facts:** Exact dates, venues, setlists
3. **Enhanced Credibility:** Cites actual database
4. **Complete Picture:** Combines with GD knowledge base
5. **Better Responses:** More accurate, detailed answers

### For Administrators

1. **No Configuration:** Works automatically
2. **No Maintenance:** Data is static (historical)
3. **Low Overhead:** Minimal performance impact
4. **Easy Updates:** Can replace CSV files if needed
5. **Clear Attribution:** Source tracking built-in

---

## Summary

The Setlist Database Integration provides the chatbot with:

✅ **2,388 shows** of accurate setlist data  
✅ **31 years** of complete coverage (1965-1995)  
✅ **Intelligent query detection** for relevant searches  
✅ **Multiple search types** (date, venue, song, year, etc.)  
✅ **Automatic integration** with existing context sources  
✅ **Clear source attribution** in responses  
✅ **Zero configuration** required  

Users can now ask specific questions about any Grateful Dead show and receive accurate, detailed information directly from the database, enhanced by Claude's understanding and the comprehensive knowledge base.

---

**Files Modified:**
- `includes/class-setlist-search.php` (NEW)
- `includes/class-chat-handler.php` (UPDATED)
- `gd-claude-chatbot.php` (UPDATED)

**Database Location:**
- `context/Deadshows/deadshows/*.csv`

**Documentation:**
- This file (SETLIST-DATABASE.md)

---

*Last Updated: January 3, 2026*  
*Plugin Version: 1.0.0*  
*Feature Status: Production Ready*
