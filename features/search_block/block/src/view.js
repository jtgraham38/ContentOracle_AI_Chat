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
import { computePosition, flip, shift, offset, arrow, autoUpdate } from '@floating-ui/dom'


document.addEventListener('DOMContentLoaded', () => {
	//get refs to floating element and search bar
	const searchbars = Array.from(document.querySelectorAll('.contentoracle-ai_search_root'));

	//update location of each notice to stay with searchbar
	searchbars.map((searchbarEl) => {
		//find notice element
		const noticeEl = searchbarEl.querySelector('.contentoracle-ai_search_notice');

		//check the cookei to determine if we have shown this vistor the ai notice recently
		if (document.cookie.includes('contentoracle_ai_notice')) {
			noticeEl.remove();
			return;
		}

		//find the floating notice element and arrow
		const cleanUp = autoUpdate(searchbarEl, noticeEl, () => { updateNoticePosition(searchbarEl) } )
		console.log(cleanUp);

		//add event listener to remove notice and clean up
		const closeEl = searchbarEl.querySelector('.contentoracle-ai_search_notice_close');
		closeEl.addEventListener('click', () => {
			//remove the notice element
			noticeEl.remove();
			//clean up the auto update
			cleanUp();

			//set the cookie to not show the visiotr the notice again for 3 months
			const date = new Date();
			date.setMonth(date.getMonth() + 3);
			document.cookie = `contentoracle_ai_notice=1; expires=${date.toUTCString()}; path=/`;
		});
	});
		
		

});

function updateNoticePosition(searchbarEl) {
	//find notice and arrow elements
	const noticeEl = searchbarEl.querySelector('.contentoracle-ai_search_notice');
	const arrowEl = noticeEl.querySelector('.contentoracle-ai_search_arrow');

	//compute position the floating element should occupy
	computePosition(searchbarEl, noticeEl, {
		placement: "bottom",
		middleware: [
			flip(),	//flip if against a border
			shift({ padding: 5 }),	//padding when against a border
			offset(2),	//pixel distance from the searchbar
			arrow({ element: arrowEl })	//arrow element
		]
	}).then(({ x, y, placement, middlewareData }) => {
		//update the notice element's position
		Object.assign(noticeEl.style, {
			left: `${x}px`,
			top: `${y}px`,
		});

		//get arrow location
		const { x: arrowX, y: arrowY } = middlewareData.arrow;
		
		//update the arrow's position
		const staticSide = {
			top: 'bottom',
			right: 'left',
			bottom: 'top',
			left: 'right',
		}[placement.split('-')[0]];
		
		Object.assign(arrowEl.style, {
			left: arrowX != null ? `${arrowX}px` : '',
			top: arrowY != null ? `${arrowY}px` : '',
			right: '',
			bottom: '',
			[staticSide]: '-4px',
		});
	});
}