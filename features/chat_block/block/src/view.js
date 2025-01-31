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
		console.log(this.stream_responses);

		//scroll to the bottom of the chat when the conversation updates
		this.$watch('conversation', () => {
            this.scrollToBottom(); // Call the function when conversation updates
		});
		this.$watch('loading', () => {
			this.scrollToBottom(); // Call the function when loading updates
		});

		//preemptively add the search query to the conversation, if it exists
		const urlParams = new URLSearchParams(window.location.search);
		const searchQuery = urlParams.get('contentoracle_ai_search');
		if (searchQuery) {
			if (this.stream_responses) {
				this.sendStreamed( searchQuery, event );
			}
			else{
				this.send( searchQuery, event );
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
		const data = {
			message: msg,
			conversation: this.conversation.length <= 10 ? this.conversation : this.conversation.slice(this.conversation.length - 10),
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
			this.error = json.error
			console.error(json.error);
		}
		if (json.errors) {
			//combine the errors into a single string, and push to the conversation
			let error_msgs = [];
			Object.entries(json.errors).map(([key, value]) => {
				error_msgs.push(key + ": " + value);
			});
				
			this.error = error_msgs.join(", ");
			console.error(json.errors);
		}
		else if ( json?.response?.error ) {
			//push the error to the conversation
			//this is an error that might be set in contentoracle api, because it is a part of the response
			this.error = json.response.error;
			console.error(json.response.error);
		} 
		else if (json?.code) {
			//handle error from the wp api validators, like nonce error, etc.
			this.error = json?.message;
			console.error(json);
		}
		else {
			//check for unauthenticated
			//TODO: change structure of handler when coai response changes
			if (json?.response == "Unauthenticated.")
			{
				//push the error to the conversation
				this.error = "Unauthenticated. Site admin should check api token.";
				console.error("Unauthenticated. Site admin should check api token.");
			}
			else{
				try {

					//render and sanitize the markdown
					let rendered = DOMPurify.sanitize(marked.parse(json.response));

					//push the response to the conversation
						console.log(json);
						this.conversation.push( {
							role: 'assistant',
							content: rendered,
							citations: json.citations,
							context_used: json.context_used,
							context_supplied: json.context_supplied,
							action: json.action
						});
				}
				catch(e){
					this.error = "An error occurred while processing the response";
					console.error(e);
				}
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
			"GET",			//TODO: change later!
			this.apiBaseUrl + 'contentoracle-ai-chat/v1/chat/stream&message=' + msg,
			true
		);

		//set the headers
		xhr.setRequestHeader('Content-Type', 'application/json');
		xhr.setRequestHeader('COAI-X-WP-Nonce', this.chatNonce);

		//set streaming handler
		let finger = 0;
		let full_response = "";
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

			//TODO
			//TODO
			//TODO TODO: sometimes, each entry of the respones array is not a full fragment, I need to solve and fix this
			//TODO
			//TODO

			//iterate through the responses, parsing them
			responses.map((response) => {

				//push a chat bubble to the conversation for the ai response to fill, if one has not already been added
				if (this.conversation.length == 0 || this.conversation[this.conversation.length - 1].role != 'assistant') {
					this.conversation.push({
						role: 'assistant',
						content: "",
						citations: [],
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
					console.error(e);
					console.error(responses);
					console.error(_response);
					return;
				}

				//handle the response
				if ( parsed?.error ){
					//this is an error that might be set in the wp api, because it is not a part of the response
					this.error = parsed.error
					console.error(parsed.error);
				}
				//check if this is the action response
				else if (parsed?.action) {
					//handle the action
					if (!this.conversation?.action && this.conversation[this.conversation.length - 1].role == 'assistant') {
						this.conversation[this.conversation.length - 1].action = parsed.action;
					}
				}
				//otherwise, extract the generated message fragment
				else {
					//ensure the last message is not finished yet and it is an assistant message
					if (!this.conversation?.action && this.conversation[this.conversation.length - 1].role == 'assistant') {
						//append the fragment to the last message
						if (parsed?.generated?.message.length > 0) {
							full_response += parsed?.generated?.message;
						}
					}

					//render and sanitize the markdown
					this.conversation[this.conversation.length - 1].content = DOMPurify.sanitize(
						marked.parse(
							full_response
						)
					);
				}
			} )
		}.bind(this);	//IMPORTANT: bind the this context to the alpine object, otherwise it will be the xhr object

		//set error handler
		xhr.onerror = function() {
			console.error(event);
		};

		//send the request with the body
		const data = {
			message: msg,
			conversation: this.conversation.length <= 10 ? this.conversation : this.conversation.slice(this.conversation.length - 10),
		};
		xhr.send(JSON.stringify(data));
	},
	//scrolls to the bottom of the chat
	scrollToBottom( event ) {
        const chatContainer = this.$refs.chatBody;
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
})
)

Alpine.start();