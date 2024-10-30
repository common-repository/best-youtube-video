<?php
/**
 * Plugin Name: Best youtube Video
 * Description: This plugin is used to display video in iframe using youtube watch url. This is boon if want to add more youtube video directly on your post or pages.
 * Version: 1.0.0
 * Author: Infoseek Team
 * Author URI: http://infoseeksoftwaresystems.com/
 * License: GPL2
 */
 
/*
* PREVENT LOAD THE FILE DIRECTLY
*/
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'BYViframevideo' ) ) 
{
	class BYViframevideo{
		
		function __construct(){
			add_action('admin_enqueue_scripts', array( $this, 'byv_backEndScript'));
			add_action( 'admin_menu', array( $this, 'byt_menu_pages' ));	
			add_shortcode("BYV_iframe_video", array( $this,"BYV_iframe_video_handler"));		
		}		
		
		/*
		* INCLUDE JS & CSS
		*/
		public function byv_backEndScript() {
			wp_enqueue_script( 'byt-video-script-js', plugins_url('/js/script.js' , __FILE__ ), array('jquery'));
			wp_enqueue_style( 'byt-video-style-css', plugins_url( '/css/style.css' , __FILE__ ));
		}	
		
		/*
		* ADD ADMIN MENU FOR IFRAME SETTING
		*/
		public function byt_menu_pages()
		{
		    add_menu_page("Youtube Video Setting", "Youtube Video Setting","manage_options","youtube-iframe-setting", array($this,'byt_iframe_setting'));		
		    add_action("add_meta_boxes", array( $this, "byt_meta_box_for_video"));
			add_action("save_post", array( $this, "byt_save_video_urls"));	
		}
		
		public function byt_iframe_setting()
		{				
			if(isset($_POST["youtubesave"]))
			{
				/*Verify nonce before saving*/
				if (!isset($_POST["byv-nonce"]) || !wp_verify_nonce($_POST["byv-nonce"], basename(__FILE__)))
					return;
				
				$byv_width_box = sanitize_text_field($_POST["byv-width-box"]);
				if ( get_option( "iframe-width" ) !== false ){
					update_option( "iframe-width", $byv_width_box );
				}else {
					add_option( "iframe-width", $byv_width_box, null, "no" );
				}	
					
				$byv_height_box = sanitize_text_field($_POST["byv-hight-box"]);
				if ( get_option("iframe-hight") !== false ){
					update_option("iframe-hight", $byv_height_box );
				}else{	
					add_option(	"iframe-hight", $byv_height_box, null, 	"no" );
				}							
			}
			echo '<div class="wrapper">
					<h4>Set height/width of your iframe video, which is display on your page.</h4>
					<form action="" method="post" class="wrapper-one">
						<div class="wrapper-two">
						'.wp_nonce_field(basename(__FILE__), "byv-nonce").'
						<ul>
							<li><input type="text" name="byv-width-box" placeholder="Set Iframe width" value="'.get_option( "iframe-width" ).'"> (in % or pixel ie. 100%)</li> 
							<li><input type="text" name="byv-hight-box" placeholder="Set Iframe height" value="'.get_option( "iframe-hight" ).'"> (in % or pixel ie 300px)</li>
							<li><input type="submit" name="youtubesave" value="Save" class="button button-primary"></li>
							</ul>				
						</div>
					</form>
				</div>';	
			echo '<div class="instruction-container">
					<p><h3>Shortcode</h3></p>
					<p>Add this shortcode on your single post or page.</p>
					<p>[BYV_iframe_video]</p>
					<h1>Youtube video URL instruction.</h1>
					<span>Note: </span>
					<ul>
					<li>1. This plugin only support a youtube watch url.</li>
					<li>2. Video url must be from youtube site.</li>
					<li>3. Like this ex: https://www.youtube.com/watch?v=6ay7Qqb0BVQ.</li>
					</ul>
				</div>';
		}
		
		/*
		* ADDING METABOX ON PAGE & POST TYPE
		*/
		public function byt_meta_box_for_video() 
		{			
		    $post_types = get_post_types();
		    foreach ( $post_types as $post_type ){
		        if ( $post_type == 'page' || $post_type =='post' )
		        continue;
		        add_meta_box('BYV_meta_box', 'Youtube Watch Video Urls', array($this,'show_byt_meta_box'), $post_types, 'normal', 'high');
		    }
		}

		/*
		* ADDING METABOX TO ADD YOUTUBE VIDEO
		*/
		public function show_byt_meta_box()
		{	
			wp_nonce_field(basename(__FILE__), "byv-meta-nonce");
			echo '<div class="demo">
					<div>
						<label>Youtube url</label>
						<input type="text" class="ytval" name="up_video[]" id="tst1" onchange="ytvalfunc(this.id)" size="45" placeholder="Youtube video watch url">
						<button type="button" name="btnclick" id="btnclick" onclick="appendRow();">ADD MORE</button>
						<div id="div"></div> 
					</div>
					<div>';
			$q = 1;
			$slam_featured_videos = get_post_meta( get_the_ID(), 'yuv_video',true);	
			$slam_featured_videos = unserialize($slam_featured_videos);
			if (is_array($slam_featured_videos) || is_object($slam_featured_videos)){			
				foreach($slam_featured_videos as $slam_featured_video)
				{
					$youtube_id = $this->getYouTubeIdFromURL($slam_featured_video);
					echo '
					<div id="ab'.$q.'" class="video-box-bkend">
						<iframe width="100" height="100" src="https://www.youtube.com/embed/'.$youtube_id.'" frameborder="0" allowfullscreen style="float:left;"></iframe>	
						<div class="video-box-div">
							<input type="text" name="up_video[]" size="55" value="'.$slam_featured_video.'">
							<a href="javascript:void();" onclick="deleteVideo('.$q.');">Remove</a>	
						</div>	 
					</div>';	
					$q++;
				}
			}
			echo'</div></div>';	
		}
		public function sanitize( $input ) {
			$new_input = array();
			foreach ( $input as $key => $val ) {
				$new_input[ $key ] = ( isset( $input[ $key ] ) ) ? sanitize_text_field( $val ) :'';
			}
			return $new_input;
		}
		/*
		* ADDING METABOX TO ADD YOUTUBE VIDEO
		*/
		public function byt_save_video_urls()
		{
			global $post;
			
			// if we're doing an auto save
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				return;
			 
			// Verify nonce before saving
			if (!isset($_POST["byv-meta-nonce"]) || !wp_verify_nonce($_POST["byv-meta-nonce"], basename(__FILE__)))
				return;
			 
			// if our current user can't edit this post
			if( !current_user_can( 'edit_post' ) ) 
				return;
			
			$up_videoArr = $this->sanitize($_POST['up_video']);//sanitize_text_field
			if( !empty($up_videoArr) ){
				foreach($up_videoArr as $url){
					if($url != ""){
						$newarr[] = $url;//video url
					}
				}
				$byv_video = serialize( $newarr );
				update_post_meta($post->ID, "yuv_video", $byv_video);	
			}
		}	
		
		/*
		* DISPLAYING SHORTCODE 
		*/
		public function getYouTubeIdFromURL($slam_featured_video)
		{
			$url_string = parse_url($slam_featured_video, PHP_URL_QUERY);
			parse_str($url_string, $args);					
			return isset($args['v']) ? $args['v'] : false;
									
		}
		public function BYV_iframe_video_handler() 
		{
		    $slam_featured_videos = get_post_meta(get_the_ID(), 'yuv_video', true);
			$slam_featured_videos = unserialize($slam_featured_videos);
			if(!empty($slam_featured_videos)){
				foreach ($slam_featured_videos as $slam_featured_video){
					$youtube_id = $this->getYouTubeIdFromURL($slam_featured_video);
					$BYV_iframe_width = (get_option('iframe-width') !='')?get_option('iframe-width'):'100%';
					$BYV_iframe_height = (get_option('iframe-hight') !='')?get_option('iframe-height'):'300px';
					echo '<div class="video-box">
						<iframe style="width:'.$BYV_iframe_width.';height:'.$BYV_iframe_height.';" src="https://www.youtube.com/embed/'.$youtube_id.'" frameborder="0" allowfullscreen></iframe>			 
					</div>';
				}
			}			
		}	
	}
	new BYViframevideo();
}