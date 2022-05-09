<?php
/**
 * WP_Theme_JSON_Gutenberg class
 *
 * @package gutenberg
 */

/**
 * Class that encapsulates the processing of structures that adhere to the theme.json spec.
 *
 * This class is for internal core usage and is not supposed to be used by extenders (plugins and/or themes).
 * This is a low-level API that may need to do breaking changes. Please,
 * use get_global_settings, get_global_styles, and get_global_stylesheet instead.
 *
 * @access private
 */
class WP_Theme_JSON_6_0 extends WP_Theme_JSON_5_9 {
	/**
	 * Metadata for style properties.
	 *
	 * Each element is a direct mapping from the CSS property name to the
	 * path to the value in theme.json & block attributes.
	 */
	const PROPERTIES_METADATA = array(
		'background'                        => array( 'color', 'gradient' ),
		'background-color'                  => array( 'color', 'background' ),
		'border-radius'                     => array( 'border', 'radius' ),
		'border-top-left-radius'            => array( 'border', 'radius', 'topLeft' ),
		'border-top-right-radius'           => array( 'border', 'radius', 'topRight' ),
		'border-bottom-left-radius'         => array( 'border', 'radius', 'bottomLeft' ),
		'border-bottom-right-radius'        => array( 'border', 'radius', 'bottomRight' ),
		'border-color'                      => array( 'border', 'color' ),
		'border-width'                      => array( 'border', 'width' ),
		'border-style'                      => array( 'border', 'style' ),
		'border-top-color'                  => array( 'border', 'top', 'color' ),
		'border-top-width'                  => array( 'border', 'top', 'width' ),
		'border-top-style'                  => array( 'border', 'top', 'style' ),
		'border-right-color'                => array( 'border', 'right', 'color' ),
		'border-right-width'                => array( 'border', 'right', 'width' ),
		'border-right-style'                => array( 'border', 'right', 'style' ),
		'border-bottom-color'               => array( 'border', 'bottom', 'color' ),
		'border-bottom-width'               => array( 'border', 'bottom', 'width' ),
		'border-bottom-style'               => array( 'border', 'bottom', 'style' ),
		'border-left-color'                 => array( 'border', 'left', 'color' ),
		'border-left-width'                 => array( 'border', 'left', 'width' ),
		'border-left-style'                 => array( 'border', 'left', 'style' ),
		'color'                             => array( 'color', 'text' ),
		'font-family'                       => array( 'typography', 'fontFamily' ),
		'font-size'                         => array( 'typography', 'fontSize' ),
		'font-style'                        => array( 'typography', 'fontStyle' ),
		'font-weight'                       => array( 'typography', 'fontWeight' ),
		'letter-spacing'                    => array( 'typography', 'letterSpacing' ),
		'line-height'                       => array( 'typography', 'lineHeight' ),
		'margin'                            => array( 'spacing', 'margin' ),
		'margin-top'                        => array( 'spacing', 'margin', 'top' ),
		'margin-right'                      => array( 'spacing', 'margin', 'right' ),
		'margin-bottom'                     => array( 'spacing', 'margin', 'bottom' ),
		'margin-left'                       => array( 'spacing', 'margin', 'left' ),
		'padding'                           => array( 'spacing', 'padding' ),
		'padding-top'                       => array( 'spacing', 'padding', 'top' ),
		'padding-right'                     => array( 'spacing', 'padding', 'right' ),
		'padding-bottom'                    => array( 'spacing', 'padding', 'bottom' ),
		'padding-left'                      => array( 'spacing', 'padding', 'left' ),
		'--wp--style--root--padding'        => array( 'spacing', 'padding' ),
		'--wp--style--root--padding-top'    => array( 'spacing', 'padding', 'top' ),
		'--wp--style--root--padding-right'  => array( 'spacing', 'padding', 'right' ),
		'--wp--style--root--padding-bottom' => array( 'spacing', 'padding', 'bottom' ),
		'--wp--style--root--padding-left'   => array( 'spacing', 'padding', 'left' ),
		'--wp--style--block-gap'            => array( 'spacing', 'blockGap' ),
		'text-decoration'                   => array( 'typography', 'textDecoration' ),
		'text-transform'                    => array( 'typography', 'textTransform' ),
		'filter'                            => array( 'filter', 'duotone' ),
	);

