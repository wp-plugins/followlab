<?php

// display the admin options page
function followlab_page()
{
/*<script>
	jQuery(document).ready(function($){
		$.get('url', function(data){
			$('#messagediv').text(data);
		});
	});
</script>
<div id="messagediv" style="margin-top:10px"></div>*/
?>

<form action="options.php" method="post">
	<?php settings_fields('followlab'); ?>
	<?php do_settings_sections('followlab'); ?>
	<input name="Submit" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>">
</form>

<?php

}

$fl = new Followlab;

class Followlab
{
	public function __construct()
	{
		$this->options = get_option('followlab');
		add_action('admin_init', array($this, 'plugin_admin_init'));
		add_action('admin_menu', array($this, 'add_admin_page'));
	}
	function add_admin_page()
	{
		add_menu_page('FollowLab Plugin Settings', 'FollowLab', 'manage_options', 'followlab', 'followlab_page', 'dashicons-share');
	}
	function wp_gear_manager_admin_scripts()
	{
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('jquery');
	}
	function wp_gear_manager_admin_styles()
	{
		wp_enqueue_style('thickbox');
	}
	function plugin_admin_init()
	{
		register_setting('followlab', 'followlab');
		add_settings_section('plugin_main', 'FollowLab Settings', array($this, 'plugin_section_text'), 'followlab');

		$this->add_field('public_key', 'API Public Key');
		$this->add_field('private_key', 'API Private Key');
		$this->add_field('return_url', 'Return Page');
		$this->add_field('percentage', 'Price Percentage');
		$this->add_field('show_price', 'Show Price in Dropdown');
		$this->add_field('button_innertext', 'Checkout Text');
		$this->add_field('response_innertext', 'Default Response Text');
		$this->add_field('response_return_url', 'Show Response Return Link');
		$this->add_field('paypal_logo', 'PayPal Logo');
		$this->add_field('css', 'Custom Styling');
	
		add_action('admin_print_scripts', array($this, 'wp_gear_manager_admin_scripts'));
		add_action('admin_print_styles', array($this, 'wp_gear_manager_admin_styles'));
	}
	function add_field($id, $desc)
	{
		add_settings_field($id, $desc, array($this, $id), 'followlab', 'plugin_main', $id);
	}
	function plugin_section_text()
	{
		return "";
	}
	function show_price($id)
	{
		$str = '<input type="checkbox" name="followlab[' . esc_attr($id) . ']"';
		if (isset($this->options[$id])):
			$str .= ' checked';
		endif;
		$str .= '>';
		echo $str;
	}
	function public_key($id)
	{
		$str = '<input type="text" class="regular-text" name="followlab[' . esc_attr($id) . ']"';
		if (isset($this->options[$id])):
			$str .= ' value="' . esc_attr($this->options[$id]) . '"';
		endif;
		$str .= '>';
		echo $str;
	}
	function private_key($id)
	{
		$str = '<input type="text" class="regular-text" name="followlab[' . esc_attr($id) . ']"';
		if (isset($this->options[$id])):
			$str .= ' value="' . esc_attr($this->options[$id]) . '"';
		endif;
		$str .= '>';
		echo $str;
	}
	function return_url($id)
	{
		$str = '<select name="followlab[' . esc_attr($id) . ']">';
		$str .= '<option value="/">' . esc_attr('Select Page') . '</option>'; 
		$pages = get_pages();
		if (count($pages) > 0):
			foreach ($pages as $page):
				$permalink = get_permalink($page->ID);
				$str .= '<option value="' . $permalink . '"';
				if ($this->options[$id] == $permalink): 
					$str .= ' selected';
				endif;
				$str .= '>' . $page->post_title . '</option>';
			endforeach;
		endif;
		$str .= '</select>';
		echo $str;
	}
	function button_innertext($id)
	{
		$str = '<input type="text" class="regular-text" name="followlab[' . esc_attr($id) . ']"';
		if (isset($this->options[$id])): 
			$str .= ' value="' . esc_attr($this->options[$id]) . '"';
		else:
			$str .= ' value="' . esc_attr('Buy Now!') . '"';
		endif;
		$str .= '>';
		echo $str;
	}
	function response_innertext($id)
	{
		$str = '<input type="text" class="regular-text" name="followlab[' . esc_attr($id) . ']"';
		if (isset($this->options[$id])): 
			$str .= ' value="' . esc_attr($this->options[$id]) . '"';
		else:
			$str .= ' value="' . esc_attr('The customer should not be able to see this page without ordering.') . '"';
		endif;
		$str .= '>';
		$str .= '<p class="description">The default message for the shortcode [FL]response[/FL]. Leave blank for no default.</p>';
		echo $str;
	}
	function response_return_url($id)
	{
		$str = '<input type="checkbox" name="followlab[' . esc_attr($id) . ']"';
		if (isset($this->options[$id])):
			$str .= ' checked';
		endif;
		$str .= '>';
		$str .= '<p class="description">Provides a link to return to the last page on an error response.</p>';
		echo $str;
	}
	function percentage($id)
	{
		$str = '<select name="followlab[' . esc_attr($id) . ']">';
		$ladders = array(0, 20, 40, 60, 80, 100, 120, 140, 160, 180, 200, 220, 240, 260, 280, 300);
		foreach ($ladders as $ladder):
			$str .= '<option value="' . $ladder . '"';
			if ($this->options[$id] == $ladder):
				$str .= ' selected';
			endif;
			$str .=  '>' . $ladder . '</option>';
		endforeach;
		$str .= '</select>';
		$str .= '<p class="description">The percentage increase over the FollowLab base prices. This is your profit.</p>';
		echo $str;
	}
	function css($id)
	{
		$str = '<textarea rows="10" cols="50" name="followlab[' . esc_attr($id) . ']">';
		if (isset($this->options[$id])):
			$str .= esc_textarea($this->options[$id]);
		endif;
		$str .= '</textarea>';
		echo $str;
	}
	function paypal_logo($id)
	{
?>
		<script>
			jQuery(document).ready(function($){
				$('#upload_image_button').click(function(){
					formfield = $('#upload_image').attr('name');
					tb_show('', 'media-upload.php?type=image&TB_iframe=true');
					return false;
				});
				window.send_to_editor = function(html){
					imgurl = $('img', html).attr('src');
					$('#upload_image').val(imgurl);
					tb_remove();
				}
			});
		</script>
<?php 
		$str = '<input id="upload_image" type="text" class="regular-text" name="followlab[' . esc_attr($id) . ']"';
		if (isset($this->options[$id])):
			$str .= ' value="' . esc_attr($this->options[$id]) . '"';
		endif;
		$str .= '>';
		$str .= '<input id="upload_image_button" type="button" class="button" value="' . esc_attr('Upload Image') . '">';
		$str .= '<p class="description">Enter URL or upload an image for the PayPal banner.</p>';
		echo $str;
	}
}