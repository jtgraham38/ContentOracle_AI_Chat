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
	init() {
		console.log('init chat!!!');
		//load the rest url into the apiBaseUrl from the data-contentoracle_rest_url attribute
		this.apiBaseUrl = this.$el.getAttribute('data-contentoracle_rest_url');
		this.chatNonce = this.$el.getAttribute('data-contentoracle_chat_nonce');

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
			this.send( searchQuery );
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
		await this.send( this.userMsg, event );
	},
	//sends a message to the server and gets an ai response back
	async send( msg ) {
		//set loading state, after a slight delat
		setTimeout( 
			() => { this.loading = true; }, 1000 
		);
		//this.loading = true;
		
		//prepare the request headers and body
		const url = this.apiBaseUrl + 'contentoracle/v1/chat';
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
	//scrolls to the bottom of the chat
	scrollToBottom( event ) {
        const chatContainer = this.$refs.chatBody;
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
})
)

Alpine.start();

//the interactivity api way to do it... api seems to new with too little documentation to use just yet
/*
store( 'contentoracle-ai-chat', {
	actions: {
		//NOTE: this should not be implemented with async/await, but with a generator
		//NOTE: unexpected behavior when using async/await, the message is not added to the conversation
		//TODO: check this page for more: https://make.wordpress.org/core/2024/03/04/interactivity-api-dev-note/
		sendMessage: async ( event ) => {
			//get the message from the user
			const context = getContext();

			//ensure there is a message
			if ( context.userMsg === '' ) {
				//NOTE: the validaity doesnt work, for some reason!
				event.target.setCustomValidity( 'Please enter a message!' );
				console.log( 'No message entered!' );
				return
			}

			//add message to conversation
			context.conversation.push( {
				role: 'user',
				message: context.userMsg,
			} );
			console.log( context.conversation );

			//prepare the request
			const url = context.apiBaseUrl + 'contentoracle/v1/search';
			const data = {
				query: context.userMsg,
			};
			const options = {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( data ),
			};

			//send the request
			const request = await fetch( url, options )
			const json = await request.json();
			//const json = { response: {it: "worked"} }

			//push the response to the conversation
			context.conversation.push( {
				role: 'assistant',
				message: json.response.it,	//NOTE: .it is temporary for now, will grab actual message later
			} );

			//clear out input
			context.userMsg = '';
			event.target.value = '';
		},
		updateUserMsg: ( event ) => {
			console.log("updateUserMsg")
			//console.log( `Updating user message to: ${ event.target.value }` );
			const context = getContext();
			context.userMsg = event.target.value;
		}
	},
	callbacks: {
		
	},
} );
*/