	/**
	 * Presets are a set of values that serve
	 * to bootstrap some styles: colors, font sizes, etc.
	 *
	 * They are a unkeyed array of values such as:
	 *
	 * ```php
	 * array(
	 *   array(
	 *     'slug'      => 'unique-name-within-the-set',
	 *     'name'      => 'Name for the UI',
	 *     <value_key> => 'value'
	 *   ),
	 * )
	 * ```
	 *
	 * This contains the necessary metadata to process them:
	 *
	 * - path             => Where to find the preset within the settings section.
	 * - prevent_override => Disables override of default presets by theme presets.
	 *                       The relationship between whether to override the defaults
	 *                       and whether the defaults are enabled is inverse:
	 *                         - If defaults are enabled  => theme presets should not be overriden
	 *                         - If defaults are disabled => theme presets should be overriden
	 *                       For example, a theme sets defaultPalette to false,
	 *                       making the default palette hidden from the user.
	 *                       In that case, we want all the theme presets to be present,
	 *                       so they should override the defaults by setting this false.
	 * - use_default_names => whether to use the default names
	 * - value_key        => the key that represents the value
	 * - value_func       => optionally, instead of value_key, a function to generate
	 *                       the value that takes a preset as an argument
	 *                       (either value_key or value_func should be present)
	 * - css_vars         => template string to use in generating the CSS Custom Property.
	 *                       Example output: "--wp--preset--duotone--blue: <value>" will generate as many CSS Custom Properties as presets defined
	 *                       substituting the $slug for the slug's value for each preset value.
	 * - classes          => array containing a structure with the classes to
	 *                       generate for the presets, where for each array item
	 *                       the key is the class name and the value the property name.
	 *                       The "$slug" substring will be replaced by the slug of each preset.
	 *                       For example:
	 *                       'classes' => array(
	 *                         '.has-$slug-color'            => 'color',
	 *                         '.has-$slug-background-color' => 'background-color',
	 *                         '.has-$slug-border-color'     => 'border-color',
	 *                       )
	 * - properties       => array of CSS properties to be used by kses to
	 *                       validate the content of each preset
	 *                       by means of the remove_insecure_properties method.
	 */
	const PRESETS_METADATA = array(
		array(
			'path'              => array( 'color', 'palette' ),
			'prevent_override'  => array( 'color', 'defaultPalette' ),
			'use_default_names' => false,
			'value_key'         => 'color',
			'css_vars'          => '--wp--preset--color--$slug',
			'classes'           => array(
				'.has-$slug-color'            => 'color',
				'.has-$slug-background-color' => 'background-color',
				'.has-$slug-border-color'     => 'border-color',
			),
			'properties'        => array( 'color', 'background-color', 'border-color' ),
		),
		array(
			'path'              => array( 'color', 'gradients' ),
			'prevent_override'  => array( 'color', 'defaultGradients' ),
			'use_default_names' => false,
			'value_key'         => 'gradient',
			'css_vars'          => '--wp--preset--gradient--$slug',
			'classes'           => array( '.has-$slug-gradient-background' => 'background' ),
			'properties'        => array( 'background' ),
		),
		array(
			'path'              => array( 'color', 'duotone' ),
			'prevent_override'  => array( 'color', 'defaultDuotone' ),
			'use_default_names' => false,
			'value_func'        => 'gutenberg_get_duotone_filter_property',
			'css_vars'          => '--wp--preset--duotone--$slug',
			'classes'           => array(),
			'properties'        => array( 'filter' ),
		),
		array(
			'path'              => array( 'typography', 'fontSizes' ),
			'prevent_override'  => false,
			'use_default_names' => true,
			'value_key'         => 'size',
			'css_vars'          => '--wp--preset--font-size--$slug',
			'classes'           => array( '.has-$slug-font-size' => 'font-size' ),
			'properties'        => array( 'font-size' ),
		),
		array(
			'path'              => array( 'typography', 'fontFamilies' ),
			'prevent_override'  => false,
			'use_default_names' => false,
			'value_key'         => 'fontFamily',
			'css_vars'          => '--wp--preset--font-family--$slug',
			'classes'           => array( '.has-$slug-font-family' => 'font-family' ),
			'properties'        => array( 'font-family' ),
		),
	);

