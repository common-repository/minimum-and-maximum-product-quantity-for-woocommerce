<?php
/*
  * Plugin Name: Minimum and Maximum Product Quantity for WooCommerce
  * Plugin URI: 
  * Description: "Minimum and Maximum Product Quantity for WooCommerce" plugin will allow the site admin to enable the feature of minimum and maximum purchase qty for individual products. There is an additional functionality to set the allowed quantity in the multiple of a number.
  * Author: Sunarc
  * Author URI: https://www.suncartstore.com/
  * Version: 1.0.7
  * Text Domain: mmqwp
 */

if (!defined("ABSPATH"))
      exit;


/* Add a setting tab on product page */
add_filter('woocommerce_product_data_tabs', 'mmqwp_add_product_setting_tab' );
function mmqwp_add_product_setting_tab( $tabs ){
 
  $tabs['mmqwp'] = array(
      'label'    => 'Min Max Quantity',
      'target'   => 'minmaxqty',
      'class'    => array('show_if_simple','show_if_variable'),
      'priority' => 21,
    );
    return $tabs;
 
}
 
/* Add setting tab fields */
add_action( 'woocommerce_product_data_panels', 'mmqwp_add_plugin_settings' );
function mmqwp_add_plugin_settings(){
 
  echo '<div id="minmaxqty" class="panel woocommerce_options_panel hidden">';

  woocommerce_wp_checkbox( array(
    'id'                => '_mmqwp_option_enable',
    'value'             => get_post_meta( get_the_ID(), '_mmqwp_option_enable', true ),
    'label'             => 'Enable',
    'desc_tip'          => true,
    'description'       => __( 'Enable or disable', 'mmqwp' )
  ) );
 
  echo '<div id="" class="mmqwp_qty hidden">';
  echo '<style>div.mmqwp_qty.hidden { display:none; }</style>';

  woocommerce_wp_text_input( array(
    'id'                => '_mmqwp_min',
    'value'             => get_post_meta( get_the_ID(), '_mmqwp_min', true ),
    'label'             => 'Minimum Quantity',
    'desc_tip'          => true,
    'description'       => __( 'Minimum quantity for this product', 'mmqwp' ) ,
    'type'              => 'number', 
    'custom_attributes' => array('step'  => 'any','min' => '1')
  ) );
 
  woocommerce_wp_text_input( array(
    'id'          => '_mmqwp_max',
    'value'       => get_post_meta( get_the_ID(), '_mmqwp_max', true ),
    'label'       => 'Maximum Quantity',
    'desc_tip'    => true,
    'description' => __( 'Maximum quantity for this product', 'mmqwp' ) ,
    'type'              => 'number', 
    'custom_attributes' => array('step'  => 'any','min' => '0') 
  ) );

  woocommerce_wp_text_input( array(
    'id'          => '_mmqwp_multi_qty',
    'value'       => get_post_meta( get_the_ID(), '_mmqwp_multi_qty', true ),
    'label'       => 'Multiple of Quantity/Group',
    'desc_tip'    => true,
    'description' => __( 'Quantity multiple steps', 'mmqwp' ) ,
    'type'              => 'number', 
    'custom_attributes' => array('step'  => 'any','min' => '0')
  ) );

  echo "<p>Note: <i>This option is available for simple and variable products.</i></p>";
 
  echo '</div>';
 
}


// Show/hide setting fields (admin product pages)
add_action( 'admin_footer', 'mmqwp_product_type_selector_filter_callback' );
function mmqwp_product_type_selector_filter_callback() {
    global $pagenow, $post_type;

    if( in_array($pagenow, array('post-new.php', 'post.php') ) && $post_type === 'product' ) :
    ?>
    <script>
    jQuery(function($){
        if( $('input#_mmqwp_option_enable').is(':checked') && $('div.mmqwp_qty').hasClass('hidden') ) {
            $('div.mmqwp_qty').removeClass('hidden')
        }
        $('input#_mmqwp_option_enable').click(function(){
            if( $(this).is(':checked') && $('div.mmqwp_qty').hasClass('hidden')) {
                $('div.mmqwp_qty').removeClass('hidden');
            } else if( ! $(this).is(':checked') && ! $('div.mmqwp_qty').hasClass('hidden')) {
                $('div.mmqwp_qty').addClass('hidden');
            }
        });

        jQuery("#_mmqwp_min").focusout(function(){
          var minval = jQuery(this).val();
          if (minval.length > 0 && minval != 0) {
            jQuery("#_mmqwp_max").val('');
            jQuery("#_mmqwp_max").attr('min',(parseInt(minval)+1));
          }
          else{
            jQuery("#_mmqwp_max").val('');
            jQuery("#_mmqwp_max").attr('min','1');
          }
          
        });

        jQuery('#_mmqwp_min').on('keypress',function(){
          return false;
        });
        jQuery('#_mmqwp_max').on('keypress',function(){
          return false;
        });

    });
    </script>
    <?php
    endif;
}


/* Save setting values*/
add_action('woocommerce_process_product_meta', 'mmqwp_save_meta_box');
function mmqwp_save_meta_box($post_id)
{ 
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    if(isset($_POST['_mmqwp_option_enable'])){ 
      update_post_meta($post_id, '_mmqwp_option_enable', 'yes');
    }else{ 
      update_post_meta($post_id, '_mmqwp_option_enable', 'no');
    }
    
    update_post_meta($post_id, '_mmqwp_max',(int) wc_clean($_POST['_mmqwp_max']));
    update_post_meta($post_id, '_mmqwp_min', (int) wc_clean($_POST['_mmqwp_min']));
    update_post_meta($post_id, '_mmqwp_multi_qty', (int) wc_clean($_POST['_mmqwp_multi_qty']));
}



