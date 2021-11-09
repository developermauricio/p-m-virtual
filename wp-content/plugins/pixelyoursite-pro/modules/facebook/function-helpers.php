<?php

namespace PixelYourSite\Facebook\Helpers;

use PixelYourSite;
use function PixelYourSite\wooGetOrderIdFromRequest;
use function PixelYourSite\wooIsRequestContainOrderId;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * @return array
 */
function getAdvancedMatchingParams() {

	$params = array();
	$user = wp_get_current_user();

	if ( $user->ID ) {

		// get user regular data
		$params['fn'] = $user->get( 'user_firstname' );
		$params['ln'] = $user->get( 'user_lastname' );
		$params['em'] = $user->get( 'user_email' );

	}

	/**
	 * Add common WooCommerce Advanced Matching params
	 */

	if ( PixelYourSite\isWooCommerceActive() && PixelYourSite\PYS()->getOption( 'woo_enabled' ) ) {

		// if first name is not set in regular wp user meta
		if ( empty( $params['fn'] ) ) {
			$params['fn'] = $user->get( 'billing_first_name' );
		}

		// if last name is not set in regular wp user meta
		if ( empty( $params['ln'] ) ) {
			$params['ln'] = $user->get( 'billing_last_name' );
		}

		$params['ph'] = $user->get( 'billing_phone' );
		$params['ct'] = $user->get( 'billing_city' );
		$params['st'] = $user->get( 'billing_state' );

		$params['country'] = $user->get( 'billing_country' );

		/**
		 * Add purchase WooCommerce Advanced Matching params
		 */

		if ( is_order_received_page() && wooIsRequestContainOrderId() ) {

			$order_id = wooGetOrderIdFromRequest();
			$order    = wc_get_order( $order_id );

			if ( $order ) {

				if ( PixelYourSite\isWooCommerceVersionGte( '3.0.0' ) ) {

					$params = array(
						'em'      => $order->get_billing_email(),
						'ph'      => $order->get_billing_phone(),
						'fn'      => $order->get_billing_first_name(),
						'ln'      => $order->get_billing_last_name(),
						'ct'      => $order->get_billing_city(),
						'st'      => $order->get_billing_state(),
						'country' => $order->get_billing_country(),
					);

				} else {

					$params = array(
						'em'      => $order->billing_email,
						'ph'      => $order->billing_phone,
						'fn'      => $order->billing_first_name,
						'ln'      => $order->billing_last_name,
						'ct'      => $order->billing_city,
						'st'      => $order->billing_state,
						'country' => $order->billing_country,
					);

				}

			}

		}

	}

	/**
	 * Add common EDD Advanced Matching params
	 */

	if ( PixelYourSite\isEddActive() && PixelYourSite\PYS()->getOption( 'edd_enabled' ) ) {

		/**
		 * Add purchase EDD Advanced Matching params
		 */

		// skip payment confirmation page
		if ( edd_is_success_page() && ! isset( $_GET['payment-confirmation'] ) ) {
			global $edd_receipt_args;

			$session = edd_get_purchase_session();
			if ( isset( $_GET['payment_key'] ) ) {
				$payment_key = urldecode( $_GET['payment_key'] );
			} else if ( $session ) {
				$payment_key = $session['purchase_key'];
			} elseif ( $edd_receipt_args['payment_key'] ) {
				$payment_key = $edd_receipt_args['payment_key'];
			}

			if ( isset( $payment_key ) ) {

				$payment_id = edd_get_purchase_id_by_key( $payment_key );

				if ( $payment = edd_get_payment( $payment_id ) ) {

					// if first name is not set in regular wp user meta
					if ( empty( $params['fn'] ) ) {
						$params['fn'] = $payment->user_info['first_name'];
					}

					// if last name is not set in regular wp user meta
					if ( empty( $params['ln'] ) ) {
						$params['ln'] = $payment->user_info['last_name'];
					}

					$params['ct'] = $payment->address['city'];
					$params['st'] = $payment->address['state'];

					$params['country'] = $payment->address['country'];

				}

			}

		}

	}

	$sanitized = array();

	foreach ( $params as $key => $value ) {

		if ( ! empty( $value ) ) {
			$sanitized[ $key ] = sanitizeAdvancedMatchingParam( $value, $key );
		}

	}

	return $sanitized;

}

