/**
 * WordPress dependencies
 */
import Alpine from 'alpinejs';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

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
	init() {
		console.log('init chat!!!');
		//load the rest url into the apiBaseUrl from the data-contentoracle_rest_url attribute
		this.apiBaseUrl = this.$el.getAttribute('data-contentoracle_rest_url');
		this.chatNonce = this.$el.getAttribute('data-contentoracle_chat_nonce');
		this.stream_responses = this.$el.getAttribute('data-contentoracle_stream_responses');

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
	},
	//sends a message using the input value
	async sendMessage( event ) {
		event.preventDefault();	//prevent a page refresh due to form tag
		
		//ensure there is a message
		if ( this.userMsg === '' ) {
			//NOTE: the validaity doesnt work, for some reason!
			this.$refs.chatInput.reportValidity();
			return
		}

		//ensure not already loading
		if ( this.loading ) {
			this.$refs.chatInput.setCustomValidity( 'Please wait, I am trying to come up with a response!' );
			return
		}

		//ensure error is not set
		if ( this.error ) {
			return
		}

		//send the message
		if (this.stream_responses) {
			await this.sendStreamed( this.userMsg, event );
		}
		else{
			await this.send( this.userMsg, event );
		}

	},
	//sends a message to the server and gets an ai response back
	async send( msg ) {
		//set loading state, after a slight delay
		setTimeout( 
			() => { this.loading = true; }, 1000 
		);
		
		//prepare the request headers and body
		const url = this.apiBaseUrl + 'contentoracle-ai-chat/v1/chat';
		const headers = {
			'Content-Type': 'application/json',
			'COAI-X-WP-Nonce': this.chatNonce
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
			body: JSON.stringify( data ),
		};

		//add the user's message to conversation 
		//this is done here so that the message is not already in the conversation when the message is sent to the
		//coai api, because if it is, the api will append it again, and the conversation will have two user messages in a row
		this.conversation.push( {
			role: 'user',
			content: msg,
		} );

		//send the request
		const request = await fetch(url, options)
		const json = await request.json();

		//handle the response
		if ( json.error ){
			//this is an error that might be set in the wp api, because it is not a part of the response
			this.handleErrorResponse(json);
		}
		else {
			try {
				//add the response to the conversation
				//this is done here so that the message is not already in the conversation when the message is sent to the
				//coai api, because if it is, the api will append it again, and the conversation will have two user messages in a row
				const placheholder_response = {
					role: 'assistant',
					raw_content: json.response,
					content: "",
					context_used: [],
					context_supplied: json.context_supplied,
					action: json.action
				}
				this.conversation.push(placheholder_response);

				//render the main idea
				const main_idea_chat = this.addMainIdea(placheholder_response);

				//render the citations
				const cited_chat = this.addCitations(main_idea_chat);

				//render and sanitize the markdown
				cited_chat.content = DOMPurify.sanitize(marked.parse(cited_chat.raw_content));

				//replace the placheholder with the rendered chat
				this.conversation[this.conversation.length - 1] = cited_chat;

				console.log("conversation", this.conversation);
			}
			catch(e){
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
		this.conversation.push( {
			role: 'user',
			content: msg,
		} );

		//initialize the xhr request
		const xhr = new XMLHttpRequest();
		xhr.open(
			"POST",			//TODO: change later!
			this.apiBaseUrl + 'contentoracle-ai-chat/v1/chat/stream',//&message=' + msg,
			true
		);

		//set the headers
		xhr.setRequestHeader('Content-Type', 'application/json');
		xhr.setRequestHeader('COAI-X-WP-Nonce', this.chatNonce);

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
			finger += responses.length-1;

			//filter out empty strings
			responses = responses.filter((response) => response.length > 0);

			//iterate through the responses, parsing them
			responses.map((response) => {

				//push a chat bubble to the conversation for the ai response to fill, if one has not already been added
				if (this.conversation.length == 0 || this.conversation[this.conversation.length - 1].role != 'assistant') {
					this.conversation.push({
						role: 'assistant',
						content: "",
						context_used: [],
						context_supplied: [],
						action: null
					});
				}

				//parse the response
				let parsed;
				try {
					parsed = JSON.parse(response);
				} catch (e) {
					//don't use handleErrorResponse, because this is not the result of a response
					this.error = "The response could not be parsed.";
					console.error(e);
					return;
				}

				//handle the response
				if ( parsed?.error ){
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
				else if (parsed?.context_supplied) {
					//set the context supplied on the ai's message
					if (!this.conversation?.action && this.conversation[this.conversation.length - 1].role == 'assistant') {
						this.conversation[this.conversation.length - 1].context_supplied = parsed.context_supplied;
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

					//render the main idea
					const main_idea_chat = this.addMainIdea(this.conversation[this.conversation.length - 1]);

					//add the in-text citations to the last message
					//render the citations
					const cited_chat = this.addCitations(main_idea_chat);
					
					//render and sanitize the markdown
					const rendered_chat_content = DOMPurify.sanitize(
						marked.parse(
							cited_chat.raw_content
						)
					);
							
					//the content raw content has been cooked!
					const rendered_cited_chat = cited_chat;
					rendered_cited_chat.content = rendered_chat_content;
					
					//now, after all parses and transformations, set the chat content to the rendered chat
					this.conversation[this.conversation.length - 1] = cited_chat;
				}
			} )
		}.bind(this);	//IMPORTANT: bind the this context to the alpine object, otherwise it will be the xhr object

		//set error handler
		xhr.onerror = function() {
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

	//ads inline citations and citations section to an ai chat response
	addCitations(chat) {
		//begin by making a SHALLOW copy of the original chat object
		//I'd rather return a copy than modify state directly
		const copy = Object.assign({}, chat);

		//find the current citation label
		const num_labelled = Object.entries(chat.context_used).map(([key, value]) => {
            return value.label;
		}).length
		let current_lbl = num_labelled + 1;

		//find all citations, which match the form "[|$|]lorem ipsum... [|$|][|@|]content_id[|@|]"
		//and replace them with the citation,
		//which is an a tag linking to the content_id. with the class contentoracle-inline_citation
		//and text numbered from 1 to n
		//where n is the number of citations in the response
		copy.raw_content = chat.raw_content.replace(
			/\|\[\$\]\|([^|]+)\|\[\$\]\|\s*\|\[@\]\|(\d+)\|\[@\]\|/g,
			(match, text, post_id) => {
				// Get the post URL
				const post = chat.context_supplied[post_id];

				//see if this post has been labelled already
				if (post && !post?.label) {
					chat.context_supplied[post_id].label = current_lbl++;
				}

				//create the citation
				const label = post?.label || current_lbl++;
				const url = post?.url || "#";
				return `${text} <a href="${url}" class="contentoracle-inline_citation" target="_blank">${label}</a>`;
			}
		);

		//now, handle citations that are missing the "[|$|]lorem ipsum... [|$|]" part
		//in the case that the wrapper around the content is missing
		copy.raw_content = chat.raw_content.replace(
			/\|\[@\]\|(\d+)\|\[@\]\|/g,
			(match, post_id) => {
				// Get the post URL
				const post = chat.context_supplied[post_id];
				
				//see if this post has been labelled already
				if (post && !post?.label) {
					chat.context_supplied[post_id].label = current_lbl++;
				}

				//create the citation
				const label = post?.label || current_lbl++;
				const url = post?.url || "#";
				return `<a href="${url}" class="contentoracle-inline_citation" target="_blank">${label}</a>`;
			}
		)

		//remove extra "|[$]|" and "|[@]|" from the content
		copy.raw_content = copy.raw_content.replace(/\|\[@\]\|/g, "");
		copy.raw_content = copy.raw_content.replace(/\|\[\$\]\|/g, "");

		// set context used for bottom citations
		//filter to see which ones were labelled
		const context_used = []
		Object.entries(chat.context_supplied).forEach(([key, post]) => {
			if (post.label) {
				context_used.push(post);
			}
		})
		//sort by label, with lowest label first
		context_used.sort((a, b) => a.label - b.label);
		//set the context used
		copy.context_used = context_used;

		//return copy
		return copy;
		
	},
	//add styling to the main idea of an ai response
	addMainIdea(chat) {
		//anything fitting the form "|[#]|lorem ipsum...|[#]|" is the main idea
		//wrap it in a span with the class "contentoracle-ai_chat_bubble_bot_main_idea"

		//begin by making a SHALLOW copy of the original chat object
		//I'd rather return a copy than modify state directly
		const copy = Object.assign({}, chat);

		//find the main idea, which matches the form "|[#]|lorem ipsum...|[#]|"
		//and replace it with the main idea,
		//which is a span tag with the class contentoracle-ai_chat_bubble_bot_main_idea
		copy.raw_content = chat.raw_content.replace(
			/\|\[\#\]\|([^|]+)\|\[#\]\|/g,
			(match, text) => {
				return `<span class="contentoracle-ai_chat_bubble_bot_main_idea">${text}</span>`;
			}
		);

		//remove extra "|[#]|" from the content
		copy.raw_content = copy.raw_content.replace(/\|\[#\]\|/g, "");

		//return copy
		return copy;
	},
	//scrolls to the bottom of the chat
	scrollToBottom( event ) {
        const chatContainer = this.$refs.chatBody;
		chatContainer.scrollTop = chatContainer.scrollHeight;
	},
	//scrolls to the top of the bottommost assistant chat
	scrollToBottomMostChat( event ) {
		const chatContainer = this.$refs.chatBody;
		const assistantChats = chatContainer.querySelectorAll('.contentoracle-ai_chat_bubble');
		const lastChat = assistantChats[assistantChats.length - 1];
		if (lastChat) {
			// Get the position of the last assistant chat
			const lastChatPosition = lastChat.offsetTop;

			// Check if the current scroll position is not already at the desired position
			if (chatContainer.scrollTop + chatContainer.clientHeight < lastChatPosition) {
				// Scroll to the position of the last assistant chat
				chatContainer.scrollTop = lastChatPosition + 5; // Adjust the offset value as needed
			}
		}
	},
	//performs all tasks that need to be performed when an error response is received
	handleErrorResponse( error ) {
		this.error = `Error ${error.error.error}: "${error.error.message}".`;
		console.error(`Error originates from ${error.error_source == "coai" ? "ContentOracle AI API" : "WordPress API"}.`, error.error);
	},

	//get the conversations, with the context prepended to the user message
	getConversationWithContext(){
		//make a copy of the conversation, to avoid state mutation
		const conversation = JSON.parse(JSON.stringify(this.conversation));
		let context_used = null;
		let context_used_str = null;

		//iterate through the conversation, and prepend the context used by the ai in its response to the user message
		for (let i = conversation.length - 1; i >= 0; i--) {
			//get a chat message
			const chat = conversation[i];

			//get the context used by the ai in its response if 
			//this chat is an assistant message
			if (chat.role == 'assistant') {
				//get the context used by the ai in its response
				context_used = chat.context_used;

				//create the new text content for the user message
				context_used_str = "Use this site content in your response: " + context_used.map((post) => {
					return "Title: " + post.title + " (" + post.type + ")" + " - " + post.body
				}).join("\n");
			}
			//otherwise, if this is a user message, prepend the context used by the ai in its response
			else{
				//prepend the context used to the user message
				if (context_used){
					conversation[i].content = context_used_str + "\nUser query: " + chat.content;
				}
			}
		}

		//return the conversation
		return conversation;
	}
})
)

Alpine.start();