	/**
	 * The top-level keys a theme.json can have.
	 *
	 * @var string[]
	 */
	const VALID_TOP_LEVEL_KEYS = array(
		'customTemplates',
		'patterns',
		'settings',
		'styles',
		'templateParts',
		'version',
		'title',
	);

	const APPEARANCE_TOOLS_OPT_INS = array(
		array( 'border', 'color' ),
		array( 'border', 'radius' ),
		array( 'border', 'style' ),
		array( 'border', 'width' ),
		array( 'color', 'link' ),
		array( 'spacing', 'blockGap' ),
		array( 'spacing', 'margin' ),
		array( 'spacing', 'padding' ),
		array( 'typography', 'lineHeight' ),
	);

	/**
	 * The valid properties under the settings key.
	 *
	 * @var array
	 */
	const VALID_SETTINGS = array(
		'appearanceTools'  => null,
		'useRootVariables' => null,
		'border'           => array(
			'color'  => null,
			'radius' => null,
			'style'  => null,
			'width'  => null,
		),
		'color'            => array(
			'background'       => null,
			'custom'           => null,
			'customDuotone'    => null,
			'customGradient'   => null,
			'defaultDuotone'   => null,
			'defaultGradients' => null,
			'defaultPalette'   => null,
			'duotone'          => null,
			'gradients'        => null,
			'link'             => null,
			'palette'          => null,
			'text'             => null,
		),
		'custom'           => null,
		'layout'           => array(
			'contentSize' => null,
			'wideSize'    => null,
		),
		'spacing'          => array(
			'blockGap' => null,
			'margin'   => null,
			'padding'  => null,
			'units'    => null,
		),
		'typography'       => array(
			'customFontSize' => null,
			'dropCap'        => null,
			'fontFamilies'   => null,
			'fontSizes'      => null,
			'fontStyle'      => null,
			'fontWeight'     => null,
			'letterSpacing'  => null,
			'lineHeight'     => null,
			'textDecoration' => null,
			'textTransform'  => null,
		),
	);

	/**
	 * The valid properties under the styles key.
	 *
	 * @var array
	 */
	const VALID_STYLES = array(
		'border'     => array(
			'color'  => null,
			'radius' => null,
			'style'  => null,
			'width'  => null,
			'top'    => null,
			'right'  => null,
			'bottom' => null,
			'left'   => null,
		),
		'color'      => array(
			'background' => null,
			'gradient'   => null,
			'text'       => null,
		),
		'filter'     => array(
			'duotone' => null,
		),
		'spacing'    => array(
			'margin'   => null,
			'padding'  => null,
			'blockGap' => 'top',
		),
		'typography' => array(
			'fontFamily'     => null,
			'fontSize'       => null,
			'fontStyle'      => null,
			'fontWeight'     => null,
			'letterSpacing'  => null,
			'lineHeight'     => null,
			'textDecoration' => null,
			'textTransform'  => null,
		),
	);

	/**
	 * Returns the current theme's wanted patterns(slugs) to be
	 * registered from Pattern Directory.
	 *
	 * @return array
	 */
	public function get_patterns() {
		if ( isset( $this->theme_json['patterns'] ) && is_array( $this->theme_json['patterns'] ) ) {
			return $this->theme_json['patterns'];
		}
		return array();
	}