function sanitizeAdvancedMatchingParam( $value, $key ) {

    // prevents fatal error when mb_string extension not enabled
    if ( function_exists( 'mb_strtolower' ) ) {
        $value = mb_strtolower( $value );
    } else {
        $value = strtolower( $value );
    }

	if ( $key == 'ph' ) {
		$value = preg_replace( '/\D/', '', $value );
	} elseif ( $key == 'em' ) {
		$value = preg_replace( '/[^a-z0-9._+-@]+/i', '', $value );
	} else {
	    // only letters with unicode support
        $value = preg_replace( '/[^\w\p{L}]/u', '', $value );
	}

	return $value;

}

 function get_fb_plugin_retailer_id( $woo_product ) {
    if(!$woo_product) return "";
    $woo_id = $woo_product->get_id();

    // Call $woo_product->get_id() instead of ->id to account for Variable
    // products, which have their own variant_ids.
    return $woo_product->get_sku() ? $woo_product->get_sku() . '_' .
        $woo_id : 'wc_post_id_'. $woo_id;
}

/**
 * @param string $product_id
 *
 * @return array
 */
function getFacebookWooProductContentId( $product_id ) {

	if ( PixelYourSite\Facebook()->getOption( 'woo_content_id' ) == 'product_sku' ) {
		$content_id = get_post_meta( $product_id, '_sku', true );
	} else {
		$content_id = $product_id;
	}

	$prefix = PixelYourSite\Facebook()->getOption( 'woo_content_id_prefix' );
	$suffix = PixelYourSite\Facebook()->getOption( 'woo_content_id_suffix' );

	$value = $prefix . $content_id . $suffix;
	$value = array( $value );

	// Facebook for WooCommerce plugin integration
	if ( ! isDefaultWooContentIdLogic() ) {

		$product = wc_get_product($product_id);

		if ( ! $product ) {
			return $value;
		}

		$ids = array(
            get_fb_plugin_retailer_id($product)
		);

		$value = array_values( array_filter( $ids ) );


	}

	return $value;

}

function getFacebookWooProductDataId( $item ) {

    if($item['type'] == 'variation' && isDefaultWooContentIdLogic() ) {
        if(PixelYourSite\Facebook()->getOption( 'woo_variable_as_simple' ) ) {
            $product_id = $item['parent_id'];
        } else {
            $product_id = $item['product_id'];
        }
    } else {
        $product_id = $item['product_id'];
    }

    return $product_id;

}

function getFacebookWooCartItemId( $item ) {

	if ( ! PixelYourSite\Facebook()->getOption( 'woo_variable_as_simple' ) && isset( $item['variation_id'] ) && $item['variation_id'] !== 0 ) {
		$product_id = $item['variation_id'];
	} else {
		$product_id = $item['product_id'];
	}

	// Facebook for WooCommerce plugin integration
	if ( ! isDefaultWooContentIdLogic() ) {

		if ( isset( $item['variation_id'] ) && $item['variation_id'] !== 0 ) {
			$product_id = $item['variation_id'];
		} else {
			$product_id = $item['product_id'];
		}

	}

	return $product_id;

}

function getWooCustomAudiencesOptimizationParams( $post_id ) {

	$post = get_post( $post_id );

	$params = array(
		'content_name'  => '',
		'category_name' => '',
	);

	if ( ! $post ) {
		return $params;
	}

	if ( $post->post_type == 'product_variation' ) {
		$post_id = $post->post_parent; // get terms from parent
	}

	$params['content_name'] = $post->post_title;
	$params['category_name'] = implode( ', ', PixelYourSite\getObjectTerms( 'product_cat', $post_id ) );

	return $params;

}

