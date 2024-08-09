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

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */

/* My imports here */
import { RichText, __experimentalUseBorderProps as useBorderProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';

export default function Edit({
	attributes,
	setAttributes,
	className,
}) {	
	//TODO: get a unique id for this block instance
	//const instanceId = useInstanceId();

	//load the props
	const blockProps = useBlockProps();
	const borderProps = useBorderProps(attributes);
	console.log(attributes.width);

	//create styles for each element
	const buttonStyles = {
		borderRadius: borderProps.style.borderRadius,
		color: blockProps.style.color,
		backgroundColor: blockProps.style.backgroundColor,
	}
	
	const inputStyles = {
		borderRadius: borderProps.style.borderRadius,
	}

	const labelStyles = {
		color: blockProps.style.color,
		fontSize: blockProps.style.fontSize,
	}

	const rootStyles = {
		width: attributes.width
	}


	return (
		<>
		<InspectorControls>
			<PanelBody>
				<div>
					<h3>Display Settings</h3>
					
					<label 
						className="components-base-control__label aceef-fb-c-f-cfc-1v57ksj ej5x27r2" 
						htmlFor="wp-block-search__width-0">
						Width
					</label>
					todo: link this label to the input with a unique id
					<input 
						type="range" 
						min="10" 
						max="100" 
						step="1"
						onChange={ ( event ) => {
							setAttributes( { width: event.target.value + "%" } );
							console.log(attributes.width)
						} }
					></input>
					<p>{ attributes?.width || "-" } </p>

				</div>
			</PanelBody>
		</InspectorControls>

		<div className="contentoracle-ai_search_root" style={ rootStyles }>
			<RichText
				tagName="label"
				className="contentoracle-ai_search_label wp-block-search__label"
				placeholder="Label here..."
				defaultValue="Search"
				style={ labelStyles }
				onChange={ ( value ) => {
					setAttributes( { label: value } );
				} }
			>

			</RichText>
			<div {...borderProps} className="contentoracle-ai_search_container" >
				<input 
					type="search" 
					aria-label="Optional placeholder text" 
					placeholder="Optional placeholder…" 
					defaultValue=""
					className="contentoracle-ai_search_input wp-block-search__input"
					style={ inputStyles }
				>
				</input>
				<div 
					type="submit" 
					style={ buttonStyles }
					className='contentoracle-ai_search_button'
				>
					Search
				</div>
			</div>

		</div>
		</>
	);
}