	/**
	 * Converts each style section into a list of rulesets
	 * containing the block styles to be appended to the stylesheet.
	 *
	 * See glossary at https://developer.mozilla.org/en-US/docs/Web/CSS/Syntax
	 *
	 * For each section this creates a new ruleset such as:
	 *
	 *   block-selector {
	 *     style-property-one: value;
	 *   }
	 *
	 * @param array $style_nodes Nodes with styles.
	 * @return string The new stylesheet.
	 */
	protected function get_block_classes( $style_nodes ) {
		$block_rules = '';

		foreach ( $style_nodes as $metadata ) {
			if ( null === $metadata['selector'] ) {
				continue;
			}

			$use_root_vars = _wp_array_get( $this->theme_json, array( 'settings', 'useRootVariables' ), array() );
			$node          = _wp_array_get( $this->theme_json, $metadata['path'], array() );
			$selector      = $metadata['selector'];
			$settings      = _wp_array_get( $this->theme_json, array( 'settings' ) );
			$declarations  = static::compute_style_properties( $node, $settings, null, $selector, $use_root_vars );

			// 1. Separate the ones who use the general selector
			// and the ones who use the duotone selector.
			$declarations_duotone = array();
			foreach ( $declarations as $index => $declaration ) {
				if ( 'filter' === $declaration['name'] ) {
					unset( $declarations[ $index ] );
					$declarations_duotone[] = $declaration;
				}
			}

			/*
			 * Reset default browser margin on the root body element.
			 * This is set on the root selector **before** generating the ruleset
			 * from the `theme.json`. This is to ensure that if the `theme.json` declares
			 * `margin` in its `spacing` declaration for the `body` element then these
			 * user-generated values take precedence in the CSS cascade.
			 * @link https://github.com/WordPress/gutenberg/issues/36147.
			 */
			if ( static::ROOT_BLOCK_SELECTOR === $selector ) {
				$block_rules .= 'body { margin: 0; }';
			}

			// 2. Generate the rules that use the general selector.
			$block_rules .= static::to_ruleset( $selector, $declarations );

			// 3. Generate the rules that use the duotone selector.
			if ( isset( $metadata['duotone'] ) && ! empty( $declarations_duotone ) ) {
				$selector_duotone = static::scope_selector( $metadata['selector'], $metadata['duotone'] );
				$block_rules     .= static::to_ruleset( $selector_duotone, $declarations_duotone );
			}

			// 4. Generate additional rules for the root block.
			$block_rules .= static::additional_root_selector_rules( $selector, $this->theme_json, $use_root_vars );
		}

		return $block_rules;
	}

	/**
	 * Generate additional rules for the root selector
	 *
	 * @param string $selector The selector to generate rules for.
	 * @param object $theme_json A copy of $this->theme_json.
	 * @param bool   $use_root_vars whether or not the theme has opted in to root padding variables.
	 * @return string The rules for the root selector.
	 */
	protected static function additional_root_selector_rules( $selector, $theme_json, $use_root_vars ) {
		$block_rules = '';
		if ( static::ROOT_BLOCK_SELECTOR === $selector ) {
			if ( $use_root_vars ) {
				$block_rules .= '.wp-site-blocks { padding-top: var(--wp--style--root--padding-top); padding-bottom: var(--wp--style--root--padding-bottom); }';

				$block_rules .= '.wp-site-blocks,
				.wp-block-group.alignfull,
				.wp-block-group.has-background,
				.wp-block-columns.alignfull.has-background,
				.wp-block-cover.alignfull
				{ padding-right: var(--wp--style--root--padding-right); padding-left: var(--wp--style--root--padding-left); }';

				$block_rules .= '.wp-site-blocks .alignfull { margin-right: calc(var(--wp--style--root--padding-right) * -1); margin-left: calc(var(--wp--style--root--padding-left) * -1); width: unset; }';

				// Alignfull blocks in the block editor that are direct children of post content should also get negative margins.
				if ( is_callable( 'get_current_screen' ) && get_current_screen()->is_block_editor() ) {
					$block_rules .= 'body > .is-root-container,
					.edit-post-visual-editor__post-title-wrapper,
					.is-root-container .wp-block.alignfull > .wp-block-group,
					.is-root-container .wp-block.alignfull > .wp-block-columns.has-background,
					.is-root-container .wp-block.alignfull > .wp-block-cover
					{ padding-right: var(--wp--style--root--padding-right); padding-left: var(--wp--style--root--padding-left); }';

					$block_rules .= '.is-root-container .wp-block.alignfull { margin-right: calc(var(--wp--style--root--padding-right) * -1); margin-left: calc(var(--wp--style--root--padding-left) * -1); max-width: unset; width: unset; }';
				}
			}

			$block_rules .= '.wp-site-blocks > .alignleft { float: left; margin-right: 2em; }';
			$block_rules .= '.wp-site-blocks > .alignright { float: right; margin-left: 2em; }';
			$block_rules .= '.wp-site-blocks > .aligncenter { justify-content: center; margin-left: auto; margin-right: auto; }';

			$has_block_gap_support = _wp_array_get( $theme_json, array( 'settings', 'spacing', 'blockGap' ) ) !== null;
			if ( $has_block_gap_support ) {
				$block_rules .= '.wp-site-blocks > * { margin-block-start: 0; margin-block-end: 0; }';
				$block_rules .= '.wp-site-blocks > * + * { margin-block-start: var( --wp--style--block-gap ); }';
			}
		}

		return $block_rules;
	}

