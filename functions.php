<?php
/**
 * Sage includes
 *
 * The $sage_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 *
 * @link https://github.com/roots/sage/pull/1042
 */
$sage_includes = [
  'lib/assets.php',    // Scripts and stylesheets
  'lib/extras.php',    // Custom functions
  'lib/setup.php',     // Theme setup
  'lib/titles.php',    // Page titles
  'lib/wrapper.php',   // Theme wrapper class
  'lib/customizer.php' // Theme customizer
];

foreach ($sage_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'sage'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}

unset($file, $filepath);

/*Kent Custom Functions*/

add_filter("gform_ajax_spinner_url", "spinner_url", 10, 2);
function spinner_url($image_src, $form){
    return  get_bloginfo('template_directory') . '/dist/images/blank.gif' ; // relative to you theme images folder
}
function remove_cssjs_ver( $src ) {
    if( strpos( $src, '?ver=' ) )
        $src = remove_query_arg( 'ver', $src );
    return $src;
}
add_filter( 'style_loader_src', 'remove_cssjs_ver', 10, 2 );
add_filter( 'script_loader_src', 'remove_cssjs_ver', 10, 2 );

/*Behzad Custom Functions*/

class GW_Rounding {

    private static $instance = null;

    protected static $is_script_output = false;

    protected $class_regex = 'gw-round-(\w+)-?(\w+)?';

