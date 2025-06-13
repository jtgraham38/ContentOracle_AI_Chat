=== ContentOracle AI Chat ===
Contributors: jtgraham38  
Tags: ai, search, content, rag, chat  
Requires at least: 6.5  
Tested up to: 6.8.1
Stable tag: 1.10.4
License: GPLv3 or later  
License URI: https://www.gnu.org/licenses/gpl-3.0.html  

ContentOracle AI Search seamlessly blends the power of generative AI with your website’s unique content.

== Description ==

ContentOracle AI Chat adds powerful no-code, fully-customizable, content-aware AI chat features to your site. By combining the power of AI with the insights provided by your site content, ContentOracle AI Chat brings your users an unrivaled chat experience.

== Demo! ==
**Try it Here:** [demo.contentoracleai.com/](https://demo.contentoracleai.com/)  

== Intro ==

https://youtu.be/W9pr_weJHWA

The internet is changing. Millions of people every day ask artificial intelligence for recommendations, facts, and how-to knowledge. However, AI lacks the specialist knowledge that many online content creators and businesses provide.  

ContentOracle AI Chat bridges this gap by combining your unique content with AI's capabilities. It delivers tailored answers based on your content, recommends relevant posts, products, and events, and provides analytics to enhance your site's user experience.

== Features ==
* Content-aware, retrieval augmented ai chat for site visitors.
* Semantic text matching using text embeddings.
* Chatbot cites it's sources in its messages.
* Inline content recommendations in chat bubbles.
* Full support and customizability with the block editor.
* No code or third-party integrations required!
* Compatible with major page builders.
* AI agent speec habits customizable.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/contentoracle_ai_chat` directory, or install the plugin through the WordPress plugins screen directly.  
2. Activate the plugin through the 'Plugins' screen in WordPress.  
3. Go to [app.contentoracleai.com](https://app.contentoracleai.com) and create an account.  
4. Navigate to the **Tokens** tab, create a new token, and copy it to your clipboard.  
5. Navigate to the **Subscription** tab, enter your billing information, or use the free trial.  
6. On your WordPress site, go to **ContentOracle > Settings** and paste the token into the "ContentOracle API Token" field.  
7. Place the provided blocks on your site, and you're done!  

== Frequently Asked Questions ==

= What makes this plugin different than ChatGPT or Gemini? =  
ContentOracle AI Chat uses retrieval-augmented generation (RAG) to incorporate your WordPress site content into AI responses, unlike generic AI chat services.

= Do I need to integrate with any third-party applications? =
No!  Just link your site to your ContentOracle AI account, and we handle the rest.  No need to integrate directly with LLM apis or third-party vector databases!

= How do I customize the AI's responses? =  
Navigate to **ContentOracle > Prompt** in the admin dashboard to edit prompt settings, tone, jargon, and more.

= How do I add Chat UI features to my site? =  
Use the block editor to place the AI chat block on your desired page.

= How do I get a ContentOracle API key? =  
Follow the installation instructions.

= How does billing work? =  
Billing is usage-based and monthly. Visit [https://contentoracleai.com/pricing](https://contentoracleai.com/pricing) for details. A free trial is available.

= What does the AI Search block do? =  
The AI Search block redirects users to your AI chat page with their query preloaded, enhancing the search experience.

== 3rd-Party Services ==

This plugin uses the ContentOracle AI API to process user messages, index website content, and generate responses.  
- API URL: [https://app.contentoracleai.com](https://app.contentoracleai.com)  
- Terms of Service: [https://app.contentoracleai.com/terms](https://app.contentoracleai.com/terms)  

Data sent to the API includes user messages, site content, server/client domains, and analytics. Messages and content may be stored for debugging and feature development.

== 3rd-Party Libraries ==

This plugin uses the following libraries:  
- **AlpineJS**: Reactive data for UI ([GitHub](https://github.com/alpinejs/alpine))  
- **Floating UI**: Tooltip positioning ([GitHub](https://github.com/floating-ui/floating-ui))  
- **PHP NLP Tools**: Natural language processing ([GitHub](https://github.com/angeloskath/php-nlp-tools))  
- **Marked**: Markdown to HTML parser  
- **DOMPurify**: HTML XSS sanitizer  

== Screenshots ==

1. AI chat with inline citations from site content.  
2. AI recommending a relevant post based on the conversation.  
3. AI search bar.  
4. Main settings menu of the plugin.  
5. Customizing AI responses in the admin dashboard.  
6. Embeddings explorer for semantic text similarity search.  
7. Customizing an AI chat block in the block editor.  
8. Customized block on the frontend.

== Changelog ==

= 1.10.4 =
* Fix memory issues for large sites.

= 1.10.3 =
* Optimizations for large content libraries.

= 1.10.2 =
* Adjusted the ordering of posts shown in the embedding queue table.
* Bug fixes for the bulk embedding process.

= 1.10.1 =
* Fixed a bug that prevented posts in the embedding queue from showing a completed status.
* Fixed ordering in embedding queue.
* Fixed a bug that would enqueue posts with no body for embedding generation.

= 1.10.0 =
* Added a setup wizard to make getting started a breeze.
* Made a host of upgrades to the bulk embedding system to make it scale to huge content libraries.
* UI improvements to improve the mobile experience.
* Bug fixes.

= 1.9.1 =
* Fixed a bug that caused embeddings to be consumed without use.

= 1.9.0 =
* Revamped the ui on the embeddings admin tab.
* Overhauled the system for embedding site content.
* Added more options for ai tone and jargon.
* Bug fixes.

= 1.8.1 =
* Bug fix in bulk embed route.

= 1.8.0 =
* Fixed a bug in the bulk embedding route.
* Fixed a bug in the post meta selector.
* Added a way to place blocks on the site using shortcodes.

= 1.7.2 =
* Fixed a bug that prevented non-authenticated users from chatting.
* Fix a bug with error handling on streamed responses.
* Show a notice when free users run out of trial usage.

= 1.7.1 =
* Fix a bug involving an incorrect id on embedding generation.

= 1.7.0 =
* Built a dedicated REST api route to generate embeddings for content.
* Improve UI for embedding generation.
* Made embedding usage the default choice for the plugin.
* Add nonce verification on all api routes.
* Delete embeddings for posts that have been deleted.
* Intuitively redesigned several elements of the administrative UI.

= 1.6.0 =
* Added a greeter section to help your visitors get the convesation started.
* You can now configure suggested starting messages for your visitors.
* Made button background and text colors configurable independently of border and text styles in the chat block.
* Fixed several styling bugs.

= 1.5.2 =
* Fix outputting of content supplied.
* Updates to readme.txt

= 1.5.1 =  
* Fix a small error display bug.  

= 1.5.0 =  
* Add support for sending certain post meta to AI API.  
* Fix issue with unpublished posts in response generation.  
* Fix bug in WP Admin popup menu.  
* Unrender unsupported artifacts.  

= 1.4.2 =  
* Fix to the response streaming feature.  

= 1.4.1 =  
* Various bug fixes.  

= 1.4.0 =  
* Inline content recommendations.  
* Auto-scroll to chat block on page load.  
* Improved search bar notification.  

= 1.3.1 =  
* Fix a bug in response streaming.  

= 1.3.0 =  
* Artifact rendering for interactive chats.  
* Improved conversation structure.  

= 1.2.0 =  
* Implemented streaming of AI chat responses.  
* Fixed bugs with semantic text matching.  
* Improved error handling and auto-scrolling.  

= 1.1.1 =  
* Modified similarity calculation for database compatibility.  

= 1.1.0 =  
* Changed database prefix and admin menu slugs.  

= 1.0.0 =  
* Initial release.  

== Upgrade Notice ==

= 1.10.4 =
Fix memory issues on large sites.

= 1.10.3 = 
Better support for large content libraries.

= 1.10.2 =
Bug fixes and quality-of-life improvements.

= 1.10.1 =
Upgrade to ensure embeddings are only generated for posts with a body.

= 1.10.0 =
Upgrade now for a setup wizard, and improvements to the embedding system and frontend ui.

= 1.9.1 =
Update to preserve your free embedding usage.

= 1.9.0 =
Support for embedding large content libraries added.

= 1.8.1 =
Fix bug in bulk embed route.

= 1.8.0 =
Upgrade for bug fixes and for shortcode support.

= 1.7.2 =
Please upgrade to fix the permission callback to allow guests to chat.

= 1.7.1 =
Update to fix a bug with bulk embedding generation.

= 1.7.0 =
Full compatibility, upgrade for improved UX

= 1.6.0 =
Full reverse compatibility, but upgrade for new features.

= 1.5.0 =  
Upgrade to ensure compatibility with future artifacts.  

= 1.4.0 =  
Upgrade to properly handle inline content recommendations.  

= 1.3.0 =  
Install this update to handle artifacts like inline citations.  

= 1.2.0 =  
Enable response streaming via the chat block settings.  

= 1.1.0 =  
Regenerate embeddings and reset settings due to database prefix changes.  