/*Function to manipulate minimum and maximum qty with steps*/
add_filter('woocommerce_quantity_input_args', 'mmqwp_quantity_input_args', 10, 2);
function mmqwp_quantity_input_args($args, $product)
{
  if ( $product->is_type('variation') ) {
      $prodid = $product->get_parent_id();
  } else {
      $prodid = $product->get_id();
  }

  $mmaxEnable = get_post_meta($prodid, '_mmqwp_option_enable', true);
  $minQty     = get_post_meta($prodid, '_mmqwp_min', true);
  $maxQty     = get_post_meta($prodid, '_mmqwp_max', true);
  $multi_qty  = get_post_meta($prodid, '_mmqwp_multi_qty', true);
  if ($minQty > 0 && $maxQty > 0 && $mmaxEnable == 'yes') {
    if (!is_cart()) {
      $args['input_value'] = $minQty;   // Starting value
    }
      $args['min_value']  = $minQty;    // Starting value
      $args['max_value']  = $maxQty;    // Ending value
      $args['step']       = $multi_qty; // Step
  }
   return $args;
   
}

// Variations
add_filter( 'woocommerce_available_variation', 'mmqwp_woocommerce_available_variation' ); 
function mmqwp_woocommerce_available_variation( $args ) {
    if (!is_cart()) {
      global $product;
      if ( $product->is_type('variation') ) {
          $prodid = $product->get_parent_id();
      } else {
          $prodid = $product->get_id();
      }

      $mmaxEnable = get_post_meta($prodid, '_mmqwp_option_enable', true);
      $minQty     = get_post_meta($prodid, '_mmqwp_min', true);
      $maxQty     = get_post_meta($prodid, '_mmqwp_max', true);
      $multi_qty  = get_post_meta($prodid, '_mmqwp_multi_qty', true);
      if ($minQty > 0 && $maxQty > 0 && $mmaxEnable == 'yes') {
          if (!is_cart()) {
            $args['input_value'] = $minQty;   // Starting value
          }
            $args['min_qty']    = $minQty;    // Maximum value (variations)
            $args['max_qty']    = $maxQty;    // Minimum value (variations)
            $args['step']       = $multi_qty; // Step
      }
    }
  return $args;
}


/*Function to check weather the maximum quantity is already existing in the cart*/
add_action('woocommerce_add_to_cart', 'mmqwp_custom_add_to_cart',10,2);
function mmqwp_custom_add_to_cart($args,$product)
{
  $mmaxEnable = get_post_meta($product, '_mmqwp_option_enable', true);
  $minQty     = get_post_meta($product, '_mmqwp_min', true);
  $maxQty     = get_post_meta($product, '_mmqwp_max', true);
  $cartQty    =  mmqwp_woo_in_cart($product);

  if($maxQty < $cartQty && $mmaxEnable == 'yes')
  {
    $maxQTYMsg = 'You can add the maximum Quantity '.$maxQty.' for the product for the current purchase';
    wc_add_notice($maxQTYMsg,'error');
    exit(wp_redirect( get_permalink($product) ));
  }

}


/* Get cart quantity*/
function mmqwp_woo_in_cart($product_id) {
    global $woocommerce;
    foreach($woocommerce->cart->get_cart() as $key => $val ) {
  
      $_product = $val['data'];

      $pro = wc_get_product( $_product->get_id() );
      if( $pro->is_type( 'simple' ) ) {
         if($product_id == $_product->get_id()) {
           return  $val['quantity'];
         }
      } else {
          if(wc_clean($_POST['variation_id']) == $_product->get_id()) {
           return  $val['quantity'];
         }
      }
   
    }
    return 0;
}


#filter hook to remove extra add to cart button in the shop and category pages
add_filter( 'woocommerce_loop_add_to_cart_link','mmqwp_add2cart' );
function mmqwp_add2cart( $link ) {
  global $product;
  $product_id = $product->get_id();
  $product_sku = $product->get_sku();
  $product_type = $product->get_type();
  $qtylink = ''; 
  $mmaxEnable = get_post_meta( $product_id, '_mmqwp_option_enable', true );
  $minQty     = get_post_meta( $product_id, '_mmqwp_min', true );

  if ( $product_type != 'simple' || $mmaxEnable != 'yes' ){
    return $link;
  }
  $qtylink = '&quantity='.$minQty;
  $ajax_cart_en = 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' );
  if ( $ajax_cart_en && $mmaxEnable !== 'yes'  && $product_type !='variable' ) {
    $ajax_class = 'ajax_add_to_cart';
  }
  else{
    $ajax_class = '';
  }
  $link = sprintf( '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" data-quantity="%s" class="button %s product_type_%s %s">%s</a>',
    esc_url( $product->add_to_cart_url().$qtylink ),
    esc_attr( $product_id ),
    esc_attr( $product->get_sku() ),
    esc_attr( isset( $minQty ) ? $minQty : 1 ),
    $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
    esc_attr( $product->get_type() ),
    esc_attr( $ajax_class ),
    esc_html( $product->add_to_cart_text() )
  );
  return $link;
}