function getWooSingleAddToCartParams( $_product_id, $qty = 1, $is_external = false,$args = null ) {

	$params = array();
    $product = wc_get_product($_product_id);
    if(!$product) return array();
    $product_ids = array();

    $isGrouped = $product->get_type() == "grouped";
    if($isGrouped) {
        $product_ids = $product->get_children();
    } else {
        $product_ids[] = $_product_id;
    }

    $params['content_type'] = 'product';
    $params['content_ids']  = array();
    $params['contents'] = array();

    // add tags
    $tagList = PixelYourSite\getObjectTerms( 'product_tag', $_product_id );
    if(count($tagList)) $params['tags'] = implode( ', ', $tagList );

    // add content and category name
    $params = array_merge( $params, getWooCustomAudiencesOptimizationParams( $_product_id ) );

	// set option names
	$value_enabled_option = $is_external ? 'woo_affiliate_value_enabled' : 'woo_add_to_cart_value_enabled';
	$value_option_option  = $is_external ? 'woo_affiliate_value_option' : 'woo_add_to_cart_value_option';
	$value_global_option  = $is_external ? 'woo_affiliate_value_global' : 'woo_add_to_cart_value_global';
	$value_percent_option = $is_external ? '' : 'woo_add_to_cart_value_percent';

	// currency, value
	if ( PixelYourSite\PYS()->getOption( $value_enabled_option ) ) {

		$value_option   = PixelYourSite\PYS()->getOption( $value_option_option );
		$global_value   = PixelYourSite\PYS()->getOption( $value_global_option, 0 );
		$percents_value = PixelYourSite\PYS()->getOption( $value_percent_option, 100 );

        $valueArgs = [
            'valueOption' => $value_option,
            'global' => $global_value,
            'percent' => $percents_value,
            'product_id' => $_product_id,
            'qty' => $qty
        ];

        if($args && !empty($args['discount_value']) && !empty($args['discount_type'])) {
            $valueArgs['discount_value'] = $args['discount_value'];
            $valueArgs['discount_type'] = $args['discount_type'];
        }

        $params['value']    = PixelYourSite\getWooProductValue($valueArgs);

        $params['currency'] = get_woocommerce_currency();
	}


    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if(!$product) continue;
        if($product->get_type() == "variable" && $isGrouped) {
            continue;
        }
        $content_id = getFacebookWooProductContentId( $product_id );
        $params['content_ids'] = array_merge($params['content_ids'],$content_id);
        // contents
        if ( isDefaultWooContentIdLogic() ) {

            // Facebook for WooCommerce plugin does not support new Dynamic Ads parameters
            $params['contents'][] = array(
                    'id'         => (string) reset( $content_id ),
                    'quantity'   => $qty,
                    //'item_price' => PixelYourSite\getWooProductPriceToDisplay( $product_id ),// remove because price need send only with currency
            );
        }
    }


	if ( $is_external ) {
		$params['action'] = 'affiliate button click';
	}

	return $params;

}

