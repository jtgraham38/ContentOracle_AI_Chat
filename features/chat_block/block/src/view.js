/**
 * WordPress dependencies
 */
import Alpine from 'alpinejs';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import InlineCitationArtifact from './artifacts/inline_citation_artifact';

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


		//TEST
// 		const S = {
// 			content_supplied: {
//     "1": {
//         "id": "1",
//         "title": "Hello world!",
//         "url": "http://localhost:8080/?p=1",
//         "body": "Welcome to WordPress . This is your first post . Edit or delete it , then start writing !",
//         "type": "post"
//     },
//     "2": {
//         "id": "2",
//         "title": "Sample Page",
//         "url": "http://localhost:8080/?page_id=2",
//         "body": "This is an example page . It ' s different from a blog post because it will stay in one place and will show up in your site navigation ( in most themes ) . Most people start with an About page that introduces them to potential site visitors . It might say something like this : Hi there ! I ' m a bike messenger by day , aspiring actor by night , and this is my website . I live in Los Angeles , have a great dog named Jack , and I like piña coladas . ( And gettin ' caught in the rain . ) . . . or something like this : The XYZ Doohickey Company was founded in 1971 , and has been providing quality doohickeys to the public ever since . Located in Gotham City , XYZ employs over 2 , 000 people and does all kinds of awesome things for the Gotham community . As a new WordPress user , you should go to your dashboard to delete this page and create new pages for your content . Have fun !",
//         "type": "page"
//     },
//     "23": {
//         "id": "23",
//         "title": "The Most Tasty Tomatos in the Northeast",
//         "url": "http://localhost:8080/?p=23",
//         "body": "The tastiest tomatos to grow in the Northeast are Roma , Beefsteak , and Cherry tomatos . These tomatos are the most popular and have the best flavor .",
//         "type": "post"
//     },
//     "25": {
//         "id": "25",
//         "title": "How to Grow Tomatos in the Northeast",
//         "url": "http://localhost:8080/?p=25",
//         "body": "Growing tomatos in the Northeast is easy . You need to plant them in the spring , water them regularly , and give them plenty of sunlight . You can grow tomatos in your backyard or in a container on your porch .",
//         "type": "post"
//     },
//     "27": {
//         "id": "27",
//         "title": "The Best Soil for Growing Tomatos",
//         "url": "http://localhost:8080/?p=27",
//         "body": "The best soil for growing tomatos is loamy soil . Loamy soil is a mixture of sand , silt , and clay . It is well - draining and has plenty of nutrients for tomatos to grow . You can buy loamy soil at your local garden center or make your own by mixing sand , silt , and clay together . . .",
//         "type": "post"
//     },
//     "297": {
//         "id": "297",
//         "title": "Tomato Seed Starting Seminar",
//         "url": "http://localhost:8080/?tribe_events=tomato-seed-starting-seminar",
//         "body": "Join us for an informative seminar on starting your own tomato plants from seed ! This hands - on event is perfect for both novice and experienced gardeners who want to learn the essentials of growing healthy , vibrant tomatoes from scratch . During this seminar , you ' ll learn about : Selecting the best tomato varieties for your garden . Proper seed starting techniques , including timing and soil preparation . Creating the ideal environment for seed germination . Transplanting seedlings and caring for young plants . Common challenges and how to overcome them for a bountiful harvest . This seminar will provide you with practical tips and expert advice to help ensure a successful growing season . All participants will also receive a packet of tomato seeds to start at home . Whether you ' re looking to enhance your gardening skills or simply want to grow your own delicious tomatoes , this seminar is a great opportunity to get started ! Register now to reserve your spot ! growth",
//         "type": "tribe_events"
//     },
//     "299": {
//         "id": "299",
//         "title": "Tomato",
//         "url": "http://localhost:8080/?tribe_events=tomato",
//         "body": "This event is a tomato",
//         "type": "tribe_events"
//     },
//     "340": {
//         "id": "340",
//         "title": "Cart",
//         "url": "http://localhost:8080/?page_id=340",
//         "body": "You may be interested in … Your cart is currently empty ! New in store",
//         "type": "page"
//     },
//     "345": {
//         "id": "345",
//         "title": "Tomato Seeds",
//         "url": "http://localhost:8080/?product=tomato-seeds",
//         "body": "Experience the joy of homegrown tomatoes with our premium tomato seed pack . These non - GMO seeds guarantee vigorous growth and high yield , perfect for both beginner and experienced gardeners . Enjoy fresh , juicy tomatoes right from your backyard .",
//         "type": "product"
//     },
//     "346": {
//         "id": "346",
//         "title": "Garden Soil",
//         "url": "http://localhost:8080/?product=garden-soil",
//         "body": "Our Garden Soil is a premium blend of organic materials , designed to promote healthy plant growth . It ensures proper drainage and nutrient retention , creating an ideal environment for your plants . Enhance your garden ' s fertility and overall health with our rich , earthy soil . Perfect for various plants , flowers , and vegetables , especially tomatoes .",
//         "type": "product"
//     },
//     "542": {
//         "id": "542",
//         "title": "The Journey of Tomato Growth: From Seed to Fruit",
//         "url": "http://localhost:8080/?p=542",
//         "body": "providing the right care and attention , you can enjoy a steady supply of homegrown tomatoes , bursting with flavor and nutrition . Whether you ' re growing them in containers on a balcony or in a sprawling backyard garden , tomatoes are a delightful addition to any garden and a testament to the wonders of nature .",
//         "type": "post"
//     }
// 			},
// 			content_used: [],
// 			raw_content: `Growing tomatoes can be a rewarding experience. Here are some steps to help you succeed:
// 1. **Choose the Right Variety**: Select a tomato variety suitable for your climate and space. Determinate (bush) types are ideal for small spaces, while indeterminate (vine) types are perfect for a prolonged harvest.

