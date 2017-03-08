<?php

namespace Roots\Sage\Customizer;

use Roots\Sage\Assets;

/**
 * Add postMessage support
 */
function customize_register($wp_customize) {
	$wp_customize->get_setting('blogname')->transport = 'postMessage';
	$wp_customize->get_section('title_tagline')->title = __('Broker Identity','sage');
	$wp_customize->get_section('title_tagline')->priority = 1;

	remove_action( 'customize_controls_enqueue_scripts', array( $wp_customize->nav_menus, 'enqueue_scripts' ) );
	remove_action( 'customize_register', array( $wp_customize->nav_menus, 'customize_register' ), 11 );
	remove_filter( 'customize_dynamic_setting_args', array( $wp_customize->nav_menus, 'filter_dynamic_setting_args' ) );
	remove_filter( 'customize_dynamic_setting_class', array( $wp_customize->nav_menus, 'filter_dynamic_setting_class' ) );
	remove_action( 'customize_controls_print_footer_scripts', array( $wp_customize->nav_menus, 'print_templates' ) );
	remove_action( 'customize_controls_print_footer_scripts', array( $wp_customize->nav_menus, 'available_items_template' ) );
	remove_action( 'customize_preview_init', array( $wp_customize->nav_menus, 'customize_preview_init' ) );

	$wp_customize->remove_panel( 'widgets' );
	$wp_customize->remove_section( 'static_front_page' );

	$wp_customize->add_section(
	'color_section',
	array(
	'title' => 'Broker Colours',
	'description' => 'Configure the form colours to match broker branding',
	'priority' => 56,
	)
	);

	$wp_customize->add_setting(
	'button_color',
	array(
    'default'     => '#27ae60',
    'transport'   => 'refresh',
	)
	);			
	$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'color_section4', array(
		'label'        => __( 'Button Colour', 'sage' ),
		'description' => 'Use the broker brand colour',
		'section'    => 'color_section',
		'settings'   => 'button_color',
	) ) );
	$wp_customize->add_setting(
	'button_color_hover',
	array(
    'default'     => '#27ae60',
    'transport'   => 'refresh',
	)
	);			
	$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'color_section', array(
		'label'        => __( 'Button Hover Colour', 'sage' ),
		'description' => 'Typically this is a slightly darker shade of the broker brand colour',
		'section'    => 'color_section',
		'settings'   => 'button_color_hover',
	) ) );
	$wp_customize->add_setting(
	'button_shadow',
	array(
    'default'     => '#ffffff',
    'transport'   => 'refresh',
	)
	);			
	$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'color_section1', array(
		'label'        => __( 'Button Shadow Colour', 'sage' ),
		'description' => 'Use a slightly darker shade of the button colour. For no shadow, leave this as the default white.',
		'section'    => 'color_section',
		'settings'   => 'button_shadow',
	) ) );


	$wp_customize->add_setting(
	'heading_color',
	array(
    'default'     => '#27ae60',
    'transport'   => 'refresh',
	)
	);			
	$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'color_section3', array(
		'label'        => __( 'Heading Colour', 'sage' ),
		'section'    => 'color_section',
		'settings'   => 'heading_color',
	) ) );

	$wp_customize->add_setting(
		'label_color',
		array(
		'default'     => '#333333',
		'transport'   => 'refresh',
		)
	);			
	$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'color_section3', array(
		'label'        => __( 'Label Colour', 'sage' ),
		'section'    => 'color_section',
		'settings'   => 'label_color',
	) ) );

	$wp_customize->add_setting(
		'premium_color',
		array(
		'default'     => '#f5f5f5',
		'transport'   => 'refresh',
		)
	);			
	$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'color_section5', array(
		'label'        => __( 'Annual Premium Background Colour', 'sage' ),
		'section'    => 'color_section',
		'settings'   => 'premium_color',
	) ) );
		
	
$wp_customize->add_section( 'themeslug_logo_section' , array(
    'title'       => __( 'Logo', 'themeslug' ),
    'priority'    => 30,
    'description' => 'Upload a logo to display at the top of the form',
) );
	

$wp_customize->add_setting( 'themeslug_logo' );

$wp_customize->add_control( new \WP_Customize_Image_Control( $wp_customize, 'themeslug_logo', array(
    'label'    => __( 'Logo', 'themeslug' ),
    'section'  => 'themeslug_logo_section',
    'settings' => 'themeslug_logo',
) ) );

	
	
	
	$wp_customize->add_section(
	'more_info',
	array(
	'title' => 'Theme Info',
	'description' => 'This theme has been designed to work with Gravity Forms.<br><br>Classes available to add to any fields are:<br><br>
	hide_instruction<br>
	gf_invisible<br>
	gf_hidden<br><br>
	
	gf_left_half<br>
	gf_right_half<br><br>
	
	gf_left_third<br>
	gf_middle_third<br>
	gf_right_third<br><br>
	
	gf_list_2col<br>
	gf_list_3col<br>
	gf_list_5col<br>
	gf_list_4col<br><br>
	
	gf_divider_above<br>
	gf_divider_below<br>
	
	gf_more_bottom_padding<br>
	gf_max_bottom_padding<br>
	gf_no_bottom_padding<br><br>
	
	gf_more_top_padding<br>
	gf_max_top_padding<br>
	gf_no_top_padding<br><br>
	<br><br>To request any other classes, please email krygiel@gmail.com',
	'priority' => 57,
	)
	);

	$wp_customize->add_setting(
	'peace',
	array(
    'default'     => '#000',
    'transport'   => 'refresh',
	)
	);			
	$wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'color_section2', array(
		'label'        => __( 'Peace', 'sage' ),
		'section'    => 'more_info',
		'settings'   => 'peace',
	) ) );


}

add_action('customize_register', __NAMESPACE__ . '\\customize_register');

/**
 * Customizer JS
 */
function customize_preview_js() {
  wp_enqueue_script('sage/customizer', Assets\asset_path('scripts/customizer.js'), ['customize-preview'], null, true);
}
add_action('customize_preview_init', __NAMESPACE__ . '\\customize_preview_js');