	/**
	 * Merge new incoming data.
	 *
	 * @param WP_Theme_JSON $incoming Data to merge.
	 */
	public function merge( $incoming ) {
		$incoming_data    = $incoming->get_raw_data();
		$this->theme_json = array_replace_recursive( $this->theme_json, $incoming_data );

		/*
		 * The array_replace_recursive algorithm merges at the leaf level,
		 * but we don't want leaf arrays to be merged, so we overwrite it.
		 *
		 * For leaf values that are sequential arrays it will use the numeric indexes for replacement.
		 * We rather replace the existing with the incoming value, if it exists.
		 * This is the case of spacing.units.
		 *
		 * For leaf values that are associative arrays it will merge them as expected.
		 * This is also not the behavior we want for the current associative arrays (presets).
		 * We rather replace the existing with the incoming value, if it exists.
		 * This happens, for example, when we merge data from theme.json upon existing
		 * theme supports or when we merge anything coming from the same source twice.
		 * This is the case of color.palette, color.gradients, color.duotone,
		 * typography.fontSizes, or typography.fontFamilies.
		 *
		 * Additionally, for some preset types, we also want to make sure the
		 * values they introduce don't conflict with default values. We do so
		 * by checking the incoming slugs for theme presets and compare them
		 * with the equivalent default presets: if a slug is present as a default
		 * we remove it from the theme presets.
		 */
		$nodes        = static::get_setting_nodes( $incoming_data );
		$slugs_global = static::get_default_slugs( $this->theme_json, array( 'settings' ) );
		foreach ( $nodes as $node ) {
			$slugs_node = static::get_default_slugs( $this->theme_json, $node['path'] );
			$slugs      = array_merge_recursive( $slugs_global, $slugs_node );

			// Replace the spacing.units.
			$path    = array_merge( $node['path'], array( 'spacing', 'units' ) );
			$content = _wp_array_get( $incoming_data, $path, null );
			if ( isset( $content ) ) {
				_wp_array_set( $this->theme_json, $path, $content );
			}

			// Replace the presets.
			foreach ( static::PRESETS_METADATA as $preset ) {
				$override_preset = ! static::get_metadata_boolean( $this->theme_json['settings'], $preset['prevent_override'], true );

				foreach ( static::VALID_ORIGINS as $origin ) {
					$base_path = array_merge( $node['path'], $preset['path'] );
					$path      = array_merge( $base_path, array( $origin ) );
					$content   = _wp_array_get( $incoming_data, $path, null );
					if ( ! isset( $content ) ) {
						continue;
					}

					if ( 'theme' === $origin && $preset['use_default_names'] ) {
						foreach ( $content as &$item ) {
							if ( ! array_key_exists( 'name', $item ) ) {
								$name = static::get_name_from_defaults( $item['slug'], $base_path );
								if ( null !== $name ) {
									$item['name'] = $name;
								}
							}
						}
					}

					if (
						( 'theme' !== $origin ) ||
						( 'theme' === $origin && $override_preset )
					) {
						_wp_array_set( $this->theme_json, $path, $content );
					} else {
						$slugs_for_preset = _wp_array_get( $slugs, $preset['path'], array() );
						$content          = static::filter_slugs( $content, $slugs_for_preset );
						_wp_array_set( $this->theme_json, $path, $content );
					}
				}
			}
		}
	}

