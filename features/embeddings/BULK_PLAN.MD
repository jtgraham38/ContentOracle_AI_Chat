# SCALABLE BULK EMBEDDINGS PLAN

## Overview
This plan outlines the strategy for efficiently generating embeddings for hundreds of posts using the ContentOracle AI API while maintaining system stability and providing a good user experience.

## Current Limitations
1. API rate limits
2. Long load times, no background running!!!!!!!!!!!!!!!
3. Timeout issues with long-running requests!!!!!!!!!!!!!!!!!
4. No progress tracking
5. No error recovery mechanism

## Implementation Plan

### Summary
We will create a custom table, `wp_coai_post_embed_queue`, to serve as a reference for all posts that we would like to generate embeddings for.  It will serve as a queue based on the `queued_time` attribute.  We will have a function running on `wp_cron` quite frequently that grabs a batch of 15-25 posts (~100 chunks at a time), sends them to hte ContentOracle AI API embed route, and saves the resultant embeddings.  The status of these requests will be shown in the `wp_coai_post_embed_queue` table.  We will have updated ui, automatic enqueueing of posts for embeddings, error handling, and more to keep the user in the loop on how it is going.  At the end, we should be able to embed libraries of thousands of posts/products without major failures. 

### 1. Adding posts to `wp_coai_post_embed_queue`.
- We will have a button to add all posts of the post types to embed are added to the `wp_coai_post_embed_queue` (assuming they are not already in it, no repeats!).
- Additionally, we will have a button to add all posts of the post types to embed to the `wp_coai_post_embed_queue`that are not embedded already (assuming they are not already in it, no repeats!).
- We may also have a list of posts with a button next to them to add them to the `wp_coai_post_embed_queue` (assuming they are not already in it, no repeats!).

### 2. Scheduling embeds
- There needs to be an option to automatically update the embeddings for posts that have been edited.  Maybe this could replace the checkbox in the post editor.
- Maybe I should just let this queue table drive all embeddings.

### 3. Progress tracking
- Create a new table `wp_coai_post_embed_queue` to track:
  - Job ID
  - Post ID
  - Chunk count?
  - Status (pending, processing, completed, failed)
  - Queued time
  - Start time
  - End time
  - Error count
- No post should have multiple records in the `wp_coai_post_embed_queue`, unless the status is 'complete' or '(failed' and the error count is > 3)

### 4. Batch processing
- Create batches by retrieving the least recently queued posts.
- Batch should contain 20 chunks or less.
- Batch should not contain only part of the chunks from a post, all or nothing.
- Skip posts with no body/chunks, without the records.

### 5. Use existing embedding logic
- Reuse the `bulk_generate_embeddings` method from the `ContentOracleApiConnection` object to send the batches off to the ContentOracle AI api for embedding.
- This method works already, no need to change it.
- Set the start time on the `wp_coai_post_embed_queue` record.
- Update the status of the `wp_coai_post_embed_queue` record.


### 6. Handle results
- Based on the return from the embedding generation, update the status of the `wp_coai_post_embed_queue` record.
- Update the error count on the `wp_coai_post_embed_queue` record, if needed.
- Set the end time on the `wp_coai_post_embed_queue` record.

### 7. Cleanup
- Schedule a job to remove records from the `wp_coai_post_embed_queue` that have both start and end time set, and a status equal to 'completed'.
- Remove all posts from the `wp_coai_post_embed_queue` table with a status equal to complete and an error count greater than 3.

### 8. Error handling
- When something goes wrong, update the status and error count for all the records in `wp_coai_post_embed_queue` that had the error.
- We will retry posts up to 3 times before removing them from the `wp_coai_post_embed_queue`.
- Special handling for rate limiting errors, where we save the current time to an option.  We refrain from submitting any more embeddings until an hour later.

### 9. Integration with existing systems
- Integrate with the meta checkbox on selected post types to add them to the `wp_coai_post_embed_queue`.


## Technical Implementation

### Database Schema

  - Job ID
  - Post ID
  - Chunk count?
  - Status (pending, processing, completed, failed)
  - Queued time
  - Start time
  - End time
  - Error count
```sql

CREATE TABLE wp_coai_post_embed_queue (
    job_id SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL,
    chunk_count INTEGER DEFAULT 0,
    status VARCHAR(20) NOT NULL CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    queued_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    start_time TIMESTAMP,
    end_time TIMESTAMP,
    error_count INTEGER DEFAULT 0
);

```
======================================================

### Background Processing
1. Use WordPress Cron for scheduling
2. Use `wp_coai_post_embed_queue` table as a queue, pulling out the least recently added records first.
3. Ensure it runs asynchronously if possible.

## Security Considerations
1. Escape data going into database.
2. Do not let user's directly make entries.

## Performance Optimization
1. Limit number of database queries.
2. Optimize database queries.
3. Ensure long requests are run asyncronously.
4. Back off when rate limited.

## Monitoring & Maintenance
1. Add logging system, for displaying results of embedding each post.
2. Rework the embeddings page to show current state of `wp_coai_post_embed_queue` queue.
3. Get live progress updates on pending embeds.

## Future Enhancements
1. Parallel processing support.

## Implementation Phases

### Phase 1: Core Functionality
- Table embeddings UI.
    - UI to add all posts of the right type to the queue.
    - UI to add all posts of right type that are not embedded to the queue.
    - UI to add a particular post to queue.
- A class to handle all interactions with the `wp_coai_post_embed_queue` table.
- Extract a batch of posts to embed.
- Send them to COAI API using existing function.
- Scheduled sending of batches to api.


### Phase 2: Enhanced Features
- Cleanup unneeded records from `wp_coai_post_embed_queue` table.
- Options to automatically queue changed posts.
- Options to automatically queue new posts.
- Handle errors and retries.

### Phase 3: Monitoring & Maintenance
- Logging system.
- UI to show the current state of the `wp_coai_post_embed_queue` table.
- Final touch-ups.

## Success Metrics
1. Processing time per post
2. Success rate
3. Error rate
4. System resource usage
5. User satisfaction
6. Embeddings successfully generated on sites with 100s of posts.