    public static function get_instance() {
        if( null == self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

    private function __construct( $args = array() ) {

        // make sure we're running the required minimum version of Gravity Forms
        if( ! property_exists( 'GFCommon', 'version' ) || ! version_compare( GFCommon::$version, '1.8', '>=' ) )
            return;

        // time for hooks
        add_filter( 'gform_pre_render',            array( $this, 'prepare_form_and_load_script' ) );
        add_filter( 'gform_register_init_scripts', array( $this, 'add_init_script' ) );
        add_filter( 'gform_enqueue_scripts',       array( $this, 'enqueue_form_scripts' ) );

        add_action( 'gform_pre_submission',     array( $this, 'override_submitted_value' ), 10, 5 );
        add_filter( 'gform_calculation_result', array( $this, 'override_submitted_calculation_value' ), 10, 5 );

    }

    function prepare_form_and_load_script( $form ) {

        if( ! $this->is_applicable_form( $form ) ) {
            return $form;
        }

        if( ! self::$is_script_output ) {
            $this->output_script();
        }

        foreach( $form['fields'] as &$field ) {
            if( preg_match( $this->get_class_regex(), $field['cssClass'] ) ) {
                $field['cssClass'] .= ' gw-rounding';
            }
        }

        return $form;
    }

    function output_script() {
        ?>

        <script type="text/javascript">

            var GWRounding;

            ( function( $ ) {

                GWRounding = function( args ) {

                    var self = this;

                    // copy all args to current object: (list expected props)
                    for( prop in args ) {
                        if( args.hasOwnProperty( prop ) )
                            self[prop] = args[prop];
                    }

                    self.init = function() {

                        self.fieldElems = $( '#gform_wrapper_' + self.formId + ' .gw-rounding' );

                        self.parseElemActions( self.fieldElems );

                        self.bindEvents();

                    }

                    self.parseElemActions = function( elems ) {

                        elems.each( function() {

                            var cssClasses      = $( this ).attr( 'class' ),
                                roundingActions = self.parseActions( cssClasses );

                            $( this ).data( 'gw-rounding', roundingActions );

                        } );

                    }

                    self.parseActions = function( str ) {

                        var matches         = getMatchGroups( String( str ), new RegExp( self.classRegex.replace( /\\/g, '\\' ), 'i' ) ),
                            roundingActions = [];

                        for( var i = 0; i < matches.length; i++ ) {

                            var action      = matches[i][1],
                                actionValue = matches[i][2];

                            if( typeof actionValue == 'undefined' ) {
                                actionValue = action;
                                action = 'round';
                            }

                            var roundingAction = {
                                'action':      action,
                                'actionValue': actionValue
                            };

                            roundingActions.push( roundingAction );

                        }

                        return roundingActions;
                    }

                    self.bindEvents = function() {

                        self.fieldElems.find( 'input' ).each( function() {
                            self.applyRoundingActions( $( this ) );
                        } ).blur( function() {
                            self.applyRoundingActions( $( this ) );
                        } );

                        gform.addFilter( 'gform_calculation_result', function( result, formulaField, formId, calcObj ) {

                            var $input = $( '#input_' + formId + '_' + formulaField.field_id )
                                $field = $input.parents( '.gfield' );

                            if( $field.hasClass( 'gw-rounding' ) ) {
                                result = self.getRoundedValue( $input, result );
                            }

                            return result;
                        } );

                    }

                    self.applyRoundingActions = function( $input ) {
                        var value = self.getRoundedValue( $input );
                        if( $input.val() != value ) {
                            $input.val( value ).change();
                        }
                    }

                    self.getRoundedValue = function( $input, value ) {

                        var $field  = $input.parents( '.gfield' ),
                            actions = $field.data( 'gw-rounding' );

                        // allows setting the 'gw-rounding' data for an element to null and it will be reparsed
                        if( actions === null ) {
                            self.parseElemActions( $field );
                            actions = $field.data( 'gw-rounding' );
                        }

                        if( typeof actions == 'undefined' || actions === false || actions.length <= 0 ) {
                            return;
                        }

                        if( typeof value == 'undefined' ) {
                            value = $input.val();
                        }

                        if( value == '' ) {
                            value = '';
                        } else {
                            for( var i = 0; i < actions.length; i++ ) {
                                value = GWRounding.round( value, actions[i].actionValue, actions[i].action );
                            }
                        }

                        return isNaN( value ) ? '' : value;
                    }

                    GWRounding.round = function( value, actionValue, action ) {

                        var interval, base, min, max;

                        value = parseInt( value );
                        actionValue = parseInt( actionValue );

                        switch( action ) {
                            case 'min':
                                min = actionValue;
                                if( value < min ) {
                                    value = min;
                                }
                                break;
                            case 'max':
                                max = actionValue;
                                if( value > max ) {
                                    value = max;
                                }
                                break;
                            case 'up':
                                interval = actionValue;
                                base     = Math.ceil( value / interval );
                                value    = base * interval;
                                break;
                            case 'down':
                                interval = actionValue;
                                base     = Math.floor( value / interval );
                                value    = base * interval;
                                break;
                            default:
                                interval = actionValue;
                                base     = Math.round( value / interval );
                                value    = base * interval;
                                break;
                        }

                        return parseInt( value );
                    }

                    self.init();

                }

            } )( jQuery );

        </script>

        <?php

        self::$is_script_output = true;

    }

    function add_init_script( $form ) {

        if( ! $this->is_applicable_form( $form ) ) {
            return;
        }

        $args = array(
            'formId'    => $form['id'],
            'classRegex' => $this->class_regex
        );
        $script = 'new GWRounding( ' . json_encode( $args ) . ' );';

        GFFormDisplay::add_init_script( $form['id'], 'gw_rounding', GFFormDisplay::ON_PAGE_RENDER, $script );

    }

    function enqueue_form_scripts( $form ) {

        if( $this->is_applicable_form( $form ) ) {
            wp_enqueue_script( 'gform_gravityforms' );
        }

    }

    function override_submitted_value( $form ) {

        foreach( $form['fields'] as $field ) {
            if( $this->is_applicable_field( $field ) ) {
                $value = $this->process_rounding_actions( rgpost( "input_{$field['id']}" ), $this->get_rounding_actions( $field ) );
                $_POST[ "input_{$field['id']}" ] = $value;
            }
        }

    }

    function override_submitted_calculation_value( $result, $formula, $field, $form, $entry ) {

        if( $this->is_applicable_field( $field ) ) {
            $result = $this->process_rounding_actions( $result, $this->get_rounding_actions( $field ) );
        }

        return $result;
    }

    function process_rounding_actions( $value, $actions ) {

        foreach( $actions as $action ) {
            $value = $this->round( $value, $action['action'], $action['action_value'] );
        }

        return $value;
    }

    function round( $value, $action, $action_value ) {

        $value = intval( $value );
        $action_value = intval( $action_value );

        switch( $action ) {
            case 'min':
                $min = $action_value;
                if( $value < $min ) {
                    $value = $min;
                }
                break;
            case 'max':
                $max = $action_value;
                if( $value > $max ) {
                    $value = $max;
                }
                break;
            case 'up':
                $interval = $action_value;
                $base     = ceil( $value / $interval );
                $value    = $base * $interval;
                break;
            case 'down':
                $interval = $action_value;
                $base     = floor( $value / $interval );
                $value    = $base * $interval;
                break;
            default:
                $interval = $action_value;
                $base     = round( $value / $interval );
                $value    = $base * $interval;
                break;
        }

        return intval( $value );
    }

    // # HELPERS

    function is_applicable_form( $form ) {

        foreach( $form['fields'] as $field ) {
            if( $this->is_applicable_field( $field ) ) {
                return true;
            }
        }

        return false;
    }

    function is_applicable_field( $field ) {
        return preg_match( $this->get_class_regex(), rgar( $field, 'cssClass' ) ) == true;
    }

    function get_class_regex() {
        return "/{$this->class_regex}/";
    }

    function get_rounding_actions( $field ) {

        $actions = array();

        preg_match_all( $this->get_class_regex(), rgar( $field, 'cssClass' ), $matches, PREG_SET_ORDER );

        foreach( $matches as $match ) {

            list( $full_match, $action, $action_value ) = array_pad( $match, 3, false );

            if( $action_value === false ) {
                $action_value = $action;
                $action = 'round';
            }

            $action = array(
                'action'       => $action,
                'action_value' => $action_value
            );

            $actions[] = $action;

        }

        return $actions;
    }

}

function gw_rounding() {
    return GW_Rounding::get_instance();
}

gw_rounding();


/**
* Calculation Subtotal Merge Tag
*
* Adds a {subtotal} merge tag which calculates the subtotal of the form. This merge tag can only be used
* within the "Formula" setting of Calculation-enabled fields (i.e. Number, Calculated Product).
*
* @author    David Smith <david@gravitywiz.com>
* @license   GPL-2.0+
* @link      http://gravitywiz.com/subtotal-merge-tag-for-calculations/
* @copyright 2013 Gravity Wiz
*/
class GWCalcSubtotal {

    public static $merge_tag = '{subtotal}';

    function __construct() {

        // front-end
        add_filter( 'gform_pre_render', array( $this, 'maybe_replace_subtotal_merge_tag' ) );
        add_filter( 'gform_pre_validation', array( $this, 'maybe_replace_subtotal_merge_tag_submission' ) );

        // back-end
        add_filter( 'gform_admin_pre_render', array( $this, 'add_merge_tags' ) );

    }

    /**
    * Look for {subtotal} merge tag in form fields 'calculationFormula' property. If found, replace with the
    * aggregated subtotal merge tag string.
    *
    * @param mixed $form
    */
    function maybe_replace_subtotal_merge_tag( $form, $filter_tags = false ) {
        
        foreach( $form['fields'] as &$field ) {
            
            if( current_filter() == 'gform_pre_render' && rgar( $field, 'origCalculationFormula' ) )
                $field['calculationFormula'] = $field['origCalculationFormula'];
            
            if( ! self::has_subtotal_merge_tag( $field ) )
                continue;

            $subtotal_merge_tags = self::get_subtotal_merge_tag_string( $form, $field, $filter_tags );
            $field['origCalculationFormula'] = $field['calculationFormula'];
            $field['calculationFormula'] = str_replace( self::$merge_tag, $subtotal_merge_tags, $field['calculationFormula'] );

        }

        return $form;
    }
    
    function maybe_replace_subtotal_merge_tag_submission( $form ) {
        return $this->maybe_replace_subtotal_merge_tag( $form, true );
    }

    /**
    * Get all the pricing fields on the form, get their corresponding merge tags and aggregate them into a formula that
    * will yeild the form's subtotal.
    *
    * @param mixed $form
    */
    static function get_subtotal_merge_tag_string( $form, $current_field, $filter_tags = false ) {
        
        $pricing_fields = self::get_pricing_fields( $form );
        $product_tag_groups = array();
        
        foreach( $pricing_fields['products'] as $product ) {

            $product_field = rgar( $product, 'product' );
            $option_fields = rgar( $product, 'options' );
            $quantity_field = rgar( $product, 'quantity' );

            // do not include current field in subtotal
            if( $product_field['id'] == $current_field['id'] )
                continue;

            $product_tags = GFCommon::get_field_merge_tags( $product_field );
            $quantity_tag = 1;

            // if a single product type, only get the "price" merge tag
            if( in_array( GFFormsModel::get_input_type( $product_field ), array( 'singleproduct', 'calculation', 'hiddenproduct' ) ) ) {

                // single products provide quantity merge tag
                if( empty( $quantity_field ) && ! rgar( $product_field, 'disableQuantity' ) )
                    $quantity_tag = $product_tags[2]['tag'];

                $product_tags = array( $product_tags[1] );
            }

            // if quantity field is provided for product, get merge tag
            if( ! empty( $quantity_field ) ) {
                $quantity_tag = GFCommon::get_field_merge_tags( $quantity_field );
                $quantity_tag = $quantity_tag[0]['tag'];
            }
            
            if( $filter_tags && ! self::has_valid_quantity( $quantity_tag ) )
                continue;
            
            $product_tags = wp_list_pluck( $product_tags, 'tag' );
            $option_tags = array();
            
            foreach( $option_fields as $option_field ) {

                if( is_array( $option_field['inputs'] ) ) {

                    $choice_number = 1;

                    foreach( $option_field['inputs'] as &$input ) {

                        //hack to skip numbers ending in 0. so that 5.1 doesn't conflict with 5.10
                        if( $choice_number % 10 == 0 )
                            $choice_number++;

                        $input['id'] = $option_field['id'] . '.' . $choice_number++;

                    }
                }

                $new_options_tags = GFCommon::get_field_merge_tags( $option_field );
                if( ! is_array( $new_options_tags ) )
                    continue;

                if( GFFormsModel::get_input_type( $option_field ) == 'checkbox' )
                    array_shift( $new_options_tags );

                $option_tags = array_merge( $option_tags, $new_options_tags );
            }

            $option_tags = wp_list_pluck( $option_tags, 'tag' );

            $product_tag_groups[] = '( ( ' . implode( ' + ', array_merge( $product_tags, $option_tags ) ) . ' ) * ' . $quantity_tag . ' )';

        }

        $shipping_tag = 0;
        /* Shipping should not be included in subtotal, correct?
        if( rgar( $pricing_fields, 'shipping' ) ) {
            $shipping_tag = GFCommon::get_field_merge_tags( rgars( $pricing_fields, 'shipping/0' ) );
            $shipping_tag = $shipping_tag[0]['tag'];
        }*/

        $pricing_tag_string = '( ( ' . implode( ' + ', $product_tag_groups ) . ' ) + ' . $shipping_tag . ' )';

        return $pricing_tag_string;
    }
    
    /**
    * Get all pricing fields from a given form object grouped by product and shipping with options nested under their
    * respective products.
    *
    * @param mixed $form
    */
    static function get_pricing_fields( $form ) {

        $product_fields = array();

        foreach( $form["fields"] as $field ) {

            if( $field["type"] != 'product' )
                continue;

            $option_fields = GFCommon::get_product_fields_by_type($form, array("option"), $field['id'] );

            // can only have 1 quantity field
            $quantity_field = GFCommon::get_product_fields_by_type( $form, array("quantity"), $field['id'] );
            $quantity_field = rgar( $quantity_field, 0 );

            $product_fields[] = array(
                'product' => $field,
                'options' => $option_fields,
                'quantity' => $quantity_field
                );

        }

        $shipping_field = GFCommon::get_fields_by_type($form, array("shipping"));

        return array( "products" => $product_fields, "shipping" => $shipping_field );
    }
    
    static function has_valid_quantity( $quantity_tag ) {

        if( is_numeric( $quantity_tag ) ) {

            $qty_value = $quantity_tag;

        } else {

            // extract qty input ID from the merge tag
            preg_match_all( '/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $quantity_tag, $matches, PREG_SET_ORDER );
            $qty_input_id = rgars( $matches, '0/1' );
            $qty_value = rgpost( 'input_' . str_replace( '.', '_', $qty_input_id ) );

        }
        
        return floatval( $qty_value ) > 0;
    }
    
    function add_merge_tags( $form ) {

        $label = __('Subtotal', 'gravityforms');

        ?>

        <script type="text/javascript">

            // for the future (not yet supported for calc field)
            gform.addFilter("gform_merge_tags", "gwcs_add_merge_tags");
            function gwcs_add_merge_tags( mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option ) {
                mergeTags["pricing"].tags.push({ tag: '<?php echo self::$merge_tag; ?>', label: '<?php echo $label; ?>' });
                return mergeTags;
            }

            // hacky, but only temporary
            jQuery(document).ready(function($){

                var calcMergeTagSelect = $('#field_calculation_formula_variable_select');
                calcMergeTagSelect.find('optgroup').eq(0).append( '<option value="<?php echo self::$merge_tag; ?>"><?php echo $label; ?></option>' );

            });

        </script>

        <?php
        //return the form object from the php hook
        return $form;
    }

    static function has_subtotal_merge_tag( $field ) {
        
        // check if form is passed
        if( isset( $field['fields'] ) ) {

            $form = $field;
            foreach( $form['fields'] as $field ) {
                if( self::has_subtotal_merge_tag( $field ) )
                    return true;
            }

        } else {

            if( isset( $field['calculationFormula'] ) && strpos( $field['calculationFormula'], self::$merge_tag ) !== false )
                return true;

        }

        return false;
    }

}

new GWCalcSubtotal();

/**
* Merge Tags as Dynamic Population Parameters
* http://gravitywiz.com/dynamic-products-via-post-meta/
*/
add_filter('gform_pre_render', 'gw_prepopluate_merge_tags');
function gw_prepopluate_merge_tags($form) {
    
    $filter_names = array();
    
    foreach($form['fields'] as &$field) {
        
        if(!rgar($field, 'allowsPrepopulate'))
            continue;
        
        // complex fields store inputName in the "name" property of the inputs array
        if(is_array(rgar($field, 'inputs')) && $field['type'] != 'checkbox') {
            foreach($field['inputs'] as $input) {
                if(rgar($input, 'name'))
                    $filter_names[] = array('type' => $field['type'], 'name' => rgar($input, 'name'));
            }
        } else {
            $filter_names[] = array('type' => $field['type'], 'name' => rgar($field, 'inputName'));
        }
        
    }
    
    foreach($filter_names as $filter_name) {
        
        $filtered_name = GFCommon::replace_variables_prepopulate($filter_name['name']);
        
        if($filter_name['name'] == $filtered_name)
            continue;
        
        add_filter("gform_field_value_{$filter_name['name']}", create_function("", "return '$filtered_name';"));
    }
    
    return $form;
}

/**
* Gravity Wiz // Require Minimum Character Limit for Gravity Forms
* 
* Adds support for requiring a minimum number of characters for text-based Gravity Form fields.
* 
* @version   1.0
* @author    David Smith <david@gravitywiz.com>
* @license   GPL-2.0+
* @link      http://gravitywiz.com/...
* @copyright 2013 Gravity Wiz
*/
class GW_Minimum_Characters {
    
    public function __construct( $args = array() ) {
        
        // make sure we're running the required minimum version of Gravity Forms
        if( ! property_exists( 'GFCommon', 'version' ) || ! version_compare( GFCommon::$version, '1.7', '>=' ) )
            return;
        
        // set our default arguments, parse against the provided arguments, and store for use throughout the class
        $this->_args = wp_parse_args( $args, array( 
            'form_id' => false,
            'field_id' => false,
            'min_chars' => 0,
            'max_chars' => false,
            'validation_message' => false,
            'min_validation_message' => __( 'Please enter at least %s characters.' ),
            'max_validation_message' => __( 'You may only enter %s characters.' )
        ) );
        
        extract( $this->_args );
        
        if( ! $form_id || ! $field_id || ! $min_chars )
            return;
        
        // time for hooks
        add_filter( "gform_field_validation_{$form_id}_{$field_id}", array( $this, 'validate_character_count' ), 10, 4 );
        
    }
    
    public function validate_character_count( $result, $value, $form, $field ) {

        $char_count = strlen( $value );
        $is_min_reached = $this->_args['min_chars'] !== false && $char_count >= $this->_args['min_chars'];
        $is_max_exceeded = $this->_args['max_chars'] !== false && $char_count > $this->_args['max_chars'];

        if( ! $is_min_reached ) {

            $message = $this->_args['validation_message'];
            if( ! $message )
                $message = $this->_args['min_validation_message'];

            $result['is_valid'] = false;
            $result['message'] = sprintf( $message, $this->_args['min_chars'] );

        } else if( $is_max_exceeded ) {

            $message = $this->_args['max_validation_message'];
            if( ! $message )
                $message = $this->_args['validation_message'];

            $result['is_valid'] = false;
            $result['message'] = sprintf( $message, $this->_args['max_chars'] );

        }
        
        return $result;
    }
    
}

# Configuration

new GW_Minimum_Characters( array( 
    'form_id' => 1,
    'field_id' => 172,
    'min_chars' => 6,
    'max_chars' => 7,
    'min_validation_message' => __( 'You need to enter at least %s Postal Code characters.' ),
    'max_validation_message' => __( 'You can only enter %s characters.' )
) );

/**
 * Gravity Wiz // Gravity Forms // Populate Date
 *
 * Provides the ability to populate a Date field with a modified date based on the current date or a user-submitted date. If the
 * modified date is based on a user-submitted date, the modified date can only be populated once the form has been submitted.
 *
 * @version	  1.3
 * @author    David Smith <david@gravitywiz.com>
 * @license   GPL-2.0+
 * @link      http://gravitywiz.com/populate-dates-gravity-form-fields/
 */
class GW_Populate_Date {

    public function __construct( $args = array() ) {

        // set our default arguments, parse against the provided arguments, and store for use throughout the class
        $this->_args = wp_parse_args( $args, array(
            'form_id'         => false,
            'target_field_id' => false,
            'source_field_id' => false,
            'format'          => 'm/d/Y',
            'modifier'        => false,
            'min_date'        => false
        ) );

        if( ! $this->_args['form_id'] || ! $this->_args['target_field_id'] ) {
            return;
        }

        // time for hooks
        add_action( 'init', array( $this, 'init' ) );

    }

    public function init() {

        // make sure we're running the required minimum version of Gravity Forms
        if( ! property_exists( 'GFCommon', 'version' ) || ! version_compare( GFCommon::$version, '1.8', '>=' ) ) {
            return;
        }

        if( $this->_args['source_field_id'] ) {
            add_action( 'gform_pre_submission', array( $this, 'populate_date_on_pre_submission' ) );
        } else {
            add_filter( 'gform_pre_render', array( $this, 'populate_date_on_pre_render' ) );
        }

    }

    public function populate_date_on_pre_render( $form ) {

        if( ! $this->is_applicable_form( $form ) ) {
            return $form;
        }

        foreach( $form['fields'] as &$field ) {
            if( $field['id'] == $this->_args['target_field_id'] ) {

                $key = sprintf( 'gwpd_%d_%d', $form['id'], $field['id'] );
                $value = $this->get_modified_date( $field );

                $field['allowsPrepopulate'] = true;
                $field['inputName'] = $key;

                add_filter("gform_field_value_{$key}", create_function( '', 'return \'' . $value . '\';' ) );

            }
        }

        return $form;
    }

    public function populate_date_on_pre_submission( $form ) {

        if( ! $this->is_applicable_form( $form ) ) {
            return;
        }

        foreach( $form['fields'] as &$field ) {
            if( $field['id'] == $this->_args['target_field_id'] ) {

                $timestamp = $this->get_source_timestamp( GFFormsModel::get_field( $form, $this->_args['source_field_id'] ) ); 
                $value = $this->get_modified_date( $field, $timestamp );

                $_POST[ "input_{$field['id']}" ] = $value;

            }
        }

    }

    public function get_source_timestamp( $field ) {

        $raw = rgpost( 'input_' . $field['id'] );
        if( is_array( $raw ) ) {
            $raw = array_filter( $raw );
        }

        list( $format, $divider ) = $field['dateFormat'] ? array_pad( explode( '_', $field['dateFormat' ] ), 2, 'slash' ) : array( 'mdy', 'slash' );
        $dividers = array( 'slash' => '/', 'dot' => '.', 'dash' => '-' );

        if( empty( $raw ) ) {
            $raw = date( implode( $dividers[ $divider ], str_split( $format ) ) );
        }

        $date = ! is_array( $raw ) ? explode( $dividers[ $divider ], $raw ) : $raw;

        $month = $date[ strpos( $format, 'm' ) ];
        $day   = $date[ strpos( $format, 'd' ) ];
        $year  = $date[ strpos( $format, 'y' ) ];

        $timestamp = mktime( 0, 0, 0, $month, $day, $year );

        return $timestamp;
    }

    public function get_modified_date( $field, $timestamp = false ) {

        if( ! $timestamp ) {
            $timestamp = current_time( 'timestamp' );
        }

        if( GFFormsModel::get_input_type( $field ) == 'date' ) {

            list( $format, $divider ) = $field['dateFormat'] ? array_pad( explode( '_', $field['dateFormat' ] ), 2, 'slash' ) : array( 'mdy', 'slash' );
            $dividers = array( 'slash' => '/', 'dot' => '.', 'dash' => '-' );

            $format = str_replace( 'y', 'Y', $format );
            $divider = $dividers[$divider];
            $format = implode( $divider, str_split( $format ) );

        } else {

            $format = $this->_args['format'];

        }

        if( $this->_args['modifier'] ) {
            $timestamp = strtotime( $this->_args['modifier'], $timestamp );
        }

        if( $this->_args['min_date'] ) {
            $min_timestamp = strtotime( $this->_args['min_date'] ) ? strtotime( $this->_args['min_date'] ) : $this->_args['min_date'];
            if( $min_timestamp > $timestamp ) {
                $timestamp = $min_timestamp;
            }
        }

        $date = date( $format, $timestamp );

        return $date;
    }

    function is_applicable_form( $form ) {

        $form_id = isset( $form['id'] ) ? $form['id'] : $form;

        return $form_id == $this->_args['form_id'];
    }

}

/* Excalibur - Antique Tractor Insurance */

/* Define a policy period of 1 year. */
new GW_Populate_Date( array(
    'form_id' => 10,
    'target_field_id' => 231,
    'source_field_id' => 6,
    'modifier' => '+1 year'
) );

/* Calculate the cutoff year for antique tractor coverage. */
add_filter( 'gform_field_value_antiqueTractorCoverageCutoffYear', 'getAntiqueTractorCoverageCutoffYear' );

function getAntiqueTractorCoverageCutoffYear( $value ) {
    $minimumAgeForCoverage = 40;
    return (date("Y") - $minimumAgeForCoverage);
}


# Configuration

new GW_Populate_Date( array(
    'form_id' => 1,
    'target_field_id' => 209,
    'source_field_id' => 12,
    'modifier' => '+1 year'
) );


# Gravity Forms 

add_filter( 'gform_currencies', 'update_currency' );
function update_currency( $currencies ) {
    $currencies['CAD'] = array(
        'name'               => __( 'CAD', 'gravityforms' ),
        'symbol_left'        => '$',
        'symbol_right'       => '',
        'symbol_padding'     => '',
        'thousand_separator' => ',',
        'decimal_separator'  => '.',
        'decimals'           => 2
    );

    return $currencies;
}


# Theme Updates

require_once('wp-updates-theme.php');
new WPUpdatesThemeUpdater_1877( 'http://wp-updates.com/api/2/theme', basename( get_template_directory() ) );

