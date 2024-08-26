/**
 * WordPress dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import Alpine from 'alpinejs';

//initialize alpinejs
Alpine.data('contentoracle_ai_chat', () => ({
	userMsg: "",
	apiBaseUrl: "",	//will be filled in by the block via php
	conversation: [],
	loading: false,
	init() {
		//load the rest url into the apiBaseUrl from the data-contentoracle_rest_url attribute
		this.apiBaseUrl = this.$el.getAttribute('data-contentoracle_rest_url');

		//scroll to the bottom of the chat when the conversation updates
		this.$watch('conversation', () => {
            this.scrollToBottom(); // Call the function when conversation updates
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

		//send the message
		await this.send( this.userMsg, event );
	},
	//sends a message to the server and gets an ai response back
	async send( msg ) {
		//set loading state
		this.loading = true;
		this.$refs.chatInput.disabled=true;

		//add message to conversation
		this.conversation.push( {
			role: 'user',
			message: msg,
		} );
		console.log( this.conversation );

		//prepare the request
		const url = this.apiBaseUrl + 'contentoracle/v1/search';
		const data = {
			query: msg,
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
		this.conversation.push( {
			role: 'assistant',
			message: json.response.it,	//NOTE: .it is temporary for now, will grab actual message later
		} );

		//set loading state
		this.loading = false;
		this.$refs.chatInput.disabled=false;

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