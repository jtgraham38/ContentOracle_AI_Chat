/**
 * WordPress dependencies
 */
import Alpine from 'alpinejs';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import InlineCitationArtifact from './artifacts/inline_citation_artifact';
import FeaturedContentArtifact from './artifacts/featured_content_artifact';

//set the alpine prefix (default is x-)
Alpine.prefix('coai-x-')

//initialize alpinejs
Alpine.data('contentoracle_ai_chat', () => ({
	userMsg: "",
	apiBaseUrl: "",	//will be filled in by the block via php
	conversation: [],
	loading: false,
	error: "",
	chatNonce: "",	//will be filled in by the block via php
	stream_responses: true,
	scrollBlockIntoView: false,
	featured_content_border_classes: "",
	featured_content_button_classes: "",
	init() {
		console.log('init chat!!!');
		//load the rest url into the apiBaseUrl from the data-contentoracle_rest_url attribute
		this.apiBaseUrl = this.$el.getAttribute('data-contentoracle_rest_url');
		this.chatNonce = this.$el.getAttribute('data-contentoracle_chat_nonce');
		this.stream_responses = this.$el.getAttribute('data-contentoracle_stream_responses');
		this.scrollBlockIntoView = this.$el.getAttribute('data-contentoracle_scroll_block_into_view');
		this.featured_content_border_classes = this.$el.getAttribute('data-contentoracle_featured_content_border_classes').split(" ");
		this.featured_content_button_classes = this.$el.getAttribute('data-contentoracle_featured_content_button_classes').split(" ");
		this.chat_message_seeder_items = JSON.parse(this.$el.getAttribute('data-contentoracle_chat_message_seeder_items'));

		//scroll to the top of the bottommost chat when the conversation updates
		this.$watch('conversation', () => {
			this.scrollToBottomMostChat(); // Call the function when conversation updates
		});

		//scroll to the bottom of the chat when the loading state updates
		this.$watch('loading', () => {
			if (this.loading) {
				this.scrollToBottom(); // Call the function when loading updates
			}
		});

		//scroll to the bottom of the chat when the error state updates
		this.$watch('error', () => {
			if (this.error) {
				this.scrollToBottom(); // Call the function when error updates
			}
		});

		//preemptively add the search query to the conversation, if it exists
		const urlParams = new URLSearchParams(window.location.search);
		const searchQuery = urlParams.get('contentoracle_ai_search');
		if (searchQuery) {
			if (this.stream_responses) {
				this.sendStreamed(searchQuery, event)
			}
			else {
				this.send(searchQuery, event)
			}
		}

		//scroll this block into view on page load if it is set to do so
		if (this.scrollBlockIntoView)
			this.scrollToBlock();
	},
	//sends a message using the input value
	async sendMessage(event) {
		event.preventDefault();	//prevent a page refresh due to form tag

		//ensure there is a message
		if (this.userMsg === '') {
			//NOTE: the validaity doesnt work, for some reason!
			this.$refs.chatInput.reportValidity();
			return
		}

		//ensure not already loading
		if (this.loading) {
			this.$refs.chatInput.setCustomValidity('Please wait, I am trying to come up with a response!');
			return
		}

		//ensure error is not set
		if (this.error) {
			return
		}

		//send the message
		if (this.stream_responses) {
			await this.sendStreamed(this.userMsg, event);
		}
		else {
			await this.send(this.userMsg, event);
		}

	},
	//sends a message to the server and gets an ai response back
	async send(msg) {
		//set loading state, after a slight delay
		setTimeout(
			() => { this.loading = true; }, 1000
		);

		//prepare the request headers and body
		const url = this.apiBaseUrl + 'contentoracle-ai-chat/v1/chat';
		const headers = {
			'Content-Type': 'application/json',
			'X-WP-Nonce': this.chatNonce
		}
		const contextConversation = this.getConversationWithContext();
		const data = {
			message: msg,
			conversation: contextConversation.length <= 10 ? contextConversation : contextConversation.slice(contextConversation.length - 10),
		};
		//build the request
		const options = {
			method: 'POST',
			headers: headers,
			body: JSON.stringify(data),
		};

		//add the user's message to conversation 
		//this is done here so that the message is not already in the conversation when the message is sent to the
		//coai api, because if it is, the api will append it again, and the conversation will have two user messages in a row
		this.conversation.push({
			role: 'user',
			content: msg,
		});

		//send the request
		const request = await fetch(url, options)
		const json = await request.json();

		//handle the response
		if (json.error) {
			//this is an error that might be set in the wp api, because it is not a part of the response
			this.handleErrorResponse(json);
		}
		else {
			try {
				//add the response to the conversation
				//this is done here so that the message is not already in the conversation when the message is sent to the
				//coai api, because if it is, the api will append it again, and the conversation will have two user messages in a row
				const placeholder_response = {
					role: 'assistant',
					raw_content: json.response,
					content: "",
					content_used: [],
					content_supplied: json.content_supplied,
					action: json.action,
					engineered_prompt: json.engineered_prompt,
				}
				this.conversation.push(placeholder_response);

				//parse the coai artifacts (updates the raw content with the parsed artifacts)
				const artifacts_parsed_content = this.renderArtifacts(this.conversation[this.conversation.length - 1]);

				//render and sanitize the markdown in the chat's raw content
				const md_rendered_content = DOMPurify.sanitize(
					marked.parse(
						artifacts_parsed_content
					)
				);

				//now, after all parses and transformations, set the chat content to the rendered chat
				this.conversation[this.conversation.length - 1].content = md_rendered_content;

				console.log("conversation", this.conversation);
			}
			catch (e) {
				//don't use handleErrorResponse, because this is not the result of a malformed/bad response
				this.error = "An error occurred while streaming in the response.";
				console.error(e);
			}
		}

		//set loading state
		this.loading = false;

		//clear out input
		this.userMsg = '';
	},

	async sendStreamed(msg) {
		//set loading state, after a slight delay
		setTimeout(
			() => { this.loading = true; }, 1000
		);

		//add the user's message to conversation 
		//this is done here so that the message is not already in the conversation when the message is sent to the
		//coai api, because if it is, the api will append it again, and the conversation will have two user messages in a row
		this.conversation.push({
			role: 'user',
			content: msg,
		});

		//initialize the xhr request
		const xhr = new XMLHttpRequest();
		xhr.open(
			"POST",			//TODO: change later!
			this.apiBaseUrl + 'contentoracle-ai-chat/v1/chat/stream',//&message=' + msg,
			true
		);

		//set the headers
		xhr.setRequestHeader('Content-Type', 'application/json');
		xhr.setRequestHeader('X-WP-Nonce', this.chatNonce);

		//set streaming handler
		let finger = 0;
		let raw_response = "";	//store the raw response, so we don't try to double parse the markdown
		xhr.onprogress = function (event) {
			//set loading state
			this.loading = false;

			//get the response from the event
			const _response = event.target.response;

			//split on separator, the first "private use character" is the separator
			let responses = _response.split("\u{E000}").slice(finger);
			finger += responses.length - 1;

			//filter out empty strings
			responses = responses.filter((response) => response.length > 0);

			//iterate through the responses, parsing them
			responses.map((response) => {

				//push a chat bubble to the conversation for the ai response to fill, if one has not already been added
				if (this.conversation.length == 0 || this.conversation[this.conversation.length - 1].role != 'assistant') {
					this.conversation.push({
						role: 'assistant',
						content: "",
						content_used: [],
						content_supplied: [],
						action: null,
						engineered_prompt: null,
					});
				}

				//parse the response
				let parsed;
				try {
					parsed = JSON.parse(response);
				} catch (e) {
					//don't use handleErrorResponse, because this is not the result of a response
					//this.error = "The response could not be parsed.";	//commenting this to not show the error result, because the response still seems to generate alright
					console.error(e);
					return;
				}

				//handle the response
				if (parsed?.error) {
					this.handleErrorResponse(parsed);
				}
				//check if this is the action response
				else if (parsed?.action) {
					//handle the action
					if (!this.conversation?.action && this.conversation[this.conversation.length - 1].role == 'assistant') {
						this.conversation[this.conversation.length - 1].action = parsed.action;
					}
				}
				//else if it is the context supplied response
				else if (parsed?.content_supplied) {
					//set the context supplied on the ai's message
					if (!this.conversation?.action && this.conversation[this.conversation.length - 1].role == 'assistant') {
						this.conversation[this.conversation.length - 1].content_supplied = parsed.content_supplied;
					}
				}
				//else if it is the engineered input response
				else if (parsed?.engineered_prompt) {
					//set the engineered input on the ai's message
					if (!this.conversation?.action && this.conversation[this.conversation.length - 1].role == 'assistant') {
						this.conversation[this.conversation.length - 1].engineered_prompt = parsed.engineered_prompt;
					}
				}
				//otherwise, extract the generated message fragment
				else {
					//ensure the last message is not finished yet and it is an assistant message
					if (!this.conversation?.action && this.conversation[this.conversation.length - 1].role == 'assistant') {
						//append the fragment to the last message
						if (parsed?.generated?.message.length > 0) {
							raw_response += parsed?.generated?.message;
						}
					}

					//add the raw (unparsed) response to the last message
					this.conversation[this.conversation.length - 1].raw_content = raw_response;

					//parse the coai artifacts (updates the raw content with the parsed artifacts)
					const artifacts_parsed_content = this.renderArtifacts(this.conversation[this.conversation.length - 1]);

					//render and sanitize the markdown in the chat's raw content
					const md_rendered_content = DOMPurify.sanitize(
						marked.parse(
							artifacts_parsed_content
						)
					);

					//now, after all parses and transformations, set the chat content to the rendered chat
					this.conversation[this.conversation.length - 1].content = md_rendered_content;
				}
			})
		}.bind(this);	//IMPORTANT: bind the this context to the alpine object, otherwise it will be the xhr object

		//set error handler
		xhr.onerror = function () {
			console.error(event);
		};

		//after the request is done
		xhr.onload = function () {
			console.log("conversation", this.conversation);
		}.bind(this);	//IMPORTANT: bind the this context to the alpine object, otherwise it will be the xhr object

		//send the request with the body
		const contextConversation = this.getConversationWithContext();
		const data = {
			message: msg,
			conversation: contextConversation.length <= 10 ? contextConversation : contextConversation.slice(contextConversation.length - 10),
		};
		xhr.send(JSON.stringify(data));
	},

	//looks at the raw content of a chat, and replaces it with the raw content with artifacts parsed and rendered
	renderArtifacts(chat) {

		//gregex for all artifact tags
		const regex = /<coai-artifact[^>]*>(.*?)<\/coai-artifact>/g;

		//get the raw content of the chat
		const raw_content = chat.raw_content;

		//render each artifact
		const artifacts_rendered = raw_content.replace(regex, (match, innerText) => {
			//parse the tag, and find it's type
			const parser = new DOMParser();
			const doc = parser.parseFromString(match, 'text/html');
			const artifact = doc.querySelector('coai-artifact');
			const artifact_type = artifact.getAttribute('artifact_type');

			//get other information about the chat
			const content_supplied = chat.content_supplied;	//for inline citations

			//use the artifact parsers to parse each artifact based on it's type
			let rendered;
			switch (artifact_type) {
				case 'inline_citation':
					let inline_citation = new InlineCitationArtifact(artifact);
					rendered = inline_citation.render(chat.content_supplied, chat.content_used);	//NOTE: these are modified by reference
					return rendered.outerHTML;
				case 'featured_content':
					let featured_content = new FeaturedContentArtifact(artifact);
					rendered = featured_content.render(chat.content_supplied, this.featured_content_border_classes, this.featured_content_button_classes);	//NOTE: these are modified by reference
					return rendered.outerHTML;
				default:
					return innerText;
			}
		})

		return artifacts_rendered;
	},
	//scrolls to the bottom of the chat
	scrollToBottom(event) {
		const chatContainer = this.$refs.chatBody;
		chatContainer.scrollTop = chatContainer.scrollHeight;
	},
	//scrolls to the top of the bottommost assistant chat
	scrollToBottomMostChat(event) {
	},
	//performs all tasks that need to be performed when an error response is received
	handleErrorResponse(error) {
		this.error = `Error ${error.error.error || error.error_code}: "${error.error_msg}".`;	//|| output hte unauthorized error properly
		console.error(`Error originates from ${error.error_source == "coai" ? "ContentOracle AI API" : "WordPress API"}.`, error.error);
	},

	//get the conversations, with the context prepended to the user message
	getConversationWithContext() {
		//make a copy of the conversation, to avoid state mutation
		const conversation = JSON.parse(JSON.stringify(this.conversation));
		let content_used = null;
		let content_used_str = null;

		//iterate through the conversation, changing every user message's content to the engineered input from the assistant message after it
		for (let i = conversation.length - 1; i >= 0; i--) {
			//if this is a user message
			if (conversation[i].role == 'user') {
				//if there is an assistant message after this one
				if (i + 1 < conversation.length && conversation[i + 1].role == 'assistant') {
					//set the user message content to the assistant message's engineered input
					if (conversation[i + 1].engineered_prompt) {
						conversation[i].content = conversation[i + 1].engineered_prompt
					}
				}
			}
		}

		//return the conversation
		return conversation;
	},

	//scroll the window to this block
	scrollToBlock(event) {
		//scroll the block into view
		this.$el.scrollIntoView({ behavior: "smooth" });
	},

	//use one of the chat message seeder items as the user's message
	useChatMessageSeederItem(msg) {
		//send the message (note, the calls below are async, KEEP THIS IN MIND)
		if (this.stream_responses) {
			this.sendStreamed(msg, {});
		}
		else {
			this.send(msg, {});
		}
	}
})
)

Alpine.start();