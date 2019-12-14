<?php
/*
Plugin Name: Set Post Images
Description: Creating of the metafields in order to upload post images
Version: 0.1.0
Author: AlexanderWityuk
Author URI: 
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Setimagesize {
	function __construct() {
		add_action('add_meta_boxes', array($this, 'add_meta_box'));
		add_action( 'admin_menu', array($this, 'add_settings_img_size') );
		add_action ( 'admin_enqueue_scripts', array($this,'_style_admin') );
		add_action('save_post', array($this,'add_img_date_save_data'));
	}
	public function _style_admin() {
		wp_enqueue_script( 'custom_admin_script', plugin_dir_url(__FILE__) .'/js/scripts.js', '', '1.0', true );
	   	//wp_enqueue_media();
	   	if(function_exists( 'wp_enqueue_media' )){
		    wp_enqueue_media();
		}else{
		    wp_enqueue_style('thickbox');
		    wp_enqueue_script('media-upload');
		    wp_enqueue_script('thickbox');
		}
	}

	/*
	* ---------------------------------------------------
	*  Add metaboxes for images to post editor
	* ---------------------------------------------------
	*/
	public function add_meta_box () {
		add_meta_box( 'adding-images', 'Add image', array($this,'add_meta_img_fields'), array('post', 'page', 'custom_post_type'), 'normal', 'high' );
	}
	public function add_meta_img_fields ($post) {
		wp_nonce_field( basename(__FILE__), 'guida_manth_year_mam_nonce' );

		$num_images ='';
		$img_size ='';
		$taxonomies = get_object_taxonomies( array( 'post_type' => $post->post_type ) );
		foreach ($taxonomies as $taxonomy) {
			if (strpos($taxonomy, 'category') !== false) {
				$categories = wp_get_post_terms( $post->ID, $taxonomy);
				$num_images= get_option($post->post_type.$categories[0]->name.'-amount-img');
				$img_size= get_option($post->post_type.$categories[0]->name.'-img-size');
			}
		}
		 	$img_size = explode(",", $img_size['input']);
		?>
		<input id="post-img-num" type="hidden" name="post-img-num" value="<?php echo $num_images['input']; ?>" 
		width="<?php echo $img_size[0]; ?>" height="<?php echo $img_size[1]; ?>">
		<?php
		for ($i=0; $i < (int)$num_images['input'] ; $i++) { 
			$attach_id = get_post_meta( $post->ID, 'post-id-img-src'.$i, true );
			$addition_attr = get_post_meta( $post->ID, 'post-img-additional-attr'.$i, true );
			$attr_arr = explode(';', $addition_attr);
			$arg_arr = array();
			foreach ($attr_arr as $value) {
				$val = explode(':', $value);
				$arg_arr[$val[0]] = $val[1];
			}

			?>
			<div style="margin-bottom: 15px;">
				<p>
					<label for="<?php echo $post->ID.'%'.$i; ?>-additionl-img-attr">Additional img attributes</label>
		            <input type="text" id="<?php echo $post->ID.'%'.$i; ?>-additionl-img-attr"*
		            name="<?php echo $post->ID.'%'.$i; ?>-additionl-img-attr" 
		            value="<?php echo $addition_attr; ?>"  pattern="(([0-9a-z]*)(:)([0-9a-z]*))*(;)*(([0-9a-z]*)(:)([0-9a-z]*))*" />                            
		        </p>
				<input type="hidden" name="<?php echo $post->ID.'%'.$i; ?>-image-url" value="<?php echo $attach_id; ?>" >
				<div value=""  class="regular-text process_custom_images" id="process_custom_images" 
				style="overflow: hidden;
    					width: 300px;
   						 height: 150px;
    					border: 1px solid #ddd;"
			>
			<?php
			if (isset($attach_id)) {

				echo wp_get_attachment_image( $attach_id, Array($img_size[0], $img_size[1]), false, $arg_arr );
			}
			?>
			</div>
    			<button class="set_custom_images button">Set Image ID</button>
			</div>
			<?php
		}
	}
	function add_img_date_save_data ($post_id) {
		global $post;
		
		$num = $_POST['post-img-num'];
		$is_autosave = wp_is_post_autosave( $post_id );
	    $is_revision = wp_is_post_revision( $post_id );
	    $is_valid_nonce = ( isset( $_POST[ 'guida_manth_year_mam_nonce' ] ) && wp_verify_nonce( $_POST[ 'guida_manth_year_mam_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

	    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
	        return;
	    }
	    for ($i=0; $i < (int)$num ; $i++) { 
	    	$query_post = $post_id.'%'.$i.'-image-url';
		    if ( ! empty( $_POST[$query_post] ) ) {
		        update_post_meta( $post_id, 'post-id-img-src'.$i, $_POST[$query_post] );
		    }
	    	$add_attr = $post_id.'%'.$i.'-additionl-img-attr';
		    if ( ! empty( $_POST[$add_attr] ) ) {
		        update_post_meta( $post_id, 'post-img-additional-attr'.$i, trim($_POST[$add_attr]) );
		    }
	    }
	}
	
	/*
	* ---------------------------------------------------
	*  Add Page settings for images
	* ---------------------------------------------------
	*/
	public function add_settings_img_size() {
	    add_submenu_page( 
	        'options-general.php',
	        'Set sizes and amount of post images', 
	        'Set limit of media files', 
	        'manage_options', 
	        'post-img-settings-id', //ID
         	array($this,'post_img_settings_callback' )
	    );

        //call register settings function
    	add_action( 'admin_init', array($this, 'post_typeimages_param_register_mysettings') );
	}

	public function post_img_settings_callback() {

	    ?>
	        <form action="options.php" method="POST">
	            <?php
	                settings_fields( 'post-typ-settings-group' );  
	                do_settings_sections( 'post_images_settings_page' );
	                submit_button();
	            ?>
	        </form>
        <?php
		
	}

	function post_typeimages_param_register_mysettings () {


		$post_types =  get_post_types_by_support(array('title', 'editor', 'thumbnail'));

		foreach ($post_types as $post_type) {

			$taxonomies = get_object_taxonomies( array( 'post_type' => $post_type ) );
			
			foreach ($taxonomies as $taxonomy) {
				if (strpos($taxonomy, 'category') !== false) {
					$categories = get_terms($taxonomy);
					foreach ($categories as $category) {
					    register_setting( 'post-typ-settings-group', $post_type.$category->name.'-amount-img', 'sanitize_callback' );
				    	register_setting( 'post-typ-settings-group', $post_type.$category->name.'-img-size', 'sanitize_callback' );

				        $args     = array (
				            'posttype'      => $post_type,
				            'category'      =>  $category->name
				        );

					    add_settings_field(
					        $post_type.$category->name.'-amount-img',
					        'Post type: "'.$post_type.'", Category: "'.$category->name.'" num',
					        array( $this,'images_num_callback_function'), 
					        'post_images_settings_page', // page
					        'post_img_section_id', // page
					        $args
					    );

					    add_settings_field(
					        $post_type.$category->name.'-img-size',
					        'Post type: "'.$post_type.'", Category: "'.$category->name.'" size (width,height)',
					        array($this, 'images_size_callback_function'), 
					        'post_images_settings_page', // page
					        'post_img_section_id', // page
					        $args
					    );
					}
				}
			}

		}
	    
	    add_settings_section( 
	        'post_img_section_id', 
	        'Set post type images sizes and amount', 
	        '', 
	        'post_images_settings_page' 
	    ); 

	}

	function images_num_callback_function (array $args) {
	    $val= get_option($args['posttype'].$args['category'].'-amount-img');
	    if (is_array($val) && isset($val)) {
	    	$val = $val['input'];
	    }
	    ?>
	        <p>
	            <input type="number" id="<?php echo $args['posttype'].$args['category'];?>-amount-img" name="<?php echo $args['posttype'].$args['category'];?>-amount-img[input]" value="<?php echo esc_attr( $val ) ?>" />                            
	        </p>
	    <?php
	}
	function images_size_callback_function (array $args) {
	    $val= get_option($args['posttype'].$args['category'].'-img-size');
	    if (is_array($val) && isset($val)) {
	    	$val = $val['input'];
	    }
	    ?>
	        <p>
	            <input class="attch-size" type="text" id="<?php echo $args['posttype'].$args['category'];?>-img-size" 
	            name="<?php echo $args['posttype'].$args['category'];?>-img-size[input]" 
	            value="<?php echo esc_attr( $val ) ?>"  pattern="([0-9]+)(,)([0-9]+)" />                            
	        </p>
	    <?php
	}
}
new Setimagesize();
?>