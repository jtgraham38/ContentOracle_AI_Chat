/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/*
My imports
*/
import { __experimentalUseBorderProps as useBorderProps } from '@wordpress/block-editor';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({
	attributes,
	setAttributes,
	className,
}) {

	//merge the blockprops, borderProps, and classname
	const blockProps = useBlockProps({
		className: 'contentoracle-ai_chat_root'
	});
	const borderProps = useBorderProps(attributes);	//TODO: integrate border props!
	
	//return the editor markup
	return (
		<>
			<div { ...blockProps }>

				<div>
					<div>ContentOracle AI Chat</div>
				</div>
				<div></div>
				<div className="contentoracle-ai_chat_input_container">
					<input type="text" className="contentoracle-ai_chat_input" />
					<div className="contentoracle-ai_chat_button">Send</div>
				</div>
			</div>
		</>
	);
}