	/**
	 * Converts all filter (duotone) presets into SVGs.
	 *
	 * @param array $origins List of origins to process.
	 *
	 * @return string SVG filters.
	 */
	public function get_svg_filters( $origins ) {
		$blocks_metadata = static::get_blocks_metadata();
		$setting_nodes   = static::get_setting_nodes( $this->theme_json, $blocks_metadata );

		$filters = '';
		foreach ( $setting_nodes as $metadata ) {
			$node = _wp_array_get( $this->theme_json, $metadata['path'], array() );
			if ( empty( $node['color']['duotone'] ) ) {
				continue;
			}

			$duotone_presets = $node['color']['duotone'];

			foreach ( $origins as $origin ) {
				if ( ! isset( $duotone_presets[ $origin ] ) ) {
					continue;
				}
				foreach ( $duotone_presets[ $origin ] as $duotone_preset ) {
					$filters .= gutenberg_get_duotone_filter_svg( $duotone_preset );
				}
			}
		}

		return $filters;
	}

	/**
	 * For metadata values that can either be booleans or paths to booleans, gets the value.
	 *
	 * ```php
	 * $data = array(
	 *   'color' => array(
	 *     'defaultPalette' => true
	 *   )
	 * );
	 *
	 * static::get_metadata_boolean( $data, false );
	 * // => false
	 *
	 * static::get_metadata_boolean( $data, array( 'color', 'defaultPalette' ) );
	 * // => true
	 * ```
	 *
	 * @param array      $data    The data to inspect.
	 * @param bool|array $path    Boolean or path to a boolean.
	 * @param bool       $default Default value if the referenced path is missing.
	 * @return boolean
	 */
	protected static function get_metadata_boolean( $data, $path, $default = false ) {
		if ( is_bool( $path ) ) {
			return $path;
		}

		if ( is_array( $path ) ) {
			$value = _wp_array_get( $data, $path );
			if ( null !== $value ) {
				return $value;
			}
		}

		return $default;
	}

