=== ContentOracle AI Chat ===
Contributors: jtgraham38
Tags: ai, search, content, rag, chat
Requires at least: 6.5
Tested up to: 6.7.1
Stable tag: 1.4.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

ContentOracle AI Search seamlessly blends the power of generative AI with your website’s unique content.

== Description ==

ContentOracle AI Chat adds powerful no-code, fully-customizable, content-aware ai chat features to your site.  By combining the power of ai with the insights provided by your site content, ContentOracle AI Chat brings your users an unrivalled chat experience.

https://youtu.be/W9pr_weJHWA

The internet is changing. Experts and leaders of major businesses are quickly recognizing the role that generative ai will play in the future of search. Millions of people every day ask artificial intelligence for recommendations, common facts, and how-to knowledge. This trend will continue to grow exponentially in the years to come. However, their are inherent limitations on what AI can and can’t do. It is very good at recalling information, and wording even very complex topics in a way that even a layman can understand. But, it does not possess the specialist knowledge that many online content creators and businesses put into the content they post on their website, nor does it know anything about the things that are going on at that business.

That is where ContentOracle AI Chat comes in. ContentOracle AI Chat bridges the gap between the unique insights your organization offers in your blog content and the power of AI to clearly and concisely get site visitors the information they are looking for. What results is a user experience that doesn’t give visitors a generic answer, but rather an answer uniquely tailored based on your content. Add to this the fact that our AI can recommend relevant posts, products, events, and more based on a goal you set, semantic content matching, and a suite of analytic features, and you’ve got a game-changing addition to your site's UX.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/contentoracle_ai_chat` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to https://app.contentoracleai.com, and create an account by clicking register.  After registering, you should be redirected to the dashboard.
4. Navigate to the tokens tab.  Click the plus button to create a new token.  Give the token a name (the name doesn't matter, it is a label for your own purposes).  Expiration date can be set or left empty.
5. Copy the token to your clipboard.  We'll need it later.
6. Navigate to the subscription tab next.  Enter your billing information.  This is required for the chat functionality to work on your site.  You have a short free trial if you don't want to enter your billing information yet.
7. On your WordPress site, navigate to ContentOracle > Settings.  Paste the token into the "ContentOracle API Token" field.
8. An you're done!  Now, simply place the blocks provided by the plugin, and your users will be able to chat with your content using ContentOracle AI!

== Frequently Asked Questions ==

= What makes this plugin different than ChatGPT or Gemini? =
The key difference between generic ai chat services and this plugin (apart from the chat being embedded directly on your site with the plugin) is that ContentOracle AI Chat is an implementation of retrieval-augmented generation (RAG) on top of your WordPress site content.  This means that ContentOracle AI Chat can use the information from your posts, products, and other content in its responses to your users.  Meanwhile, chat apps like ChatGPT or Gemini onlyhave generic knowledge, not specifi information like there is in your site content.

= How do I customize the way the ai responds to messages? =
All you need to do is edit the prompt settings.  In the admin dashboard, navigate to ContentOracle > Prompt.  From there, you can edit the types of posts the plugin will consider in its responses, specify it's tone and jargon, give it a prompt telling it your goal, and providing it with important information it needs that won't be included in you content.

= How do I add Chat UI features to my site? =
Simply using the block editor!  Just open the page you want to place the chat window on, and use our provided ai chat block to create an ai chat on your site.

= How do I get a ContentOracle API key? =
See the installation instructions.

= How does billing work? =
ContentOracle AI Chat is billed monthly on a usage-based basis.  So, the more your users chat with your AI chatbot, the higher your bill will be  Billing details can be found by visiting https://contentoracleai.com/pricing.  A spend limit can be set in your ContentOracle account dashboard.  There is also a free trial where you can send a certain number of messages without providing billing info, perfect for giving the app a try.

= What does the AI Search block do? =
The AI Search block is designed to connect your users with your AI Chat block from anywhere on your website.  When the user searches something in the AI Search block, they will be redirected to your main AI search page, where your AI Chat block should reside (this page is configurable in the plugin settings).  Their search query will be preemptively sent to the AI to kick off the conversation.

== 3rd-Party Services ==
This app makes use of the ContentOracle AI api to process user messages, index website content, and generate chat responses.  The url of the api is: https://app.contentoracleai.com.  Here is the url of it's terms of service: https://app.contentoracleai.com/terms.  Data sent to the api includes messages the user enters into chat blocks, content from the website (posts, pages, products, etc.), server and client domains and ips (to prevent abuse), and analytic data to enhance the performance of the application.  Messages and site content may be stored and indexed for debugging and feature development purposes. ContentOracle AI is an api that is used on a pay-as-you-go basis by enrolled sites.  The API is required to make the blocks supplied by this plugin work, and allows site visitors to use an ai chat feature to interact with the information in your content in a more intuitive and lively way.

== 3rd-Party Libraries ==
This plugin makes use of the following third-party libraries and services:
- AlpineJS: a small javascript library for introducing reactive data to the ui. https://github.com/alpinejs/alpine
- Floating UI: a small javascript library that handles dynamically creating and positioning tooltips. https://github.com/floating-ui/floating-ui
- PHP NLP Tools: a php library for natural language processing, used to help find the most relevant content to a message. https://github.com/angeloskath/php-nlp-tools
- Marked: a javascript library for parsing markdown strings to html.
- DOMPurify: a javascript, DOM only HTML XSS sanitizer.

== Screenshots ==
1. An example of an ai chat with inline citations from site content.
2. The ai recommended an action, a relavent post to read, based on the conversation.
3. The ai searchbar.
4. Main settings menu of the plugin.
5. Customizing the way the ai repsonds to user queries.
6. The embeddings explorer, used for generating embeddings for semantic text similarity search.
7. Customizing an ai chat block to match a site in the block editor.
8. The customized block on the frontend of the site.

== Changelog ==
= 1.0.0 =
* Initial release

= 1.1.0 =
* Changed database prefix, and changed admin menu slugs.

= 1.1.1 =
* Modified similarity calculation to ensure compatibility with most databases.

= 1.2.0 =
* Implemented streaming of ai chat responses.
* Fixed bugs with semantic text matching.
* Vastly improved error handling and reporting.
* More user-friendly auto-scrolling in the chat block.

= 1.3.0 =
* Artifact rendering added to make chats more interactive and useful.
* Improve conversation structure to improve agent memory.

= 1.3.1 = 
* Fix a bug in response streaming.

= 1.4.0 =
* Input should no longer be disabled upon the occurence of an error.
* Inline content recommendations to further enhance the conversation with the ai agent.
* Add option to auto-scroll directly to the chat block on a page when the page loads.

== Upgrade Notice ==
= 1.0.0 =
Upgrade notice for users of previous versions.

= 1.1.0 =
You will need to regenerate embeddings, and reset all settings, including your api key, since the database prefix changed.  You can also use a database explorer to get you old exact settings back.

= 1.2.0 = 
To use response streaming, you must use the checkbox provided on the chat block.
Semantic text matching is currently experimental.  Use it, but its performance is not yet guaranteed.

= 1.3.0 =
Please install this update.  It correctly handles artifacts added to the api.  Things like inline citations will be broken if you do not update.

= 1.4.0 =
Please upgrade to properly handle inline content recommendations.
