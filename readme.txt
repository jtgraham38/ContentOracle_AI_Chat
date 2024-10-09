=== ContentOracle AI Chat ===
Contributors: Jacob Graham
Tags: wordpress, ai, search, content, rag
Requires at least: 6.5
Tested up to: 6.6.2
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==
The internet is changing. Experts and leaders of major businesses are quickly recognizing the role that generative ai will play in the future of search. Millions of people every day ask artificial intelligence for recommendations, common facts, and how-to knowledge. This trend will continue to grow exponentially in the years to come. However, their are inherent limitations on what AI can and can’t do. It is very good at recalling information, and wording even very complex topics in a way that even a layman can understand. But, it does not possess the specialist knowledge that many online content creators and businesses put into the content they post on their website, nor does it know anything about the things that are going on at that business.

That is where ContentOracle AI Chat comes in. ContentOracle AI Chat bridges the gap between the unique insights your organization offers in your blog content and the power of AI to clearly and concisely get site visitors the information they are looking for. What results is a user experience that doesn’t give vistors a generic answer, but rather an answer uniquely tailored based on your content. Add to this the fact that our AI can recommend relevant posts, products, events, and more based on a goal you set, semantic content anaysis, and a suite of analytic features, and you’ve got a game-changing addition to your user's experience on your website.

Note: this plugin requires you to make a ContentOracle AI Chat account in order to make the plugin work.  More details below...

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to __URL_HERE__, and create an account by clicking register.  After registering, you should be redirected to the dashboard.
4. Navigate to the tokens tab.  Click the plus button to create a new token.  Give the token a name (the name doesn't matter, it is a label for your own purposes).  Expiration date can be set or left empty.
5. Copy the token to your clipboard.  We'll need it later.
6. Navigate to the subscription tab next.  Enter your billing information.  This is required for the chat functionality to work on your site.
7. On your WordPress site, navigate to ContentOracle > Settings.  Paste the token into the "ContentOracle API Token" field.
8. An you're done!  Now, simply place the blocks provided by the plugin, and your users will be able to chat with your content using ContentOracle AI!

== Frequently Asked Questions ==
= How do I get a ContentOracle API key? =
See the installation instructions.

= How do I customize the way the ai responds to messages? =
All you need to do is edit the prompt settings.  In the admin dashboard, navigate to ContentOracle > Prompt.  From there, you can edit the types of posts the plugin will consider in its responses, specify it's tone and jargon, give it a prompt telling it your goal, and providing it with important information it needs that won't be included in you content.

= How does billing work? =
ContentOracle AI bills on a usage-based basis.  So, the more your users interact with the AI bot, the more you will be billed.  Billing details can be found by visiting __URL_HERE__.  A spend limit can be set in your ContentOracle account dashboard 

= What does the AI Search block do? =
The AI Search block is designed to connect your users with your AI Chat block from anywhere on your website.  When the user searches something in the AI Search block, they will be redirected to your main AI search page, where your AI Chat block should reside.  Their search query will be preemptively sent to the AI to kick off the conversation.

== 3rd-Party Services ==
This app makes use of the ContentOracle AI api to process user messages, index website content, and generate chat responses.  The url of the api is: https://contentoracle.jacob-t-graham.com.  Data sent to the api includes messages the user enters into chat blocks, content from the website (posts, pages, products, etc.), server and client domains and ips (to prevent abuse), and analytic data to enhance the performance of the application.  Messages and site content may be stored and indexed for debugging and feature development purposes. ContentOracle AI is an api that is used on a pay-as-you-go by enrolled sites.  The API is required to make the blocks supplied by this plugin work, and allows site visitors to use an ai chat feature to interact witht he information in your content in a more intuitive and lively way.

== 3rd-Party Libraries ==
This plugin makes use of the following third-party libraries and services:
- AlpineJS: a small javascript library for introducing reactive data to the ui. https://github.com/alpinejs/alpine
- Floating UI: a small javascript library that handles dynamically creating and positioning tooltips. https://github.com/floating-ui/floating-ui
- PHP NLP Tools: a php library for natural language processing, used to help find the most relevant content to a message. https://github.com/angeloskath/php-nlp-tools
- Marked: a javascript library for parsing markdown strings to html.
- DOMPurify: a javascript, DOM only HTML XSS sanitizer.

== Screenshots ==
1. This is the first screenshot.
2. This is the second screenshot.

== Changelog ==
= 1.0.0 =
* Initial release

== Upgrade Notice ==
= 1.0.0 =
Upgrade notice for users of previous versions.

== Screenshots ==
1. This is the first screenshot.
2. This is the second screenshot.

== Changelog ==
= 1.0.0 =
* Initial release

== Upgrade Notice ==
= 1.0.0 =
Upgrade notice for users of previous versions.