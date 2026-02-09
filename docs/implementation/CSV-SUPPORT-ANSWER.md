# CSV File Support - Answer

## ❌ No, CSV files are NOT currently supported

### What AI Power Supports for Chat File Uploads

**Supported**:
- ✅ PDF files (`.pdf`)
- ✅ Text files (`.txt`)

**Not Supported**:
- ❌ CSV files (`.csv`)
- ❌ Excel files (`.xlsx`, `.xls`)
- ❌ Word files (`.docx`, `.doc`)
- ❌ Other formats

### Why Not CSV?

Looking at the AI Power code, the chat file upload validator explicitly allows only:

```php
$allowed_mime_types = [
    'text/plain',      // .txt files
    'application/pdf', // .pdf files
];
```

And the file processor only handles:
- `application/pdf` → Extracts text via PDF parser
- `text/plain` → Reads as plain text
- Everything else → Returns error: "Unsupported file type"

### Workarounds for CSV Data

If you need to make CSV data available to the chatbot, you have **3 options**:

#### Option 1: Convert CSV to TXT
```bash
# Simple conversion
cat data.csv > data.txt

# Upload data.txt via AI Power chat
```

#### Option 2: Create WordPress Post with CSV Data
1. Create a WordPress post
2. Paste CSV data into post content
3. Use AI Power to index the post
4. Chatbot will find it via `wordpress_post` source

#### Option 3: Use GD Knowledgebase Loader
The GD Knowledgebase Loader plugin **does support CSV files**:
1. Upload CSV to Knowledgebase Loader
2. It processes and chunks the CSV
3. Creates embeddings and stores in Pinecone
4. Chatbot can access via KB Loader integration

### Current Integration Support

The chatbot integration currently supports:

| Source | Format | Supported |
|--------|--------|-----------|
| WordPress Posts | Any | ✅ Yes |
| WordPress Pages | Any | ✅ Yes |
| AI Power Uploads - PDF | `.pdf` | ✅ Yes |
| AI Power Uploads - TXT | `.txt` | ✅ Yes |
| AI Power Uploads - CSV | `.csv` | ❌ No |
| KB Loader - CSV | `.csv` | ✅ Yes |
| KB Loader - PDF | `.pdf` | ✅ Yes |
| KB Loader - TXT/MD | `.txt`, `.md` | ✅ Yes |
| KB Loader - JSON | `.json` | ✅ Yes |
| KB Loader - DOCX | `.docx` | ✅ Yes |
| KB Loader - XLSX | `.xlsx` | ✅ Yes |

### Recommendation

**For CSV files, use the GD Knowledgebase Loader plugin instead of AI Power file uploads.**

The Knowledgebase Loader:
- ✅ Supports CSV natively
- ✅ Parses CSV structure
- ✅ Creates meaningful chunks
- ✅ Includes headers in context
- ✅ Works alongside AI Power integration

### Example: Using Both

**Best Practice**:
- **AI Power**: Index WordPress posts/pages + upload PDFs/TXTs
- **KB Loader**: Upload CSV, JSON, DOCX, XLSX files

Both will be searched together and provide comprehensive context to Claude!

### Future Enhancement

If you need CSV support in AI Power, you could:
1. Request it from AI Power developers
2. Or extend the validator to add CSV support (would require modifying AI Power plugin)

But for now, **use Knowledgebase Loader for CSV files**.

## Summary

**Question**: Does this support CSV file uploads?

**Answer**: ❌ **No** - AI Power chat uploads only support PDF and TXT files.

**Solution**: Use **GD Knowledgebase Loader** for CSV files (it supports CSV natively).

**Result**: You can still use CSV data - just upload it via Knowledgebase Loader instead of AI Power!

---

**Updated**: January 4, 2026  
**Verified**: AI Power v2.3.50
