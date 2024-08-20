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
import { InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
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
	//get a unique id suffix for this block instance
	const iid = Math.random().toString(36).substr(2, 9)

	//merge the blockprops, borderProps, and classname
	const blockProps = useBlockProps({
		className: 'contentoracle-ai_chat_root'
	});
	console.log(blockProps);
	const borderProps = useBorderProps(attributes);	//TODO: integrate border props!
	console.log(borderProps);
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

	//make header props
	const headerProps = {
		className: 'contentoracle-ai_chat_header',
		style: {
			color: blockProps?.style?.color
		}
	}
	console.log("headerProps", headerProps);

	//make chat window props
	const chatWindowProps = {
		className: 'contentoracle-ai_chat_conversation',
		style: {
			borderRadius: borderProps?.style?.borderRadius,
			borderColor: borderProps?.style?.borderColor,
			borderWidth: borderProps?.style?.borderWidth,
			height: attributes?.height || "20rem"
		}
	}

	//make bot message props
	const botMsgProps = {
		className: 'contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_bot',
		style: {
			backgroundColor: attributes?.botMsgBgColor,
			color: attributes?.botMsgTextColor
		}
	}

	//make user message props
	const userMsgProps = {
		className: 'contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_user',
		style: {
			backgroundColor: attributes?.userMsgBgColor,
			color: attributes?.userMsgTextColor
		}
	}

	//return the editor markup
	return (
		<>
			<InspectorControls>
				<PanelBody>
				<div className="contentoracle-ai_panelbody_root">
					<h3>Display Settings</h3>
					<div className="contentoracle-ai_panelbody_group">
						<div className="contentoracle-ai_panelbody_input_container">
							<label 
								className="components-base-control__label aceef-fb-c-f-cfc-1v57ksj ej5x27r2" 
								htmlFor={`wp-block-chat_height_${iid}`}>
								Height
							</label>
							<input 
								type="range" 
								min="10" 
								max="40" 
								step="1"
								defaultValue={ parseInt(attributes.height.slice(0,-3)) }
								id={`wp-block-chat_height_${iid}`}
								onChange={ ( event ) => {
									setAttributes( { height: event.target.value + "rem" } );
								} }
							></input>
						</div>
						<p>{ attributes?.height || "-" } </p>
					</div>

					<div className="contentoracle-ai_panelbody_group">
						<div className="contentoracle-ai_panelbody_input_container">
							<PanelColorSettings
								title="Bot Message Colors"
								initialOpen={true}
								id={`contentoracle-ai_chat_bot_msg_background_color_${iid}`}
								colorSettings={[
									{
										value: attributes?.botMsgBgColor,
										onChange: (color) => setAttributes({ botMsgBgColor: color }),
										label: 'Bot Background Color',
									},
									{
										value: attributes?.botMsgTextColor,
										onChange: (color) => setAttributes({ botMsgTextColor: color }),
										label: 'Bot Text Color',
									}
								]}
							/>

							<PanelColorSettings
								title="User Message Colors"
								initialOpen={true}
								id={`contentoracle-ai_chat_user_msg_background_color_${iid}`}
								colorSettings={[
									{
										value: attributes?.userMsgBgColor,
										onChange: (color) => setAttributes({ userMsgBgColor: color }),
										label: 'User Background Color',
									},
									{
										value: attributes?.userMsgTextColor,
										onChange: (color) => setAttributes({ userMsgTextColor: color }),
										label: 'User Text Color',
									}
								]}
							/>
						</div>
					</div>
				</div>
				</PanelBody>
			</InspectorControls>


			<div { ...blockProps }>

				<div>
					<h3 { ...headerProps }>
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
				<div { ...chatWindowProps }>
					<div { ...userMsgProps }>
						<p>How do I grow a tomato plant?</p>
					</div>

					<div { ...botMsgProps }>
						<p>Tomato plants grow best in full sun, in soil that is rich in organic matter, and well-drained. They need a lot of water, but not too much. They also need a lot of nutrients, so you should fertilize them regularly. You should also prune them regularly to keep them healthy and productive. If you follow these tips, you should have a healthy and productive tomato plant.</p>
					</div>

				</div>
				<div { ...inputContainerProps }>
					<input 
						type="text" 
						{ ...inputProps }  
						aria-label="Optional placeholder text" 
						placeholder="Optional placeholder…" 
						defaultValue={ attributes.placeholder }
						onChange={ ( event ) => {
							setAttributes( { placeholder: event.target.value } );
						} }
					/>

					<div { ...buttonProps } >Send</div>
				</div>
			</div>
		</>
	);
}