function getWooCartParams( $context = 'cart' ,$filter = null) {

	$params['content_type'] = 'product';

	$content_ids        = array();
	$content_names      = array();
	$content_categories = array();
	$tags               = array();
	$contents           = array();

	foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

		$product_id = getFacebookWooCartItemId( $cart_item );

		if(!$product_id) continue;

		$content_id = getFacebookWooProductContentId( $product_id );

		if($filter && isset($filter["category"])) {
            $_product = wc_get_product($cart_item['product_id']);
            if(!$_product) continue;
            if(!in_array($filter["category"],$_product->get_category_ids())) continue;
        }

		$content_ids = array_merge( $content_ids, $content_id );

		// content_name, category_name, tags
		$custom_audiences = getWooCustomAudiencesOptimizationParams( $product_id );

		$content_names[]      = $custom_audiences['content_name'];
		$content_categories[] = $custom_audiences['category_name'];

		$cart_item_tags = PixelYourSite\getObjectTerms( 'product_tag', $product_id );
		$tags = array_merge( $tags, $cart_item_tags );


		// raw product id
		$_product_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];

		// contents
		$contents[] = array(
			'id'         => (string) reset( $content_id ),
			'quantity'   => $cart_item['quantity'],
			//'item_price' => PixelYourSite\getWooProductPriceToDisplay( $_product_id ),
		);

	}

	$params['content_ids']   = ( $content_ids );
	$params['content_name']  = implode( ', ', $content_names );
	$params['category_name'] = implode( ', ', $content_categories );

	// contents
	if ( isDefaultWooContentIdLogic() ) {

		// Facebook for WooCommerce plugin does not support new Dynamic Ads parameters
		$params['contents'] = ( $contents );

	}

	$tags           = array_unique( $tags );
	$tags           = array_slice( $tags, 0, 100 );
	if(count($tags)) {
        $params['tags'] = implode( ', ', $tags );
    }

	if ( $context == 'InitiateCheckout' ) {

		$params['num_items'] = WC()->cart->get_cart_contents_count();

		$value_enabled_option = 'woo_initiate_checkout_value_enabled';
		$value_option_option  = 'woo_initiate_checkout_value_option';
		$value_global_option  = 'woo_initiate_checkout_value_global';
		$value_percent_option = 'woo_initiate_checkout_value_percent';

		$params['subtotal'] = PixelYourSite\getWooCartSubtotal();

	} elseif ( $context == 'PayPal' ) {

		$params['num_items'] = WC()->cart->get_cart_contents_count();

		$value_enabled_option = 'woo_paypal_value_enabled';
		$value_option_option  = 'woo_paypal_value_option';
		$value_global_option  = 'woo_paypal_value_global';
		$value_percent_option = '';

		$params['subtotal'] = PixelYourSite\getWooCartSubtotal();

		$params['action'] = 'PayPal';

	} else { // AddToCart

		$value_enabled_option = 'woo_add_to_cart_value_enabled';
		$value_option_option  = 'woo_add_to_cart_value_option';
		$value_global_option  = 'woo_add_to_cart_value_global';
		$value_percent_option = 'woo_add_to_cart_value_percent';

	}

	if ( PixelYourSite\PYS()->getOption( $value_enabled_option ) ) {

		$value_option   = PixelYourSite\PYS()->getOption( $value_option_option );
		$global_value   = PixelYourSite\PYS()->getOption( $value_global_option, 0 );
		$percents_value = PixelYourSite\PYS()->getOption( $value_percent_option, 100 );
        $params['currency'] = get_woocommerce_currency();
		$params['value']    = PixelYourSite\getWooEventValueCart( $value_option, $global_value, $percents_value );
	}


	return $params;

}


/**
 * @param \WC_Order $order
 * @return array
 */
function getCompleteRegistrationOrderParams($order) {
    $params = array();

    $value_option   = PixelYourSite\Facebook()->getOption( 'woo_complete_registration_custom_value' );
    $global_value   = PixelYourSite\Facebook()->getOption( 'woo_complete_registration_global_value', 0 );
    $percents_value = PixelYourSite\Facebook()->getOption( 'woo_complete_registration_percent_value', 100 );

    $params['value'] = PixelYourSite\getWooEventValueOrder( $value_option, $order, $global_value, $percents_value );
    $params['currency'] = get_woocommerce_currency();
    return $params;
}

