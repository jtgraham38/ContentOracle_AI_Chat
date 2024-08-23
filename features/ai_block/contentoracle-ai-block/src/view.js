/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * Example:
 *
 * ```js
 * {
 *   "viewScript": "file:./view.js"
 * }
 * ```
 *
 * If you're not making any changes to this file because your project doesn't need any
 * JavaScript running in the front-end, then you should delete this file and remove
 * the `viewScript` property from `block.json`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */
//NOTE: one day, I need to migrate this to use the interactivity API

//NOTE: even when there are muiltiple blocks on a page, this script will only run once



console.log(window.wp.api)
document.addEventListener('DOMContentLoaded', function() {
    //get parts of the chat block by class
    const root = document.querySelector('.contentoracle-ai-block');

    //get the rest api base url from the wp global

    console.log(restBaseUrl);

	//get the &contentoracle_ai_search parameter
	const urlParams = new URLSearchParams(window.location.search);
	const search = urlParams.get('contentoracle_ai_search');

	//preemptively send the search term if it is set
    if (search){
        //send the search term to the server
        sendUserMessage(search);
    }
})

//send a message from the user to the server
async function sendUserMessage(message){
    //set defaults
    //RES

    //send the message to the server
    window.alert('Sending message: ' + message);
}