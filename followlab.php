<?php
/**
 * Plugin Name: FollowLab
 * Plugin URI: https://followlab.com/
 * Description: Integration with the FollowLab Social Media API.
 * Version: 0.1
 * Author: FollowLab
 * Author URI: https://followlab.com/
 */

include_once plugin_dir_path(__FILE__) . "options.php";

function fl_add_new_intervals($schedules) 
{
	$schedules['twice_hourly'] = array(
		'interval' => 1800,
		'display' => __('Twice Hourly'),
	);
	return $schedules;
}
add_filter('cron_schedules', 'fl_add_new_intervals');
register_activation_hook(__FILE__, 'fl_plan_update_schedule');
add_action('fl_plan_update', 'fl_update_plans');
function fl_plan_update_schedule()
{
	if (!wp_next_scheduled('fl_plan_update')):
		wp_schedule_event(time(), 'twice_hourly', 'fl_plan_update');
	endif;
}
function fl_update_plans()
{
	$url = "https://followlab.com/api/plans?currency=";
	$option_name = "followlab_packages";
	$request = @file_get_contents($url);
	if ($request !== false):
		$new_value = json_decode($request, true);
		update_option($option_name, $new_value);
	endif;
}
register_deactivation_hook(__FILE__, 'fl_plan_update_remove');
function fl_plan_update_remove()
{
	wp_clear_scheduled_hook('fl_plan_update');
}

$class = new Followlab_short;
add_shortcode('FL', array($class, 'shortcode'));
add_action('wp_head', 'fl_hook_css');

function fl_hook_css()
{
	wp_enqueue_style('followlab-css', plugin_dir_url(__FILE__) . 'style.css');
	$o = get_option('followlab');
	$css = isset($o['css']) ? $o['css'] : false;
	if ($css !== false):
		wp_add_inline_style('followlab-css', $css);
	endif;
}

class Followlab_short
{
	function message_decode()
	{
		$o = get_option('followlab');
		if (isset($_GET['response'])):
			$data = $_GET['response'];
			$json = base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
			$data = json_decode($json, true);
		else:
			$data = array('status' => 'info', 'message' => (isset($o['response_innertext']) ? $o['response_innertext'] : 'The customer should not be able to see this page without ordering.'));
		endif;
		if (isset($_GET['response']) || (isset($o['response_innertext']) && !empty($o['response_innertext']))):
			$el = '<div class="alert alert-' . esc_attr($data['status']). '" role="alert">' . esc_html($data['message']);
			if (isset($o['response_return_url']) && isset($data['referer']) && $data['referer'] !== false):
				$el .= '<br/><a href="' . esc_url($data['referer']). '">Click here to try again.</a>';
			endif;
			$el .= '</div>';
		else:
			$el = '<br style="display:none!important">';
		endif;	
		return $el;
	}
	function shortcode($atts, $innertext)
	{ 
		$atts = shortcode_atts(array(
			'columns' => '6,3',
			'brand' => false,
			'type' => false,
			'newline' => 'yes',
			'price' => false,
		), $atts);
		
		$o = get_option('followlab');
		$packages = get_option('followlab_packages');
		if ($innertext == "response"):
			return $this->message_decode();
		else:
			$width = explode(",", $atts['columns']);
			if (count($width) === 2):
				$w['xs'] = (intval($width[0]) > 0 ? intval($width[0]) : 6);
				$w['md'] = (intval($width[1]) > 0 ? intval($width[1]) : 3);
			endif;
			$w['xs'] = 'col-xs-' . $w['xs'];
			$w['md'] = 'col-md-' . $w['md'];
			$width = $w['xs'] . " " . $w['md'];
			$plans = array();
			if ($atts['brand'] !== false && $atts['type'] !== false):
				foreach ($packages['plans'] as $plan):
					if ($plan['brand'] == $atts['brand'] && $plan['type'] == $atts['type']):
						$plans[] = $plan;
					endif;
				endforeach;
			elseif ($atts['brand'] !== false || $atts['type'] !== false):
				if ($atts['brand'] !== false):
					foreach ($packages['plans'] as $plan):
						if ($plan['brand'] == $atts['brand']):
							$plans[] = $plan;
						endif;
					endforeach;
				else:
					foreach ($packages['plans'] as $plan):
						if($plan['type'] == $atts['type']):
							$plans[] = $plan;
						endif;
					endforeach;
				endif;
			else:
				$plans = $packages['plans'];
			endif;
			$el = "";
			if ($atts['newline'] == "yes"):
				$el .= '<div class="row">';
			endif;
			if ($atts['price'] !== false):
				$percentage = $atts['price'];
			else:
				$percentage = $o['percentage'];
			endif;
			if (isset($o['private_key']) && !empty($o['private_key']) && isset($o['public_key']) && !empty($o['public_key']) && isset($o['return_url']) && !empty($o['return_url'])):
				if (count($plans) > 0):
					foreach($plans as $plan):
						$el .= '<form method="post" action="https://followlab.com/api/create">';
							$el .= '<div class="fl ' . $width . '">';
									$el .= '<div class="form-group">';
									$el .= '<img class="img-responsive" src="' . plugin_dir_url(__FILE__) . 'images/' . $plan['brand'] . '.png">';
									$el .= '<span class="lab">' . preg_replace('/\s/', '<br>', $plan['title'], 1);
									if (!empty($plan['help'])):
										$el .= '<a class="fl_tooltip">? <span><strong>' . $plan['title'] . '</strong><br/>' . $plan['help'] . '</span></a>';
									endif;
									$el .= '</span>';
									$el .= '<input type="text" class="form-control fl_tooltip" name="to" placeholder="' . $plan['placeholder'] . '">';
									$el .= '<select name="amount" class="form-control">';
										 foreach ($plan['amounts'] as $amount => $price):
											$price += $price * ($percentage / 100);
											$el .= '<option value="' . $amount . '">' . number_format($amount);
											if ($o['show_price'] == 'on'):
												$el .= ' ($' . number_format($price, 2) . ')';
											endif;
											$el .= '</option>';
										endforeach;
									$el .= '</select>';
									$el .= '<input type="hidden" name="return_url" value="' . $o['return_url'] . '">';
									if (isset($o['paypal_logo'])):
										$el .= '<input type="hidden" name="image_url" value="' . $o['paypal_logo'] . '">';
									endif;
									$el .= '<input type="hidden" name="plan" value="' . $plan['value'] . '">';
									$el .= '<input type="hidden" name="percentage" value="' . $percentage . '">';
									$el .= '<input type="hidden" name="key" value="' . $o['public_key'] . '">';
									$el .= '<input type="hidden" name="hash" value="' . hash_hmac('md5', $percentage, $o['private_key']) . '">';
									$el .= '<button type="submit" class="btn btn-primary btn-block">' . $o['button_innertext'] . '</button>';
								$el .= '</div>';
							$el .= '</div>';
						$el .= '</form>';
					endforeach;
				else:
					$el .= "<p>No plans currently available.</p>";
				endif;
			else:
				$el .= "<p>Please set your keys and return URL in the plugin settings.</p>";
			endif;
			if ($atts['newline'] == "yes"):
				$el .= '</div>';
			endif;
			return $el;
		endif;
	}
}