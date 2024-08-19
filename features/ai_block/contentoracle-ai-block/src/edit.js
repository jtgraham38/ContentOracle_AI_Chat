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
import { RichText, __experimentalUseBorderProps as useBorderProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

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
	console.log(blockProps);
	const borderProps = useBorderProps(attributes);	//TODO: integrate border props!

	//add border classes to the block props classes
	if (borderProps?.className){
		if (blockProps?.className){
			blockProps.className += ' ' + borderProps.className;
		}
		else{
			blockProps.className = borderProps.className;
		}
	}

	//add border styles to the block props styles
	if (borderProps?.style){
		if (blockProps?.style){
			blockProps.style = {...blockProps.style, ...borderProps.style};
		}
		else{
			blockProps.style = borderProps.style;
		}
	}
	
	//make input container props
	const inputContainerProps = {
		...borderProps
	}
	if (inputContainerProps?.className){
		inputContainerProps.className += ' contentoracle-ai_chat_input_container';
	}
	else{
		inputContainerProps.className = 'contentoracle-ai_chat_input_container';
	}

	//make input props
	const inputProps = {
		className: 'contentoracle-ai_chat_input',
		style: {
			borderRadius: borderProps?.style?.borderRadius
		}	
	}

	//make button props
	const buttonProps = {
		className: 'contentoracle-ai_chat_button',
		style: {
			borderRadius: borderProps?.style?.borderRadius,
			backgroundColor: borderProps?.style?.borderColor,
			color: blockProps?.style?.textColor

		}
	}

	//return the editor markup
	return (
		<>
			<InspectorControls>
				<PanelBody>
					<div className="contentoracle-ai_panelbody_root">
						<h3>Hooba dooba</h3>
						
						<div className="contentoracle-ai_panelbody_group">
							<div className="contentoracle-ai_panelbody_input_container">
								
							</div>
						</div>

					</div>
				</PanelBody>
			</InspectorControls>


			<div { ...blockProps }>

				<div>
					<h3>
					<RichText
						tagName="header"
						placeholder="AI Chat header here..."
						value={ attributes.header }
						onChange={ ( newValue ) => {
							setAttributes( { header: newValue } );
						} }
					></RichText>
					</h3>
				</div>
				<div className="contentoracle-ai_chat_conversation">
					<div className="contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_bot">
						<p>How do I grow a tomato plant?</p>
					</div>

					<div className="contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_user">
						<p>Tomato plants grow best in full sun, in soil that is rich in organic matter, and well-drained. They need a lot of water, but not too much. They also need a lot of nutrients, so you should fertilize them regularly. You should also prune them regularly to keep them healthy and productive. If you follow these tips, you should have a healthy and productive tomato plant.</p>
					</div>

				</div>
				<div { ...inputContainerProps }>
					<input type="text" { ...inputProps } />
					<div { ...buttonProps } >Send</div>
				</div>
			</div>
		</>
	);
}
