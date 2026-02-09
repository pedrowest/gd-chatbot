# GD Knowledgebase Loader Integration

## Overview

The GD Claude Chatbot now integrates seamlessly with the **GD Knowledgebase Loader** plugin to provide context from your uploaded documents. This allows Claude to answer questions based on your own knowledge base.

## Features

✅ **Automatic Integration** - Works automatically when both plugins are installed  
✅ **Semantic Search** - Finds relevant content using AI embeddings  
✅ **Source Attribution** - Shows which documents were used  
✅ **Configurable** - Adjust relevance scores and result counts  
✅ **Works Alongside** - Complements Pinecone and Tavily search  

## Setup Instructions

### Step 1: Install Both Plugins

1. Install and activate **GD Knowledgebase Loader**
2. Install and activate **GD Claude Chatbot**

### Step 2: Configure Knowledgebase Loader

1. Go to **KB Loader > Settings**
2. Enter your Pinecone API Key
3. Enter your OpenAI API Key
4. Click **Save Settings**

### Step 3: Upload Documents

1. Go to **KB Loader > Upload**
2. Upload your knowledge base documents:
   - PDF files
   - Word documents (.docx)
   - Markdown files (.md)
   - CSV files
   - JSON files
   - Excel spreadsheets (.xlsx)
3. Wait for processing to complete

### Step 4: Configure Chatbot Integration

1. Go to **GD Chatbot > Settings > Knowledgebase** tab
2. Enable **Use knowledgebase for context**
3. Adjust settings:
   - **Maximum Results**: 10 (recommended: 5-15)
   - **Minimum Relevance Score**: 0.35 (recommended: 0.30-0.40)
4. Click **Save Settings**

## How It Works

```
User Question
     ↓
1. Query Knowledgebase
     ↓
2. Retrieve Relevant Chunks
     ↓
3. Add to Claude's Context
     ↓
4. Claude Generates Response
     ↓
5. Show Sources to User
```

### Context Priority

When a user asks a question, the chatbot searches for context in this order:

1. **Setlist Database** (for Grateful Dead show queries)
2. **GD Knowledgebase Loader** (your uploaded documents)
3. **Pinecone** (if enabled)
4. **Tavily Web Search** (if enabled)

All relevant context is combined and sent to Claude for the most accurate response.

## Configuration Options

### Maximum Results

**Default**: 10  
**Range**: 1-50  
**Recommended**: 5-15

Controls how many relevant chunks to retrieve from the knowledgebase. More results provide more context but may dilute relevance.

### Minimum Relevance Score

**Default**: 0.35  
**Range**: 0.00-1.00  
**Recommended**: 0.30-0.40

Controls the minimum similarity score for including a chunk. Higher scores mean stricter relevance filtering.

- **0.20-0.30**: Very permissive, may include tangentially related content
- **0.30-0.40**: Balanced, good for most use cases
- **0.40-0.50**: Strict, only highly relevant content
- **0.50+**: Very strict, may miss relevant content

## Example Usage

### User asks:
> "What are the best practices for WordPress security?"

### System retrieves:
1. Searches knowledgebase for relevant chunks
2. Finds 3 highly relevant chunks (score > 0.35):
   - "WordPress Security Guide" (relevance: 87%)
   - "Plugin Security Best Practices" (relevance: 82%)
   - "Database Security" (relevance: 76%)

### Claude receives context:
```
## KNOWLEDGEBASE CONTEXT

### Source: WordPress Security Guide (Relevance: 87%)
WordPress security involves multiple layers including...

### Source: Plugin Security Best Practices (Relevance: 82%)
Always keep plugins updated and remove unused ones...

### Source: Database Security (Relevance: 76%)
Secure your database with strong passwords and...
```

### Claude responds with:
Accurate, detailed answer based on your documents, with source attribution.

## Benefits

### For Administrators
- Upload documents once, use everywhere
- No need to manually update system prompts
- Easy document management interface
- Track what documents are being used

### For Users
- Get accurate answers from authoritative sources
- See which documents were referenced
- Consistent, up-to-date information
- Fast, relevant responses

### For Developers
- Clean API integration
- Automatic context retrieval
- Error handling built-in
- Works with existing chatbot features

## Troubleshooting

### Knowledgebase Not Available

**Issue**: "GD Knowledgebase Loader Not Installed" message

**Solution**: Install and activate the GD Knowledgebase Loader plugin

### Knowledgebase Not Configured

**Issue**: "Knowledgebase Not Configured" message

**Solution**: 
1. Go to KB Loader > Settings
2. Enter Pinecone and OpenAI API keys
3. Save settings

### No Results from Knowledgebase

**Issue**: Chatbot not using knowledgebase content

**Solutions**:
1. Check that documents are processed (KB Loader > Manage)
2. Lower the minimum relevance score
3. Ensure knowledgebase is enabled in chatbot settings
4. Verify API keys are correct in KB Loader

### Irrelevant Results

**Issue**: Knowledgebase returns unrelated content

**Solutions**:
1. Increase minimum relevance score to 0.40-0.50
2. Reduce maximum results to 5-7
3. Review and improve document quality
4. Remove irrelevant documents

## API Reference

### Check if KB is Available

```php
$kb_integration = new GD_KB_Integration();
if ($kb_integration->is_available()) {
    // KB is ready to use
}
```

### Search Knowledgebase

```php
$results = $kb_integration->search($query, array(
    'top_k' => 10,
    'min_score' => 0.35
));
```

### Get Formatted Context

```php
$context = $kb_integration->get_context($query, 10);
```

### Get Statistics

```php
$stats = $kb_integration->get_stats();
// Returns: total_documents, total_chunks, processed_documents, failed_documents
```

## Best Practices

### Document Organization

1. **Use Clear Filenames**: Name files descriptively
2. **Organize by Topic**: Group related documents
3. **Keep Updated**: Regularly update documents
4. **Remove Outdated**: Delete obsolete information

### Optimal Settings

**For General Knowledge Base**:
- Max Results: 10
- Min Score: 0.35

**For Technical Documentation**:
- Max Results: 15
- Min Score: 0.40

**For FAQ/Support**:
- Max Results: 5
- Min Score: 0.30

### Content Quality

1. **Clear Writing**: Use clear, concise language
2. **Good Structure**: Use headings and sections
3. **Accurate Information**: Verify all facts
4. **Regular Updates**: Keep content current

## Performance Tips

1. **Chunk Size**: Default 1000 characters works well
2. **Overlap**: 15% overlap prevents context loss
3. **File Size**: Keep files under 50MB
4. **Processing**: Upload during off-peak hours

## Security

- API keys are encrypted in database
- Only administrators can configure
- Documents stored securely
- Access controlled by WordPress capabilities

## Support

For issues with:
- **Knowledgebase Loader**: Check KB Loader documentation
- **Chatbot Integration**: Check this document
- **General Support**: Contact peter@it-influentials.com

## Updates

### Version 1.0.0
- Initial integration with GD Knowledgebase Loader
- Automatic context retrieval
- Configurable settings
- Source attribution
- Statistics display

---

**Ready to use!** Your chatbot now has access to your knowledge base.