	/**
	 * Given a styles array, it extracts the style properties
	 * and adds them to the $declarations array following the format:
	 *
	 * ```php
	 * array(
	 *   'name'  => 'property_name',
	 *   'value' => 'property_value,
	 * )
	 * ```
	 *
	 * @param array   $styles Styles to process.
	 * @param array   $settings Theme settings.
	 * @param array   $properties Properties metadata.
	 * @param string  $selector Selector for styles.
	 * @param boolean $use_root_vars Whether to use root variables.
	 * @return array Returns the modified $declarations.
	 */
	protected static function compute_style_properties( $styles, $settings = array(), $properties = null, $selector = null, $use_root_vars = null ) {
		if ( null === $properties ) {
			$properties = static::PROPERTIES_METADATA;
		}

		$declarations             = array();
		$root_variable_duplicates = array();

		if ( empty( $styles ) ) {
			return $declarations;
		}

		foreach ( $properties as $css_property => $value_path ) {
			$value = static::get_property_value( $styles, $value_path );

			if ( strpos( $css_property, '--wp--style--root--' ) === 0 && static::ROOT_BLOCK_SELECTOR !== $selector ) {
				continue;
			}

			if ( strpos( $css_property, '--wp--style--root--' ) === 0 && $use_root_vars ) {
				$root_variable_duplicates[] = substr( $css_property, strlen( '--wp--style--root--' ) );
			}

			// Root padding requires special logic to split shorthand values.
			if ( '--wp--style--root--padding' === $css_property && is_string( $value ) ) {

				$shorthand_top    = '0';
				$shorthand_right  = '0';
				$shorthand_bottom = '0';
				$shorthand_left   = '0';

				$separate_values = explode( ' ', $value );

				switch ( count( $separate_values ) ) {
					case 1:
						$shorthand_top    = $separate_values[0];
						$shorthand_right  = $separate_values[0];
						$shorthand_bottom = $separate_values[0];
						$shorthand_left   = $separate_values[0];
						break;
					case 2:
						$shorthand_top    = $separate_values[0];
						$shorthand_right  = $separate_values[1];
						$shorthand_bottom = $separate_values[0];
						$shorthand_left   = $separate_values[1];
						break;
					case 3:
						$shorthand_top    = $separate_values[0];
						$shorthand_right  = $separate_values[1];
						$shorthand_bottom = $separate_values[2];
						$shorthand_left   = $separate_values[1];
						break;
					case 4:
						$shorthand_top    = $separate_values[0];
						$shorthand_right  = $separate_values[1];
						$shorthand_bottom = $separate_values[2];
						$shorthand_left   = $separate_values[3];
						break;
				}

				$all_properties = array(
					array(
						'name'  => '--wp--style--root--padding-top',
						'value' => $shorthand_top,
					),
					array(
						'name'  => '--wp--style--root--padding-right',
						'value' => $shorthand_right,
					),
					array(
						'name'  => '--wp--style--root--padding-bottom',
						'value' => $shorthand_bottom,
					),
					array(
						'name'  => '--wp--style--root--padding-left',
						'value' => $shorthand_left,
					),
				);

				$declarations = array_merge( $declarations, $all_properties );

				continue;
			}

			// Look up protected properties, keyed by value path.
			// Skip protected properties that are explicitly set to `null`.
			if ( is_array( $value_path ) ) {
				$path_string = implode( '.', $value_path );
				if (
					array_key_exists( $path_string, static::PROTECTED_PROPERTIES ) &&
					_wp_array_get( $settings, static::PROTECTED_PROPERTIES[ $path_string ], null ) === null
				) {
					continue;
				}
			}

			// Skip if empty and not "0" or value represents array of longhand values.
			$has_missing_value = empty( $value ) && ! is_numeric( $value );
			if ( $has_missing_value || is_array( $value ) ) {
				continue;
			}

			$declarations[] = array(
				'name'  => $css_property,
				'value' => $value,
			);
		}

		// If a variable value is added to the root, the corresponding property should be removed.
		foreach ( $root_variable_duplicates as $duplicate ) {
			$discard = array_search( $duplicate, array_column( $declarations, 'name' ), true );
			if ( $discard ) {
				array_splice( $declarations, $discard, 1 );
			}
		}

		return $declarations;
	}

