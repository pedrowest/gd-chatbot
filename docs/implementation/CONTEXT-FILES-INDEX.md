# Context Files Index - GD Chatbot

## Overview

The `plugin/context/` directory contains the chatbot's complete knowledge base about the Grateful Dead, organized into 6 subdirectories with **72 files** total. This structure was reorganized in February 2026 from a flat directory with a single 63KB monolithic file into focused, topic-based files.

## Directory Structure

```
context/
├── core/               8 topic files (split from monolith, loaded in full-context mode)
├── disambiguation/     3 guides (loaded after core context)
├── reference/          3 CSV files (structured data for programmatic lookup)
├── supplementary/     21 files (loaded in full-context mode via glob)
├── setlists/          31 CSV files (1965-1995, queried by GD_Setlist_Search)
└── _archive/           6 files (dev/planning, not loaded by code)
```

## `core/` — Primary Knowledge Base (8 files)

Loaded via `glob('context/core/*.md')` in alphabetical order, concatenated into the system prompt in full-context mode. In optimized mode, condensed summaries are built per-query instead.

| File | Content | Loaded By |
|------|---------|-----------|
| `band-and-history.md` | Formation, evolution, members, eras, lineup changes | `load_full_context()` |
| `books-and-literature.md` | Essential Grateful Dead bibliography | `load_full_context()` |
| `culture-and-community.md` | Deadhead culture, post-Dead projects, philosophy | `load_full_context()` |
| `equipment.md` | Guitars, basses, drums, Wall of Sound, amplification | `load_full_context()` |
| `galleries-and-art.md` | Art galleries, museums, poster dealers | `load_full_context()` |
| `music-and-recordings.md` | Song catalog, discography, taping culture | `load_full_context()` |
| `resources-and-media.md` | History resources, online communities, people, URLs | `load_full_context()` |
| `terminology.md` | 125+ disambiguated terms | `load_full_context()` |

**Source**: Split from `grateful-dead-context.md` (1,655 lines, 63KB). Original preserved in `_archive/grateful-dead-context-ORIGINAL.md`.

## `disambiguation/` — Disambiguation Guides (3 files)

Loaded by `load_disambiguation_guides()` in `class-claude-api.php` after core context.

| File | Original Name | Content |
|------|---------------|---------|
| `song-titles.md` | `grateful_dead_disambiguation_guide.md` | Song title disambiguation |
| `duplicate-titles.md` | `Grateful Dead Songs with Duplicate Titles - Summary List.md` | Songs sharing titles |
| `equipment-names.md` | `Music-Equipment-Disambiguations.md` | Equipment name clarification |

## `reference/` — Structured Data (3 files)

CSV files used for programmatic lookup by PHP classes.

| File | Original Name | Used By |
|------|---------------|---------|
| `songs.csv` | `grateful_dead_songs.csv` | `GD_Context_Builder::build_song_guide_context()` |
| `equipment.csv` | `grateful_dead_equipment.csv` | Context builder equipment lookups |
| `tavily-domains.csv` | `tavily_trusted_domains.csv` | Tavily search domain filtering |

**Note**: Two duplicate tavily domain files were deleted during reorganization (`tavily_trusted_domains_list.csv`, `tavily_trusted_domains_list.txt`).

## `supplementary/` — Extended Knowledge (21 files)

Loaded via `glob('context/supplementary/*.md')` in `load_additional_knowledgebase_files()`. The `bahr-gallery.md` file is excluded from the glob and handled separately by `inject_bahr_gallery_content()`.

