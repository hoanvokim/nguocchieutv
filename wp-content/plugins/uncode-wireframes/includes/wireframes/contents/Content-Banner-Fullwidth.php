<?php
/**
 * name             - Wireframe title
 * cat_name         - Comma separated list for multiple categories (cat display name)
 * custom_class     - Space separated list for multiple categories (cat ID)
 * dependency       - Array of dependencies
 * is_content_block - (optional) Best in a content block
 *
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$wireframe_categories = UNCDWF_Dynamic::get_wireframe_categories();
$data                 = array();

// Wireframe properties

$data[ 'name' ]             = esc_html__( 'Content Banner Fullwidth', 'uncode-wireframes' );
$data[ 'cat_name' ]         = $wireframe_categories[ 'contents' ];
$data[ 'custom_class' ]     = 'contents';
$data[ 'image_path' ]       = UNCDWF_THUMBS_URL . 'contents/Content-Banner-Fullwidth.jpg';
$data[ 'dependency' ]       = array();
$data[ 'is_content_block' ] = false;

// Wireframe content

$data[ 'content' ]      = '
[vc_row unlock_row_content="yes" row_height_percent="0" override_padding="yes" h_padding="2" top_padding="5" bottom_padding="5" overlay_alpha="50" gutter_size="2" column_width_percent="100" shift_y="0" z_index="0" shape_dividers=""][vc_column column_width_percent="100" overlay_alpha="50" gutter_size="3" medium_width="4" mobile_width="4" shift_x="0" shift_y="0" shift_y_down="0" z_index="0" width="1/4"][vc_single_image media="'. uncode_wf_print_single_image( '80471' ) .'" media_width_percent="100" media_ratio="three-four" advanced="yes" media_items="media|original,title" media_overlay_color="color-wayh" media_overlay_opacity="20" media_text_visible="yes" media_text_anim="no" media_overlay_visible="yes" media_overlay_anim="no" single_image_anim_move="yes" media_h_align="center" media_padding="3" media_title_dimension="h3" media_title_custom="Short headline" media_link="url:%23|||" media_subtitle_custom="Tagline"][/vc_column][vc_column column_width_percent="100" overlay_alpha="50" gutter_size="3" medium_width="4" mobile_width="4" shift_x="0" shift_y="0" shift_y_down="0" z_index="0" width="1/4"][vc_single_image media="'. uncode_wf_print_single_image( '80471' ) .'" media_width_percent="100" media_ratio="three-four" advanced="yes" media_items="media|original,title" media_overlay_color="color-wayh" media_overlay_opacity="20" media_text_visible="yes" media_text_anim="no" media_overlay_visible="yes" media_overlay_anim="no" single_image_anim_move="yes" media_h_align="center" media_padding="3" media_title_dimension="h3" media_title_custom="Short headline" media_link="url:%23|||" media_subtitle_custom="Tagline"][/vc_column][vc_column column_width_percent="100" overlay_alpha="50" gutter_size="3" medium_width="4" mobile_width="4" shift_x="0" shift_y="0" shift_y_down="0" z_index="0" width="1/4"][vc_single_image media="'. uncode_wf_print_single_image( '80471' ) .'" media_width_percent="100" media_ratio="three-four" advanced="yes" media_items="media|original,title" media_overlay_color="color-wayh" media_overlay_opacity="20" media_text_visible="yes" media_text_anim="no" media_overlay_visible="yes" media_overlay_anim="no" single_image_anim_move="yes" media_h_align="center" media_padding="3" media_title_dimension="h3" media_title_custom="Short headline" media_link="url:%23|||" media_subtitle_custom="Tagline"][/vc_column][vc_column column_width_percent="100" overlay_alpha="50" gutter_size="3" medium_width="4" mobile_width="4" shift_x="0" shift_y="0" shift_y_down="0" z_index="0" width="1/4"][vc_single_image media="'. uncode_wf_print_single_image( '80471' ) .'" media_width_percent="100" media_ratio="three-four" advanced="yes" media_items="media|original,title" media_overlay_color="color-wayh" media_overlay_opacity="20" media_text_visible="yes" media_text_anim="no" media_overlay_visible="yes" media_overlay_anim="no" single_image_anim_move="yes" media_h_align="center" media_padding="3" media_title_dimension="h3" media_title_custom="Short headline" media_link="url:%23|||" media_subtitle_custom="Tagline"][/vc_column][/vc_row]
';

// Check if this wireframe is for a content block
if ( $data[ 'is_content_block' ] && ! $is_content_block ) {
	$data[ 'custom_class' ] .= ' for-content-blocks';
}

// Check if this wireframe requires a plugin
foreach ( $data[ 'dependency' ]  as $dependency ) {
	if ( ! UNCDWF_Dynamic::has_dependency( $dependency ) ) {
		$data[ 'custom_class' ] .= ' has-dependency needs-' . $dependency;
	}
}

vc_add_default_templates( $data );
