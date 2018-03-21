<?php

/*
Plugin Name: WPML Example Page Builder
Author: OnTheGoSystems
Version: 1.0
*/

/*
 * This shows how to add translation support for plugins that store the
 * page builder layout in a custom format.
 *
 * In this case we're assuming that the plugin is storing the layout in a custom field
 * of the post.
 *
 * We need to include three hooks from WPML to do this.
 * 1) 'wpml_page_builder_support_required' to tell WPML that we need page builder support
 * 2) 'wpml_page_builder_register_strings' so we can register strings from our layout with WPML
 * 3) 'wpml_page_builder_string_translated' to receive translations from WPML so we can build the translated layout
 *
 */

define( 'POST_META_FIELD_FOR_PAGE_BUILDER', 'page-builder-json' );

global $sample_page_builder_json;

$sample_page_builder_json = wp_json_encode(
	array(
		'elements' => array(
			array( 'id' => 1, 'text' => 'Text to translate for first field' ),
			array( 'id' => 2, 'text' => 'Text to translate for second field' ),
		)
	)
);

add_filter( 'wpml_page_builder_support_required', 'wpml_example_page_builder_support_required', 10, 1 );

function wpml_example_page_builder_support_required( $plugins ) {
	$plugins[] = 'WPML Example Page Builder'; // Include an identifier for this plugin

	return $plugins;
}

add_action( 'wpml_page_builder_register_strings', 'wpml_example_page_builder_register_strings', 10, 2 );

function wpml_example_page_builder_register_strings( $post, $package_data ) {
	global $sample_page_builder_json;

	// Make sure the package is for our plugin
	if ( 'WPML Example Page Builder' === $package_data['kind'] ) {
		$post_data = get_post_meta( $post->ID, POST_META_FIELD_FOR_PAGE_BUILDER, true );

		if ( ! $post_data ) {
			// Save the sample page builder json if it's not present so we have something to translate.
			$post_data = $sample_page_builder_json;
			update_post_meta( $post->ID, POST_META_FIELD_FOR_PAGE_BUILDER, $post_data );
		}

		if ( $post_data ) {
			$post_data = json_decode( $post_data );

			// go through the elements and register the strings.

			foreach ( $post_data->elements as $element ) {
				do_action(
					'wpml_register_string',
					$element->text,
					'example-element-' . $element->id,
					$package_data,
					'Example Element Text',
					'LINE'
				);
			}
		}
	}
}

add_action( 'wpml_page_builder_string_translated', 'wpml_example_page_builder_string_translated', 10, 5 );

function wpml_example_page_builder_string_translated(
	$package_kind,
	$translated_post_id,
	$original_post,
	$string_translations,
	$lang
) {
	// Make sure the package is for our plugin
	if ( 'WPML Example Page Builder' === $package_kind ) {

		// Get the data from the original post
		// We'll then update the data with the translated strings and
		// save to the translated post.

		$post_data = get_post_meta( $original_post->ID, POST_META_FIELD_FOR_PAGE_BUILDER, true );
		if ( $post_data ) {
			$post_data = json_decode( $post_data );

			// Go through the string translations and match the names to the element ids.

			foreach ( $string_translations as $name => $translation_data ) {
				// find id from name we used to register the strings. eg example-element-xxx
				$id = substr( $name, strlen( 'example-element-' ) );
				if ( isset( $translation_data[ $lang ] ) && $translation_data[ $lang ]['status'] == 10 ) { // 10 means translation complete
					foreach( $post_data->elements as &$element ) {
						if ( $element->id == $id ) {
							// We've found the element so update the text
							$element->text = $translation_data[ $lang ]['value'];
						}
					}
				}
			}

			// Save the post data that now includes the translations to the translated post.
			update_post_meta( $translated_post_id, POST_META_FIELD_FOR_PAGE_BUILDER, wp_json_encode( $post_data ) );

		}
	}
}

?>