function getWooPurchaseParams( $context ,$filter = null,$args = null) {

    if($args && !empty($args['order_id'])) {
        $order_id = $args['order_id'];
    } else {
        $order_id = wooGetOrderIdFromRequest();
    }

	$order = new \WC_Order( $order_id );

	$content_ids        = array();
	$content_names      = array();
	$content_categories = array();
	$tags               = array();
	$num_items          = 0;
	$contents           = array();

    $order_total = (float) $order->get_total( 'edit' );
    $order_tax = (float) $order->get_total_tax( 'edit' );

	foreach ( $order->get_items( 'line_item' ) as $line_item ) {

		$product_id  = getFacebookWooCartItemId( $line_item );

        if($filter && isset($filter["category"])) {
            $_product = wc_get_product($line_item['product_id']);
            if($_product && !in_array($filter["category"],$_product->get_category_ids())) continue;
        }

        if(!empty($args['offer_product_id']) ) {
            if($args['offer_product_id'] == $product_id) {
                $order_total = $line_item['total'];
                $order_tax = $line_item['total_tax'];
            } else {
                continue;
            }
        }




        $content_id  = getFacebookWooProductContentId( $product_id );
		$content_ids = array_merge( $content_ids, $content_id );

		$num_items += $line_item['qty'];

		// content_name, category_name, tags
		$custom_audiences = getWooCustomAudiencesOptimizationParams( $product_id );

		$content_names[]      = $custom_audiences['content_name'];
		$content_categories[] = $custom_audiences['category_name'];

		$cart_item_tags = PixelYourSite\getObjectTerms( 'product_tag', $product_id );
		$tags = array_merge( $tags, $cart_item_tags );

		// raw product id
		$_product_id = empty( $line_item['variation_id'] ) ? $line_item['product_id']
			: $line_item['variation_id'];

		// contents
		$contents[] = array(
			'id'         => (string) reset( $content_id ),
			'quantity'   => $line_item['qty'],
			'item_price' => PixelYourSite\getWooProductPriceToDisplay( $_product_id ),
		);

	}

	if(count($contents) == 0) return false;

	$params['content_type']  = 'product';
	$params['content_ids']   = ( $content_ids );
	$params['content_name']  = implode( ', ', $content_names );
	$params['category_name'] = implode( ', ', $content_categories );

	// contents
	if ( isDefaultWooContentIdLogic() ) {

		// Facebook for WooCommerce plugin does not support new Dynamic Ads parameters
		$params['contents'] = ( $contents );

	}

	$tags           = array_unique( $tags );
	$tags           = array_slice( $tags, 0, 100 );
	if(count($tags))
	    $params['tags'] = implode( ', ', $tags );

	$params['num_items'] = $num_items;

	// add "value" only on Purchase event
	if ( $context == 'woo_purchase' ) {

		$value_option   = PixelYourSite\PYS()->getOption( 'woo_purchase_value_option' );
		$global_value   = PixelYourSite\PYS()->getOption( 'woo_purchase_value_global', 0 );
		$percents_value = PixelYourSite\PYS()->getOption( 'woo_purchase_value_percent', 100 );

		$params['value'] = PixelYourSite\getWooEventValueOrder( $value_option, $order, $global_value, $percents_value );
		$params['currency'] = get_woocommerce_currency();
        $params['order_id'] = $order_id;
	}

	// shipping method
	if ( $shipping_methods = $order->get_items( 'shipping' ) ) {

		$labels = array();
		foreach ( $shipping_methods as $shipping ) {
			$labels[] = $shipping['name'] ? $shipping['name'] : null;
		}

		$params['shipping'] = implode( ', ', $labels );

	}

	// coupons
	if ( $coupons = $order->get_items( 'coupon' ) ) {

		$labels = array();
		foreach ( $coupons as $coupon ) {
			$labels[] = $coupon['name'] ? $coupon['name'] : null;
		}

		$params['coupon_used'] = 'yes';
		$params['coupon_name'] = implode( ', ', $labels );

	} else {

		$params['coupon_used'] = 'no';

	}

    if(!empty($args['offer_product_id'])) {

    } else {

    }

    $params['total'] = $order_total;
	$params['tax'] = $order_tax;

	if ( PixelYourSite\isWooCommerceVersionGte( '2.7' ) ) {
		$params['shipping_cost'] = (float) $order->get_shipping_total( 'edit' ) + (float) $order->get_shipping_tax( 'edit' );
	} else {
		$params['shipping_cost'] = (float) $order->get_total_shipping() + (float) $order->get_shipping_tax();
	}

    if( PYS()->getOption("enable_woo_transactions_count_param")
        || PYS()->getOption("enable_woo_predicted_ltv_param")
        || PYS()->getOption("enable_woo_average_order_param")) {
        $customer_params = PixelYourSite\PYS()->getEventsManager()->getWooCustomerTotals($order_id);
        $params['predicted_ltv'] = $customer_params['ltv'];
        $params['average_order'] = $customer_params['avg_order_value'];
        $params['transactions_count'] = $customer_params['orders_count'];
    }




	return $params;

}

