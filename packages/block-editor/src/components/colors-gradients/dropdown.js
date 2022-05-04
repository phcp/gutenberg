/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	__experimentalHStack as HStack,
	FlexItem,
	ColorIndicator,
	Dropdown,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import ColorGradientControl from './control';

export default function ColorGradientSettingsDropdown( {
	colors,
	gradients,
	disableCustomColors,
	disableCustomGradients,
	__experimentalHasMultipleOrigins,
	__experimentalIsRenderedInSidebar,
	enableAlpha,
	settings,
} ) {
	let popoverProps;
	if ( __experimentalIsRenderedInSidebar ) {
		popoverProps = {
			placement: 'left-start',
			offset: 36,
		};
	}

	return (
		<ItemGroup
			isBordered
			isSeparated
			className="block-editor-panel-color-gradient-settings__item-group"
		>
			{ settings.map(
				( setting, index ) =>
					setting && (
						<Dropdown
							key={ index }
							popoverProps={ popoverProps }
							className="block-editor-panel-color-gradient-settings__dropdown"
							contentClassName="block-editor-panel-color-gradient-settings__dropdown-content"
							renderToggle={ ( { isOpen, onToggle } ) => {
								return (
									<Item
										onClick={ onToggle }
										className={ classnames(
											'block-editor-panel-color-gradient-settings__item',
											{ 'is-open': isOpen }
										) }
									>
										<HStack justify="flex-start">
											<ColorIndicator
												className="block-editor-panel-color-gradient-settings__color-indicator"
												colorValue={
													setting.gradientValue ??
													setting.colorValue
												}
											/>
											<FlexItem>
												{ setting.label }
											</FlexItem>
										</HStack>
									</Item>
								);
							} }
							renderContent={ () => (
								<ColorGradientControl
									showTitle={ false }
									{ ...{
										colors,
										gradients,
										disableCustomColors,
										disableCustomGradients,
										__experimentalHasMultipleOrigins,
										__experimentalIsRenderedInSidebar,
										enableAlpha,
										...setting,
									} }
								/>
							) }
						/>
					)
			) }
		</ItemGroup>
	);
}