| File | Original Name | Content |
|------|---------------|---------|
| `academic-papers.md` | `Comprehensive List of...md` | Academic papers on the Dead |
| `ai-chatbots.md` | `Grateful Dead Chatbots and AI Tools.md` | AI tools survey |
| `bahr-gallery.md` | `the_bahr_gallery.md` | Bahr Gallery authoritative source (special handling) |
| `dissertations.md` | `dissertations_theses_list.md` | Academic dissertations |
| `equipment-detailed.md` | `Grateful Dead Equipment List.md` | Detailed equipment specs (was orphaned) |
| `gd-theme.md` | `GD-THEME.md` | Chatbot theme documentation |
| `gds-articles.md` | `gds_volume1_articles.md` | Grateful Dead Studies articles |
| `interviews.md` | `grateful_dead_interviews.md` | Interview collection (was orphaned) |
| `jerrybase-interviews.md` | `jerrybase.com_interviews_18.md` | Jerry Garcia interviews (was orphaned) |
| `jerry-garcia-gear.md` | `jerry_garcia_equipment.md` | Jerry's equipment details (was orphaned) |
| `jpb-compositions.md` | `www.deaddisc.com_GDFD_JPBCompositions.htm.md` | Barlow compositions (was orphaned) |
| `online-resources.md` | `A Comprehensive Guide to...md` | Online resource directory |
| `regional-galleries.md` | `A Guide to Regional Music and Rock Art Galleries.md` | Gallery guide |
| `research-findings.md` | `grateful_dead_papers_findings.md` | Research analysis |
| `reverb-gear-guide.md` | `reverb.com_news_the-gear-of-the-grateful-dead.md` | Reverb.com gear guide |
| `rh-songs.md` | `www.deaddisc.com_GDFD_RHSongs.htm.md` | Robert Hunter songs (was orphaned) |
| `songs-performances.md` | `www.deaddisc.com_GDFD_Songs_Perf.htm.md` | Performance statistics (was orphaned) |
| `statistics.md` | Lines 1584-1655 from monolith | Reference data points and stats |
| `transcripts.md` | `grateful_dead_interview_transcripts_complete.md` | Full transcripts (was orphaned) |
| `ucsc-archive.md` | `UC Santa Cruz...md` | UCSC archive holdings |
| `ucsc-notes.md` | `ucsc_gd_archive_notes.md` | UCSC archive research notes |

**9 previously orphaned files** (marked above) are now automatically loaded via the `glob()` pattern.

## `setlists/` — Show Database (31 files)

CSV files queried by `GD_Setlist_Search` class. One file per year, 1965-1995.

| Files | Content |
|-------|---------|
| `1965.csv` through `1995.csv` | Date, venue, location, setlist for every show |

**Previous path**: `context/Deadshows/deadshows/` (double-nested). Flattened to `context/setlists/`.

**Total shows**: 2,388 documented performances.

## `_archive/` — Development Files (6 files)

Not loaded by any code. Preserved for reference only.

| File | Original Name | Content |
|------|---------------|---------|
| `grateful-dead-context-ORIGINAL.md` | `grateful-dead-context.md` | Backup of monolith before splitting |
| `context-refactor-v1.9.0.md` | `GRATEFUL-DEAD-CONTEXT-REFACTOR-v1.9.0.md` | Refactor planning notes |
| `competencies.md` | `Grateful Dead Competencies` (no ext) | Knowledge area competencies |
| `context-requirements.md` | `Grateful Dead Context Requirements` (no ext) | Context requirements doc |
| `scratch-pad.md` | `Grateful Dead Scratch Pad` (no ext) | Working notes |
| `books-list.md` | `Grateful Dead Books` (no ext) | Bibliography draft |

## Content Statistics

| Directory | Files | Format | Loaded By |
|-----------|-------|--------|-----------|
| `core/` | 8 | Markdown | `load_full_context()` via glob |
| `disambiguation/` | 3 | Markdown | `load_disambiguation_guides()` |
| `reference/` | 3 | CSV | `GD_Context_Builder`, Tavily |
| `supplementary/` | 21 | Markdown | `load_additional_knowledgebase_files()` via glob |
| `setlists/` | 31 | CSV | `GD_Setlist_Search` |
| `_archive/` | 6 | Markdown | Not loaded |
| **Total** | **72** | | |

## Code References

| PHP File | Method | Context Path Used |
|----------|--------|-------------------|
| `class-claude-api.php` | `load_full_context()` | `context/core/*.md` |
| `class-claude-api.php` | `load_disambiguation_guides()` | `context/disambiguation/*.md` |
| `class-claude-api.php` | `inject_bahr_gallery_content()` | `context/supplementary/bahr-gallery.md` |
| `class-claude-api.php` | `load_additional_knowledgebase_files()` | `context/supplementary/*.md` |
| `class-context-builder.php` | `build_song_guide_context()` | `context/reference/songs.csv` |
| `class-setlist-search.php` | Constructor | `context/setlists/` |
| `scripts/build-release.sh` | Release packaging | All 5 production subdirectories |

---

**Total Files**: 72
**Production Files**: 66 (excluding _archive/)
**Time Span**: 1965-1995 (30 years)
**Shows Documented**: 2,388
**Last Updated**: February 11, 2026
