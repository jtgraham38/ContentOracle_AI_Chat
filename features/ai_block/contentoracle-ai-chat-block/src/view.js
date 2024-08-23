/**
 * WordPress dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

store( 'contentoracle-ai-chat', {
	actions: {
		sendMessage: () => {
			const context = getContext();
			console.log( `Sending message: ${ context.userMsg }` );
		},
		updateUserMsg: ( event ) => {
			//console.log(event)
			//console.log( `Updating user message to: ${ event.target.value }` );
			const context = getContext();
			context.userMsg = event.target.value;
		}
	},
	callbacks: {
		messageReceived: () => {
			//TODO: called when botMsg is updated
			const { botMsg } = getContext();
			console.log( `Message received: ${ botMsg }` );
			
		},

	},
} );
