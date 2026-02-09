# Grateful Dead Performance Database (1965-1995)

## Overview

This archive contains a comprehensive database of Grateful Dead performances from 1965 to 1995, organized by year in CSV format.

## Data Source

The data was sourced from the **gdshowsdb** repository (https://github.com/jefmsmit/gdshowsdb), a well-maintained and authoritative database of Grateful Dead show information.

## File Structure

The archive contains 31 CSV files, one for each year from 1965 to 1995:
- `1965.csv` through `1995.csv`

Each CSV file contains shows performed during that specific year.

## CSV Format

Each CSV file has the following fields:

| Field | Description |
|-------|-------------|
| **Date** | Show date in MM/DD/YYYY format |
| **Venue Name** | Name of the venue where the show was performed |
| **Venue Location** | City and state/country of the venue |
| **Set List** | Complete setlist organized by sets (e.g., "Set 1: Song1, Song2; Set 2: Song3, Song4") |
| **Performers** | Band name (always "Grateful Dead" in this dataset) |

## Dataset Statistics

- **Total Years Covered**: 31 years (1965-1995)
- **Total Shows**: 2,388 performances
- **Date Range**: May 5, 1965 to July 9, 1995

### Shows by Year

| Year | Shows | Year | Shows | Year | Shows |
|------|-------|------|-------|------|-------|
| 1965 | 11 | 1976 | 41 | 1987 | 85 |
| 1966 | 98 | 1977 | 60 | 1988 | 80 |
| 1967 | 113 | 1978 | 82 | 1989 | 74 |
| 1968 | 128 | 1979 | 75 | 1990 | 74 |
| 1969 | 134 | 1980 | 86 | 1991 | 77 |
| 1970 | 144 | 1981 | 85 | 1992 | 55 |
| 1971 | 78 | 1982 | 62 | 1993 | 82 |
| 1972 | 87 | 1983 | 67 | 1994 | 83 |
| 1973 | 73 | 1984 | 65 | 1995 | 47 |
| 1974 | 40 | 1985 | 71 | | |
| 1975 | 4 | 1986 | 47 | | |

## Notes

- **Early Years (1965-1966)**: Many early shows have incomplete or missing setlist information, as comprehensive documentation was not yet standard practice.
- **1975**: The Grateful Dead took a hiatus in 1975, resulting in only 4 shows that year.
- **Setlist Format**: Songs that segue into each other are indicated with ">" (e.g., "Scarlet Begonias > Fire on the Mountain").
- **Multiple Sets**: Shows typically have 2-3 sets, with some shows including encore performances.

## Data Quality

The data has been carefully extracted from YAML source files and converted to CSV format while preserving:
- Accurate date formatting
- Complete venue information
- Full setlist details with proper set organization
- Segue information between songs

## Usage

You can import these CSV files into any spreadsheet application (Excel, Google Sheets, LibreOffice Calc) or use them programmatically with data analysis tools (Python pandas, R, etc.).

## License

This dataset is derived from the gdshowsdb project, which is open source. Please refer to the original repository for licensing information.

## Created

January 3, 2026

---

*What a long, strange trip it's been...*
