###############################################
OMGTube – YouTube Video Import Feature Guide
###############################################

This file explains HOW TO USE the **YouTube Video Import** page that you will find inside the admin panel (`/admin/youtube_import.php`).
It covers prerequisites, step-by-step usage, all filters, and how the system stores videos / categories / tags automatically.

-------------------------------------------------
1. Prerequisites
-------------------------------------------------
A. YouTube Data API v3 key
   • Go to Google Cloud Console ➜ create a project ➜ enable **YouTube Data API v3** ➜ create an **API key**.
   • In the OMGTube admin panel open **Settings ➜ API Keys** and paste the key into **youtube_api_key**.

B. Categories (optional)
   • At least one category should exist in the `categories` table if you plan to save videos under a specific category.

C. Allowed quota
   • The YouTube key must have sufficient daily quota. If quota is exhausted you will see the red error banner.

-------------------------------------------------
2. Page Overview
-------------------------------------------------
Field list on the form:
   1. *Keyword* (optional)  – Free-text search words.
   2. *Category* (optional) – Dropdown of local categories. If empty the system will try to map categories automatically from video tags.
   3. *Date From / Date To* (optional) – ISO dates (yyyy-mm-dd) to restrict publishing dates.
   4. *Video Count* – Number of items to fetch (1-100, YouTube max 50 per request).
   5. *Video Type* – All | Shorts (<60s) | Long (≥60s)
   6. *Channel ID/Username* (optional) – Accepts:
        • Full channel ID starting with **UC…**
        • Old-style username (e.g. "GoogleDevelopers")
        • @handle or any custom URL/vanity name

   «Fetch Videos» button – contacts YouTube API and shows preview.
   «Add Selected Videos» – writes checked rows to database.

-------------------------------------------------
3. How It Works Behind The Scenes
-------------------------------------------------
1. **Request Building**
   • If *Keyword* is provided → Search API (`order=viewCount`).
   • If no keyword but Channel is provided → Search API restricted to that channel (`order=date`).
   • If neither keyword nor channel → Most-Popular list (`chart=mostPopular`).

2. **Channel Username Resolution**
   • First tries `channels?forUsername=USER`.
   • If that fails it searches `search?type=channel&q=USER` and picks the first result.

3. **Filtering in PHP**
   • Shorts/Long filter – extra call to `videos?part=contentDetails` to read duration.
   • Only videos that pass chosen length filter remain.

4. **Saving to DB**
   • Duplicate check on `video_id` + source.
   • Category priority:
        a) Selected Category dropdown, else
        b) First local category whose *name OR slug* appears in any imported video *tag*.
   • Tags: every YouTube tag is inserted (if new) into `tags` table and linked through `video_tags`.

-------------------------------------------------
4. Typical Workflows
-------------------------------------------------
A. Import by Keyword only
   1. Leave *Channel* blank.
   2. Enter keyword, e.g. `funny cats`.
   3. Choose a Category if you want (optional).
   4. Click Fetch, review, deselect any you don’t want, click Add.

B. Import all shorts from a specific Channel ID
   1. Paste channel ID e.g. `UC_x5XG1OV2P6uZZ5FSM9Ttw`.
   2. Select *Video Type = Shorts* and Count = 50.
   3. Fetch ➜ Add.

C. Import using Username / @handle
   1. In Channel field type `PewDiePie` **or** `@MrBeast`.
   2. Leave Keyword empty or add one to filter.
   3. Proceed as normal.

D. Quick Viral Picks
   1. Leave Keyword + Channel empty.
   2. Set Count 10 – you will get the current most-popular videos in region US.

-------------------------------------------------
5. Common Errors & Fixes
-------------------------------------------------
• "YouTube API key not set" – Add key in Settings.
• "YouTube API HTTP status: 403" – Key invalid or quota exhausted.
• "No data fetch from YouTube API" – Network error or empty result; try different query/filters.
• Channel username still not resolved – make sure the name is correct; some channels have neither username nor handle; paste full channel URL instead.

-------------------------------------------------
6. Tips
-------------------------------------------------
• Use fewer filters first, then refine.
• For region-specific searches change `regionCode` in code if needed.
• Daily quota is consumed quickly; fetch small counts.
• Duplicate imports are skipped automatically.

-------------------------------------------------
End of Guide
################################################