// 2. **Starting Seeds**:
//    - **Indoors**: Start seeds indoors 6-8 weeks before the last frost.
//    - **Planting**: Plant seeds in a seed-starting mix, about ¼ inch deep.
//    - **Conditions**: Keep the soil moist and provide warmth (70-80°F) and 12-16 hours of light daily.

// 3. **Transplanting**:
//    - **Hardening Off**: Gradually acclimate seedlings to outdoor conditions over 7-10 days.
//    - **Soil Preparation**: Use well-draining, nutrient-rich soil with a slightly acidic pH (6.0-6.8).
//    - **Planting Deep**: Bury seedlings up to their first set of true leaves to encourage root growth.

// 4. **Growing Conditions**:
//    - **Sunlight**: Ensure plants receive at least 6-8 hours of direct sunlight daily.
//    - **Watering**: Water deeply and consistently, providing 1-2 inches of water per week.
//    - **Mulching**: Apply mulch to retain soil moisture and regulate temperature.
//    - **Support**: Use stakes, cages, or trellises to keep plants upright.

// 5. **Fertilizing and Pruning**:
//    - **Fertilizer**: Use a balanced fertilizer initially, then switch to one higher in phosphorus and potassium as plants flower and fruit.
//    - **Pruning**: Remove suckers on indeterminate varieties to focus energy on fruit production.

// 6. **Pollination**: Encourage pollination by gently shaking the plant or using an electric toothbrush near the flowers.

// 7. **Harvesting**: Pick tomatoes when they are fully colored and slightly firm.

// For the best results, consider using <coai-artifact artifact_type="inline_citation" content_id="345">our premium tomato seeds</coai-artifact> and <coai-artifact artifact_type="inline_citation" content_id="346">our Garden Soil</coai-artifact> to ensure vigorous growth and high yield.

// Greener Garden Center is here to help you with all your gardening needs. Visit our store or shop online for a wide selection of gardening products.`}

		// this.renderArtifacts(S)

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
			switch (artifact_type) {
				case 'inline_citation':
					const inline_citation = new InlineCitationArtifact(artifact);
					const rendered = inline_citation.render(chat.content_supplied, chat.content_used);	//NOTE: these are modified by reference
					return rendered.outerHTML;
				default:
					return match;
			}
		})
		return artifacts_rendered;
	},
	//scrolls to the bottom of the chat
	scrollToBottom( event ) {
        const chatContainer = this.$refs.chatBody;
		chatContainer.scrollTop = chatContainer.scrollHeight;
	},
	//scrolls to the top of the bottommost assistant chat
	scrollToBottomMostChat( event ) {
	},
	//performs all tasks that need to be performed when an error response is received
	handleErrorResponse( error ) {
		this.error = `Error ${error.error.error ||	error.error_code}: "${error.error_msg}".`;	//|| output hte unauthorized error properly
		console.error(`Error originates from ${error.error_source == "coai" ? "ContentOracle AI API" : "WordPress API"}.`, error.error);
	},

	//get the conversations, with the context prepended to the user message
	getConversationWithContext(){
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
					conversation[i].content = conversation[i + 1].engineered_prompt;
				}
			}
		}

		//return the conversation
		return conversation;
	}
})
)

Alpine.start();