function isFacebookForWooCommerceActive() {
	return class_exists( 'WC_Facebookcommerce' );
}

function isDefaultWooContentIdLogic() {
	return ! isFacebookForWooCommerceActive() || PixelYourSite\Facebook()->getOption( 'woo_content_id_logic' ) != 'facebook_for_woocommerce';
}

/**
 * EASY DIGITAL DOWNLOADS
 */

function getFacebookEddDownloadContentId( $download_id ) {

	if ( PixelYourSite\Facebook()->getOption( 'edd_content_id' ) == 'download_sku' ) {
		$content_id = get_post_meta( $download_id, 'edd_sku', true );
	} else {
		$content_id = $download_id;
	}

	$prefix = PixelYourSite\Facebook()->getOption( 'edd_content_id_prefix' );
	$suffix = PixelYourSite\Facebook()->getOption( 'edd_content_id_suffix' );

	return $prefix . $content_id . $suffix;

}

function getEddCustomAudiencesOptimizationParams( $post_id ) {

	$post = get_post( $post_id );

	$params = array(
		'content_name'  => '',
		'category_name' => '',
	);

	if ( ! $post ) {
		return $params;
	}

	$params['content_name'] = $post->post_title;
	$params['category_name'] = implode( ', ', PixelYourSite\getObjectTerms( 'download_category', $post_id ) );

	return $params;

}

function getFDPViewContentEventParams() {
    $tagsArray = wp_get_post_tags();
    $catArray = get_the_category();



    $func = function($value) {
        return $value->cat_name;
    };
    $catArray = array_map($func,$catArray);
    $categories = implode(", ",$catArray);

    $params = array(
        'content_name'     => get_the_title(),
        'content_ids'      => get_the_ID(),
        'categories'       => $categories
    );


    if(is_array($tagsArray)) {
        $params['tags' ] = implode(", ",$tagsArray);
    }




    return $params;
}

function getFDPViewCategoryEventParams() {
    global $wp_query;
    $func = function($value) {
        return $value->ID;
    };
    $ids = array_map($func,$wp_query->posts);


    $params = array(
        'content_name'     => single_term_title('', 0),
        'content_ids'      => ($ids)
    );

    return $params;
}

function getFDPAddToCartEventParams() {
    $tagsArray = wp_get_post_tags();
    $catArray = get_the_category();



    $func = function($value) {
        return $value->cat_name;
    };
    $catArray = array_map($func,$catArray);
    $categories = implode(", ",$catArray);

    $params = array(
        'content_name'     => get_the_title(),
        'content_ids'      => get_the_ID(),
        'categories'       => $categories,
        'value'            => 0
    );

    if(is_array($tagsArray)) {
        $params['tags'] = implode(", ",$tagsArray);
    }

    return $params;
}

function getFDPPurchaseEventParams() {
    $tagsArray = wp_get_post_tags();
    $catArray = get_the_category();



    $func = function($value) {
        return $value->cat_name;
    };
    $catArray = array_map($func,$catArray);
    $categories = implode(", ",$catArray);

    $params = array(
        'content_name'     => get_the_title(),
        'content_ids'      => get_the_ID(),
        'categories'       => $categories,
        'value'            => 0
    );
    if(is_array($tagsArray)) {
        $params['tags'] = implode(", ",$tagsArray);
    }




    return $params;
}