	/**
	 * Returns a valid theme.json as provided by a theme.
	 *
	 * Unlike get_raw_data() this returns the presets flattened, as provided by a theme.
	 * This also uses appearanceTools instead of their opt-ins if all of them are true.
	 *
	 * @return string[]
	 */
	public function get_data() {
		$output = $this->theme_json;
		$nodes  = static::get_setting_nodes( $output );

		/**
		 * Flatten the theme & custom origins into a single one.
		 *
		 * For example, the following:
		 *
		 * {
		 *   "settings": {
		 *     "color": {
		 *       "palette": {
		 *         "theme": [ {} ],
		 *         "custom": [ {} ]
		 *       }
		 *     }
		 *   }
		 * }
		 *
		 * will be converted to:
		 *
		 * {
		 *   "settings": {
		 *     "color": {
		 *       "palette": [ {} ]
		 *     }
		 *   }
		 * }
		 */
		foreach ( $nodes as $node ) {
			foreach ( static::PRESETS_METADATA as $preset_metadata ) {
				$path   = array_merge( $node['path'], $preset_metadata['path'] );
				$preset = _wp_array_get( $output, $path, null );
				if ( null === $preset ) {
					continue;
				}

				$items = array();
				if ( isset( $preset['theme'] ) ) {
					foreach ( $preset['theme'] as $item ) {
						$slug = $item['slug'];
						unset( $item['slug'] );
						$items[ $slug ] = $item;
					}
				}
				if ( isset( $preset['custom'] ) ) {
					foreach ( $preset['custom'] as $item ) {
						$slug = $item['slug'];
						unset( $item['slug'] );
						$items[ $slug ] = $item;
					}
				}
				$flattened_preset = array();
				foreach ( $items as $slug => $value ) {
					$flattened_preset[] = array_merge( array( 'slug' => $slug ), $value );
				}
				_wp_array_set( $output, $path, $flattened_preset );
			}
		}

		// If all of the static::APPEARANCE_TOOLS_OPT_INS are true,
		// this code unsets them and sets 'appearanceTools' instead.
		foreach ( $nodes as $node ) {
			$all_opt_ins_are_set = true;
			foreach ( static::APPEARANCE_TOOLS_OPT_INS as $opt_in_path ) {
				$full_path = array_merge( $node['path'], $opt_in_path );
				// Use "unset prop" as a marker instead of "null" because
				// "null" can be a valid value for some props (e.g. blockGap).
				$opt_in_value = _wp_array_get( $output, $full_path, 'unset prop' );
				if ( 'unset prop' === $opt_in_value ) {
					$all_opt_ins_are_set = false;
					break;
				}
			}

			if ( $all_opt_ins_are_set ) {
				_wp_array_set( $output, array_merge( $node['path'], array( 'appearanceTools' ) ), true );
				foreach ( static::APPEARANCE_TOOLS_OPT_INS as $opt_in_path ) {
					$full_path = array_merge( $node['path'], $opt_in_path );
					// Use "unset prop" as a marker instead of "null" because
					// "null" can be a valid value for some props (e.g. blockGap).
					$opt_in_value = _wp_array_get( $output, $full_path, 'unset prop' );
					if ( true !== $opt_in_value ) {
						continue;
					}

					// The following could be improved to be path independent.
					// At the moment it relies on a couple of assumptions:
					//
					// - all opt-ins having a path of size 2.
					// - there's two sources of settings: the top-level and the block-level.
					if (
						( 1 === count( $node['path'] ) ) &&
						( 'settings' === $node['path'][0] )
					) {
						// Top-level settings.
						unset( $output['settings'][ $opt_in_path[0] ][ $opt_in_path[1] ] );
						if ( empty( $output['settings'][ $opt_in_path[0] ] ) ) {
							unset( $output['settings'][ $opt_in_path[0] ] );
						}
					} elseif (
						( 3 === count( $node['path'] ) ) &&
						( 'settings' === $node['path'][0] ) &&
						( 'blocks' === $node['path'][1] )
					) {
						// Block-level settings.
						$block_name = $node['path'][2];
						unset( $output['settings']['blocks'][ $block_name ][ $opt_in_path[0] ][ $opt_in_path[1] ] );
						if ( empty( $output['settings']['blocks'][ $block_name ][ $opt_in_path[0] ] ) ) {
							unset( $output['settings']['blocks'][ $block_name ][ $opt_in_path[0] ] );
						}
					}
				}
			}
		}

		wp_recursive_ksort( $output );

		return $output;
	}

	/**
	 * Enables some settings.
	 *
	 * @since 5.9.0
	 *
	 * @param array $context The context to which the settings belong.
	 */
	protected static function do_opt_in_into_settings( &$context ) {
		foreach ( static::APPEARANCE_TOOLS_OPT_INS as $path ) {
			// Use "unset prop" as a marker instead of "null" because
			// "null" can be a valid value for some props (e.g. blockGap).
			if ( 'unset prop' === _wp_array_get( $context, $path, 'unset prop' ) ) {
				_wp_array_set( $context, $path, true );
			}
		}

		unset( $context['appearanceTools'] );
	}
}
