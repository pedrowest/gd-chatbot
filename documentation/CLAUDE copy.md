# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is the Scuba GPT project, which appears to be an AI-powered scuba diving assistant/chatbot focused on providing travel advice and diving site information. The repository contains training data, fine-tuning datasets, and prompt templates for developing a scuba diving knowledge system.

## Project Structure

- **Scuba GPT Training Data/**: Contains extensive scuba diving datasets including:
  - Dive site information with coordinates and descriptions (`Scuba_Sites_Parsed-x-y.csv`)
  - Marine biology and diving reference materials (PDF files)
  - Website seed lists for scuba diving resources (`ScubaGPT-SearchSites.txt`)
  - Excel spreadsheets with dive site data and PADI course information

- **Fine Tunings/**: Machine learning training datasets:
  - `train.csv` and `travel-wikipedia-train-data.csv`: Training data for travel/diving content generation
  - `SURF_ALL.gz`: Compressed dataset for model training
  - `wod_intro.pdf`: Reference documentation

- **Scuba GPT Agents/**: Directory for AI agent configurations (currently empty)
- **Scuba GPT PromptsPrompts/**: Directory for prompt templates (currently empty)

## Data Architecture

The core data structure revolves around scuba diving sites with the following key components:

1. **Dive Sites Database**: CSV files containing dive locations with:
   - Geographic coordinates (lat/lng)
   - Country and location information
   - Dive descriptions and characteristics
   - Google Maps integration data

2. **Training Content**: Wikipedia-style articles about travel destinations adapted for diving context, following a structured format with attractions and experiences.

3. **Website Sources**: Comprehensive list of 200+ diving-related websites for content crawling and reference, including major platforms like PADI, diving operators, and tourism boards.

## Content Generation System

The project uses a structured approach for travel content generation:
- Template-based article creation with specific sections (intro, attractions, experiences, conclusion)
- Dependency grammar frameworks for writing quality
- Sensory detail emphasis for immersive descriptions
- Integration of diving-specific knowledge with travel writing

## Development Notes

- No traditional software development build system detected (no package.json, requirements.txt, etc.)
- Data-driven project focused on AI training and content generation
- Primary file formats: CSV, Excel, PDF, and text files
- No automated testing or deployment configurations present

## Key Datasets

- **ScubaGPT-SearchSites.txt**: Authoritative list of diving websites for content sourcing
- **Travel Prompt.txt**: Template system for generating travel articles with diving focus
- **Scuba_Sites_Parsed-x-y.csv**: Geographic database of dive sites with detailed information