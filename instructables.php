<?php
/*
 * Plugin Name: Instructables
 * Plugin URI:  http://www.x2labs.com/wp-dev/instructables-plugin/
 * Description: Display Instructables Projects on your site linking to the source.
 * Version: 2.0.4
 * Tested up to: WP 4.8.1
 * Author: Britton Scritchfield aka MrRedBeard
 * Author URI: http://www.x2labs.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
/*  Copyright 2017 Britton Scritchfield aka MrRedBeard (email : britton.scritchfield@gmail.com) */

/*
ToDo: Fix style
ToDo: Rename Elements
*/

defined('ABSPATH') or die("No script kiddies please!");

define('instructables_url_path', plugin_dir_url( __FILE__ ));

if (!class_exists('instructables')) 
{
	class instructables
	{		
		public function __construct()
		{			
			// We only need to register the admin panel on the back-end
			if (add_action('init', array( $this, 'check_access')))
			{
				add_action( 'admin_menu', array( 'instructables', 'add_admin_menu' ) );
				add_action( 'admin_init', array( 'instructables', 'register_settings' ) );
				
				add_action('init', array( $this, 'instructables_custom_post_type' ));
				add_action('add_meta_boxes', array( $this, 'add_instructables_meta_box' ));
				add_action('save_post', array( $this, 'instructables_save_metadata' ));
				
				add_action( 'admin_init', array( $this, 'instructables_tinymce_button' ) ); //my_tinymce_button
				add_action( 'admin_head', array( $this, 'instructables_tinymce_button_dashicon') ); // my_tinymce_button_dashicon

				add_filter( 'manage_posts_columns', array( $this, 'revealid_add_id_column' ), 5 );
				add_action( 'manage_posts_custom_column', array( $this, 'revealid_id_column_content' ), 5, 2 );
			}			
			add_shortcode('instructables', array( $this, 'instructables_shortcode_query' ));
			
			//Add custom StyleSheet
			add_action('wp_enqueue_scripts', array( $this, 'instructables_stylesheet' ));
			
			// Backwards Compatibility
			//Add User's Projects ShortCode
			add_shortcode('instructablesUP', array( $this, 'instructables_shortcode_bkwd' ), 'user' );
			//Add User's favorite projects ShortCode
			add_shortcode('instructablesFP', array( $this, 'instructables_shortcode_bkwd' ), 'userfav' );
			//Add Projects by keyword ShortCode
			add_shortcode('instructablesKW', array( $this, 'instructables_shortcode_bkwd' ), 'keywords' );
		}
		
		//Verify Access Level
		function check_access()
		{
			if ( current_user_can('edit_pages') || current_user_can('edit_posts') || current_user_can('editor') || current_user_can('author') ) 
			{
				return true;
			}
		}
		
		//Add post IDs to Column
		function revealid_add_id_column( $columns ) 
		{
			$columns['revealid_id'] = 'ID';
			return $columns;
		}
		//Add post IDs to Column
		function revealid_id_column_content( $column, $id ) 
		{
			if( 'revealid_id' == $column ) 
			{
				echo $id;
			}
		}
		
		/* Editor Menu Item Start */
		//
		// Add TinyMCE button and plugin filters
		function instructables_tinymce_button() //my_tinymce_button
		{
			if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) 
			{
				add_filter( 'mce_buttons', array( $this, 'my_register_tinymce_button') );
				add_filter( 'mce_external_plugins', array( $this, 'instructables_tinymce_button_script') ); // my_tinymce_button_script
			}
		}

		// Add TinyMCE buttons onto the button array
		function my_register_tinymce_button( $buttons ) {
			array_push( $buttons, 'instruct_button' );
			return $buttons;
		}

		// Add TinyMCE button script to the plugins array
		function instructables_tinymce_button_script( $plugin_array ) // my_tinymce_button_script
		{
			$plugin_array['instruct_button_script'] = instructables_url_path . 'button.js.php';  // Change this to reflect the path/filename to your js file
			return $plugin_array;
		}
		

		// Style the button with a dashicon icon instead of an image
		function instructables_tinymce_button_dashicon() // my_tinymce_button_dashicon
		{
			?>
			<style type="text/css">
			.mce-i-instruct_button:before 
			{
				content:url(' <?php echo instructables_url_path . '/images/admin-icon-16x16.png'; ?> ');
				display: inline-block;
				vertical-align: top;
			}
			</style>
			<?php
		}
		
		//Add custom StyleSheet
		function instructables_stylesheet()
		{
			wp_enqueue_style( 'prefix-style', instructables_url_path . 'Style.css' );
		}

		/* Editor Menu Item End */
		
		
		public function instructables_custom_post_type()
		{
			
			register_post_type('instructables_sc',
			   [
					'labels' => [
					   'name' => __('Instructables'),
					   'singular_name' => __('Instructable'),
					   'add_new_item' => __( 'Add Instructables Feed', 'textdomain' ),
					],
					'show_ui' => true,
					'show_in_menu' => false,
					'show_in_nav_menus' => false,
					'public' => false,
					'supports' => array('title', 'instructables_meta_box'),
					'rewrite' => array( 'slug' => 'instructables' ),
					'hierarchical' => false,
					'can_export' => true,
					'has_archive' => false,
			   ]
			);
		}
		
		public function add_instructables_meta_box() 
		{
		   add_meta_box(
			   'instructables_meta_box',						// $id
			   'Insrtuctables',									// $title
			   array( $this, 'show_instructables_meta_box' ),	// $callback
			   'instructables_sc',								// $page
			   'normal',										// $context
			   'high'											// $priority
		   );
		}
		
		public function show_instructables_meta_box($post)
		{			
			$value = get_post_meta($post->ID, 'instrct_name', true);
			?>
				<label for="instrct_name">Name of this feed. Give it something meaningful because it will be referenced later (No special charecters) : </label><br />
				<input name="instrct_name" type="text" id="instrct_name" value="<?php echo $value; ?>" onchange="setTitle(this);" onkeydown="setTitle(this);">
				<hr />
			<?php
			$value = get_post_meta($post->ID, 'instrct_title', true);
			?>
				<label for="instrct_title">Use name as title for displayed feed: </label><br />
				<input name="instrct_title" type="checkbox" id="instrct_title" <?php checked( $value, 'on' ); ?>>
				<hr />
			<?php
			
			$value = get_post_meta($post->ID, 'instrct_type', true);
			?>
				<label for="instrct_type">Select type of feed you want: </label><br />
				<select name="instrct_type" id="instrct_type" class="postbox" onchange="TypeOfFeedChange(this);">
					<option value="">Select something...</option>
					<option value="user" <?php selected($value, 'user'); ?>>A User's Instructables</option>
					<!--<option value="followers" <?php selected($value, 'followers'); ?>>A User's Followers</option>
					<option value="following" <?php selected($value, 'following'); ?>>Who a User Follows</option>-->
					<option value="keywords" <?php selected($value, 'keywords'); ?>>Instructables by Keyword(s)</option>
					<option value="userfav" <?php selected($value, 'userfav'); ?>>User's Favorites</option>
					<option value="groups" <?php selected($value, 'groups'); ?>>A Group's Instructables</option>
					<option value="featured" <?php selected($value, 'featured'); ?>>All Featured Instructables</option>
					<option value="recent" <?php selected($value, 'recent'); ?>>All Recent Instructables</option>
				</select>
				<hr />
			<?php
			
			$value = get_post_meta($post->ID, 'instrct_thumb', true);
			?>
				<label for="instrct_thumb">Use Thumbnail: </label><br />
				<input name="instrct_thumb" type="checkbox" id="instrct_thumb" <?php checked( $value, 'on' ); ?>>
				<hr />
			<?php
			
			$value = get_post_meta($post->ID, 'instrct_layout', true);
			?>
				<label for="instrct_layout">Tile or Post Layout: </label><br />
				<label>Post Layout: </label><input type="radio" name="instrct_layout" id="instrct_layout" value="post" <?php if (isset($value) && $value=="post") echo "checked";?>>
				<label>Tile Layout: </label><input type="radio" name="instrct_layout" id="instrct_layout" value="tile" <?php if (isset($value) && $value=="tile") echo "checked";?>>
				<hr />
			<?php
			
			$value = get_post_meta($post->ID, 'instrct_num', true);
			?>
				<label for="instrct_num">Number of items to show: </label><br />
				<input name="instrct_num" type="text" id="instrct_num" onchange="removeSpecialChars(this);" value="<?php echo $value; ?>">
				<hr />
			<?php
			
			$value = get_post_meta($post->ID, 'instrct_identifier', true);
			?>
				<label style="display: none;" id="instrct_identifier_label" for="instrct_identifier"> </label><br />
				<input style="display: none;" name="instrct_identifier" type="text" id="instrct_identifier" onchange="formatidentifier(this);" value="<?php echo $value; ?>">
			<?php	

			?>
				<script>
					function removeSpecialChars(obj)
					{
						var specialChars = "!@#$^&%*()+=-_[]\/{}|:;~<>?,.'`";
						for (var i = 0; i < specialChars.length; i++)
						{
							obj.value = obj.value .replace(new RegExp("\\" + specialChars[i], 'gi'), '');
						}
					}
					function formatidentifier(obj)
					{
						var specialChars = "!@#$^&%*()+=[]\/{}|:;~<>?,.'`";
						for (var i = 0; i < specialChars.length; i++)
						{
							obj.value = obj.value .replace(new RegExp("\\" + specialChars[i], 'gi'), '');
						}						
					}
					function setTitle(obj)
					{
						document.getElementById("title").value = obj.value;
					}
					function TypeOfFeedChange(obj)
					{
						if(obj.value == "user")
						{
							document.getElementById("instrct_identifier_label").style.display = 'inline';
							document.getElementById("instrct_identifier").style.display = 'inline';
							document.getElementById("instrct_identifier_label").innerHTML = "Enter Username to display that user's authored instructables: "
						}
						else if(obj.value == "followers")
						{
							document.getElementById("instrct_identifier_label").style.display = 'inline';
							document.getElementById("instrct_identifier").style.display = 'inline';
							document.getElementById("instrct_identifier_label").innerHTML = "Enter Username to display the user's followers: "
						}
						else if(obj.value == "following")
						{
							document.getElementById("instrct_identifier_label").style.display = 'inline';
							document.getElementById("instrct_identifier").style.display = 'inline';
							document.getElementById("instrct_identifier_label").innerHTML = "Enter Username to display that who that user follows: "
						}
						else if(obj.value == "keywords")
						{
							document.getElementById("instrct_identifier_label").style.display = 'inline';
							document.getElementById("instrct_identifier").style.display = 'inline';
							document.getElementById("instrct_identifier_label").innerHTML = "Enter a keyword or keywords seperated by spaces to display instructables with those keywords (tent rope): "
						}
						else if(obj.value == "userfav")
						{
							document.getElementById("instrct_identifier_label").style.display = 'inline';
							document.getElementById("instrct_identifier").style.display = 'inline';
							document.getElementById("instrct_identifier_label").innerHTML = "Enter Username to display that user's favorite instructables: "
						}
						else if(obj.value == "groups")
						{
							document.getElementById("instrct_identifier_label").style.display = 'inline';
							document.getElementById("instrct_identifier").style.display = 'inline';
							document.getElementById("instrct_identifier_label").innerHTML = "Enter Group name to display that group's instructables: "
						}
						else if(obj.value == "featured")
						{
							document.getElementById("instrct_identifier_label").style.display = 'none';
							document.getElementById("instrct_identifier").style.display = 'none';
							document.getElementById("instrct_identifier").value = "";
						}
						else if(obj.value == "recent")
						{
							document.getElementById("instrct_identifier_label").style.display = 'none';
							document.getElementById("instrct_identifier").style.display = 'none';
							document.getElementById("instrct_identifier").value = "";
						}
						else
						{
							//do nothing
						}
						
					}
					//Set identifier field
					TypeOfFeedChange(document.getElementById("instrct_type"));
					//Hide the title field for post
					document.getElementById("title-prompt-text").style.display = 'none';
					document.getElementById("title").style.display = 'none';
				</script>
			<?php
		}
		
		public function instructables_save_metadata($post_id)
		{
			if (array_key_exists('instrct_name', $_POST)) 
			{
				update_post_meta(
					$post_id,
					'instrct_name',
					$_POST['instrct_name']
				);
			}
			if (array_key_exists('instrct_title', $_POST)) 
			{
				update_post_meta(
					$post_id,
					'instrct_title',
					$_POST['instrct_title']
				);
			}
			else
			{
				update_post_meta(
					$post_id,
					'instrct_title',
					'off'
				);
			}
			if (array_key_exists('instrct_type', $_POST)) 
			{
				update_post_meta(
					$post_id,
					'instrct_type',
					$_POST['instrct_type']
				);
			}
			if (array_key_exists('instrct_thumb', $_POST)) 
			{
				update_post_meta(
					$post_id,
					'instrct_thumb',
					$_POST['instrct_thumb']
				);
			}
			else
			{
				update_post_meta(
					$post_id,
					'instrct_thumb',
					'off'
				);
			}
			if (array_key_exists('instrct_layout', $_POST)) 
			{
				update_post_meta(
					$post_id,
					'instrct_layout',
					$_POST['instrct_layout']
				);
			}
			if (array_key_exists('instrct_num', $_POST)) 
			{
				update_post_meta(
					$post_id,
					'instrct_num',
					$_POST['instrct_num']
				);
			}
			if (array_key_exists('instrct_identifier', $_POST)) 
			{
				update_post_meta(
					$post_id,
					'instrct_identifier',
					$_POST['instrct_identifier']
				);
			}
		}
		
		public static function instructables_get_feeds()
		{
			$args = array(
				'post_type'  => 'instructables_sc',
			);
			$query = new WP_Query( $args );
			
			return $query;
		}
		
		// Backwards Compatibility
		public function instructables_shortcode_bkwd($atts, $null, $type)
		{
			if($type == "instructablesUP")
			{
				$type = "user";
			}
			elseif($type == "instructablesKW")
			{
				$type = "keywords";
			}
			elseif($type == "instructablesFP")
			{
				$type = "userfav";
			}
			
			$num = $atts["num"];
			$identifier = "";
			if(array_key_exists("username",$atts))
			{
				$identifier = $atts["username"];
			}
			$keyword = "";
			if(array_key_exists("keyword",$atts))
			{
				$keyword = $atts["keyword"];
			}
			
			if($atts["thumb"] == "on" || $atts["thumb"] == true)
			{
				$thumb = "on";
			}
			else
			{
				$thumb = "off";
			}
			if($atts["tileview"] == "on")
			{
				$layout = "tile";
			}
			else
			{
				$layout = "post";
			}
			
			/*
			$num = get_post_meta($atts['id'], 'instrct_num', true);
			$layout = get_post_meta($atts['id'], 'instrct_layout', true);
			$thumb = get_post_meta($atts['id'], 'instrct_thumb', true);
			$type = get_post_meta($atts['id'], 'instrct_type', true);
			*/
			
			$url = '';
			if ( $type == 'user' )
			{
				$url = "http://www.instructables.com/member/" . $identifier . "/rss.xml?show=instructable";
			}
			elseif ( $type == 'followers' )
			{
				$url = "http://www.instructables.com/member/" . $identifier . "/rss.xml?show=FOLLOWERS";
			}
			elseif ( $type == 'following' )
			{
				$url = "http://www.instructables.com/member/" . $identifier . "/rss.xml?show=FOLLOWING";
			}
			elseif ( $type == 'keywords' )
			{
				$url = 'http://www.instructables.com/tag/type-id/stepbystep-true';
				$keywords = explode(' ', $keyword);
				foreach ($keywords as $keywordX)
				{
					$url = $url . '/keyword-' . $keywordX;
				}
				$url = $url . '/rss.xml?sort=RECENT';
			}
			elseif ( $type == 'userfav' )
			{
				$url = "http://www.instructables.com/member/" . $identifier . "/rss.xml?show=good";
			}
			elseif ( $type == 'groups' )
			{
				$url = "http://www.instructables.com/group/" . $identifier . "/rss.xml?sort=RECENT";
			}
			elseif ( $type == 'featured' )
			{
				$url = "http://www.instructables.com/tag/type-id/featured-true/rss.xml?sort=RECENT";
			}
			elseif ( $type == 'recent' )
			{
				$url = "http://www.instructables.com/rss.xml";
			}
			
			return instructables::instpProcessXML($url, $num, $layout, $thumb, "");
		}
		
		//Shortcode gets custom post type values for Instructables Feeds
		public function instructables_shortcode_query($atts)
		{
			//echo print_r(array_keys($atts));
			/*
			echo get_post_meta($atts['id'], 'instrct_name', true) . "<BR />";
			echo get_post_meta($atts['id'], 'instrct_type', true) . "<BR />";
			echo get_post_meta($atts['id'], 'instrct_thumb', true) . "<BR />";
			echo get_post_meta($atts['id'], 'instrct_layout', true) . "<BR />";
			echo get_post_meta($atts['id'], 'instrct_num', true) . "<BR />";
			echo get_post_meta($atts['id'], 'instrct_identifier', true);
			echo get_post_meta($atts['id'], 'instrct_title', true);
			*/
			
			/*
				By Group
					https://www.instructables.com/group/alternativeenergy/rss.xml?sort=RECENT
				By Keywords
					https://www.instructables.com/tag/type-id/stepbystep-true/keyword-tent/keyword-sail/rss.xml?sort=RECENT
				//Users Instructables
					//$url = "http://www.instructables.com/member/" . get_post_meta($atts['id'], 'instrct_identifier', true) . "/rss.xml?show=instructable";
				//Users Favorite Instructables
					//$url = "http://www.instructables.com/member/" . $a['username'] . "/rss.xml?show=good";
			*/
			
			$num = get_post_meta($atts['id'], 'instrct_num', true);
			$layout = get_post_meta($atts['id'], 'instrct_layout', true);
			$thumb = get_post_meta($atts['id'], 'instrct_thumb', true);
			$type = get_post_meta($atts['id'], 'instrct_type', true);
			
			$title = '';
			if (get_post_meta($atts['id'], 'instrct_title', true) == 'on')
			{
				$title = get_post_meta($atts['id'], 'instrct_name', true);
			}
			
			$url = '';
			if ( $type == 'user' )
			{
				$url = "http://www.instructables.com/member/" . get_post_meta($atts['id'], 'instrct_identifier', true) . "/rss.xml?show=instructable";
			}
			elseif ( $type == 'followers' )
			{
				//Removed from API
				$url = "http://www.instructables.com/member/" . get_post_meta($atts['id'], 'instrct_identifier', true) . "/rss.xml?show=FOLLOWERS";
			}
			elseif ( $type == 'following' )
			{
				//Removed from API
				$url = "http://www.instructables.com/member/" . get_post_meta($atts['id'], 'instrct_identifier', true) . "/rss.xml?show=FOLLOWING";
			}
			elseif ( $type == 'keywords' )
			{
				$url = 'http://www.instructables.com/tag/type-id/stepbystep-true';
				$keywords = explode(' ', get_post_meta($atts['id'], 'instrct_identifier', true));
				foreach ($keywords as $keyword)
				{
					$url = $url . '/keyword-' . $keyword;
				}
				$url = $url . '/rss.xml?sort=RECENT';
			}
			elseif ( $type == 'userfav' )
			{
				$url = "http://www.instructables.com/member/" . get_post_meta($atts['id'], 'instrct_identifier', true) . "/rss.xml?show=good&sort=RECENT";
			}
			elseif ( $type == 'groups' )
			{
				$url = "http://www.instructables.com/group/" . get_post_meta($atts['id'], 'instrct_identifier', true) . "/rss.xml?sort=RECENT";
			}
			elseif ( $type == 'featured' )
			{
				$url = "http://www.instructables.com/tag/type-id/featured-true/rss.xml?sort=RECENT";
			}
			elseif ( $type == 'recent' )
			{
				$url = "http://www.instructables.com/rss.xml";
			}
			
			/*
			echo "Type: " . $type . "<BR />";
			echo "URL: " . $url . "<BR />";
			echo "Num: " . $num . "<BR />";
			echo "Layout: " . $layout . "<BR />";
			echo "Thumb: " .$thumb . "<BR />";
			echo "Title: " . $title . "<BR />";
			*/
			
			return instructables::instpProcessXML($url, $num, $layout, $thumb, $title);
			
			//wp_reset_query();
		}
		
		
		//Process XML that was handed off by function calling
		public function instpProcessXML($url, $num, $layout, $thumb, $title)
		{
			//ToDo: Fix Feeds - following, followers - Appears to have been removed from API
			
			$feed = simplexml_load_file($url);
			$feed_array = array();
			$itemCTR = 0;
			$postx = "";
			if(strlen($title) > 0)
			{
				$postx = "<h2>" . $title . "</h2>";
				//$postx = $postx . "<h2>" . $url . "</h2>";
			}
			if($layout == 'post')
			{
				foreach($feed->channel->item as $item)
				{
					$postx = $postx . "<article class='post instructables-post'>";
					$postx = $postx . "<header>";
					$postx = $postx . "<h2 class='entry-title'><a href='" . $item->link . "' target='_blank'>" . $item->title . "</a></h2>";
					$postx = $postx . "</header>";
					if(strpos($item->imageThumb,"com") && $thumb == 'on')
					{
						$postx = $postx . '<div class="post-thumbnail">';
						$postx = $postx . "<a target='_Blank' href='" . $item->link . "' target='_blank'>";
						$postx = $postx . "<img class='wp-post-image' src='" . $item->imageThumb . "' />";
						$postx = $postx . "</a>";
						$postx = $postx . '</div>';
					}
					elseif($thumb == 'on')
					{
						$postx = $postx . '<div class="post-thumbnail">';
						$postx = $postx . "<a target='_Blank' href='" . $item->link . "' target='_blank'>";
						$postx = $postx . "<img class='wp-post-image' src='http://www.instructables.com" . $item->imageThumb . "' />";
						$postx = $postx . "</a>";
						$postx = $postx . '</div>';
					}
					
					$postx = $postx . "<div class='entry-content'>";
					
					$descx = preg_replace('/<!--(.|\s)*?-->/' , '', preg_replace('/<a[^>]+\>/i', '', preg_replace('/<img[^>]+\>/i', '', $item->description)));
					$descx = str_replace("&raquo;", "", str_replace("Continue Reading", "", str_replace("&nbsp;...<br/>By:", "By:", $descx)));
					$postx = $postx . "<p>" . $descx . "</p>";
					
					$postx = $postx . '</div>';//Close Post Content
					$postx = $postx . '<p><a href="' . $item->link . '">READ MORE...</a></p>';
					$postx = $postx . '</article>';//Close Post
					
					$itemCTR++;
					if(is_numeric($num) == true && $itemCTR >= $num)
					{
						break;
					}
				}
			}
			elseif($layout == 'tile')
			{
				$postx = $postx . "<div class='instructables_tiles'>";
				foreach($feed->channel->item as $item)
				{
					$postx = $postx . "<div class='instructables_tile'>";
					
					$postx = $postx . "<header>";
					$postx = $postx . "<h3><a target='_Blank' href='" . $item->link . "'><strong>" . $item->title . "</strong></a></h3>";
					$postx = $postx . "</header>";
					
					$postx = $postx . "<div class='post-thumbnail'>";
					if(strpos($item->imageThumb,"com"))
					{
						$postx = $postx . "<a target='_Blank' href='" . $item->link . "'><img src='" . $item->imageThumb . "' /></a>";
					}
					else
					{
						$postx = $postx . "<a target='_Blank' href='" . $item->link . "'><img src='http://www.instructables.com" . $item->imageThumb . "' /></a>";
					}
					$postx = $postx . "</div>";
					
					
					$postx = $postx . "</div>";
					
					$itemCTR++;
					if(is_numeric($num) == true && $itemCTR >= $num)
					{
						break;
					}
				}
				$postx = $postx . '</div>';//Close Post
			}
			return $postx;
		}		
		
		// Register a setting and its sanitization callback. One setting with all options in array.
		public static function register_settings() 
		{
			// Register a new setting for Instructables
			register_setting( 'instructables_options', 'instructables_options', array( 'instructables_options', 'sanitize' ) );
		}
		
		// Returns all instructables options
		public static function get_instructables_options() 
		{
			return get_option( 'instructables_options' );
		}
		
		// Returns single instructables option
		public static function get_instructables_option($id)
		{
			$options = self::get_instructables_options();
			if ( isset( $options[$id] ) ) 
			{
				return $options[$id];
			}
		}
		
		// Add sub menu page
		public static function add_admin_menu()
		{
			add_menu_page(
				esc_html__( 'Instructables', 'text-domain' ),
				esc_html__( 'Instructables', 'text-domain' ),
				'edit_posts',
				esc_html__( 'instructables_config', 'text-domain' ),
				array( 'instructables', 'create_admin_sub_page' ),
				plugin_dir_url(__FILE__) . 'images/admin-icon-16x16.png'
			);
			
			//Custom Post Type
			add_submenu_page( 'instructables_config', 'Instructables Feeds', 'Instructables Feeds', 'edit_posts', 'edit.php?post_type=instructables_sc', NULL );
			//Add New
			add_submenu_page( 'instructables_config', 'Add New Instructable Feed', 'Add New Instructable Feed', 'edit_posts', 'post-new.php?post_type=instructables_sc', NULL );
			
			//Settings Page
			add_submenu_page( 'instructables_config', 'Settings', 'Settings', 'edit_posts', 'instructables_options', array( "instructables", "create_admin_page"));
			/*
			add_menu_page( 
				$page_title, 
				$menu_title, 
				$capability, 
				$menu_slug, 
				$function, 
				$icon_url, 
				$position );
			*/
			/*
			add_submenu_page( 
				$parent_slug, 
				$page_title, 
				$menu_title, 
				$capability, 
				$menu_slug, 
				$function); 
			*/
		}
		
		public static function create_admin_sub_page()
		{
			// Custom Post Type for creating shortcodes
			?>
				<div class="wrap">
					<h1>Instructables</h1>
					
					<h2>Updates</h2>
					<p>Created an interface to define feeds</p>
					<p>
						Added new feed types
						<ul style="list-style: disc; margin-left: 30px;">
							<li>A groups Instructables</li>
							<li>A user's favorites</li>
							<li>Keyword with ability to have multiple keywords</li>
							<li>All recent featured Instructables</li>
							<li>All recent Instructables</li>
						</ul>
					</p>
					<p>
						Feeds for "A user's followers" & "Who a user follows" appear to have been removed from the API.
					</p>
					
					<h2>Legacy ShortCodes still work</h2>
					Display a user's projects:
					[instructablesUP username="MrRedBeard" num="2" thumb="true" tileview="false"] <br />

					Display a list of projects by keyword:
					[instructablesKW keyword="tent" num="3" thumb="true" tileview="false"] <br />

					Display a list of a User's favorite projects:
					[instructablesFP username="MrRedBeard" num="2" thumb="true" tileview="false"] <br />
					
					<h2>Support this plugin</h2>
					<p><a href="https://wordpress.org/plugins/instructables/" target="_BLANK">Give a rating on WordPress</a></p>
					<p><a href="https://www.instructables.com/id/Instructables-Wordpress-Plugin/" target="_BLANK">Rate and comment on Instructables</a></p>
					<p>Have an idea? <a href="mailto:britton.scritchfield@gmail.com" target="_BLANK">Email me</a></p>
					
					<h2>Got Feedback?</h2>
					<p>
						Email me: <a href="mailto:britton.scritchfield@gmail.com" target="_BLANK">britton.scritchfield@gmail.com</a><br />
						&nbsp;&nbsp;or<br />
						Submit issue on <a href="https://wordpress.org/support/plugin/instructables" target="_BLANK">WordPress.org</a><br />
						&nbsp;&nbsp;or<br />
						Submit issue on <a href="https://github.com/MrRedBeard/instructables/issues" target="_BLANK">GitHub</a><br />
						&nbsp;&nbsp;or<br />
						Comment on the <a href="https://www.instructables.com/id/Instructables-Wordpress-Plugin/" target="_BLANK">Insrtuctables Page for this plugin</a>
					</p>
					
					<p>
						If your hosting solution does not support simplexml_load_file() then please contact me. I may need to develop a curl method if this is a widespread issue.
					</p>
					<img class="alignright" height="250" src="<?php echo plugin_dir_url(__FILE__) . 'images/robot.png'; ?>" />
				</div>
			<?php
		}
		
		// Admin Page
		public static function create_admin_page()
		{
			// Any configurable options
			?>
				<div class="wrap">
					<h1>Instructables</h1>
					<p>Display instructables.com projects and feeds as posts on your website. Don't know what Instructables is? Visit <a target="_blank" href="http://www.instructables.com/">instructables.com</a> to see the awesome.</p>
					<hr />
					<h2>Coming Later</h2>
					<!--
					<form id="instructablesForm" method="post" action="options.php">
						<?php //settings_fields( 'instructables_options' ); ?>
						
						<div>
							<?php //$value = self::get_instructables_option( 'shortcodes' ); ?>
							<input type="text" name="instructables_options[shortcodes]" value="<?php //echo esc_attr( $value ); ?>">
						</div>

						
					</form>-->
					
					
					
					<br />
					<br />
					<!--<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" onclick="document.getElementById('instructablesForm').submit();">-->
					
					
				</div>
				<img class="alignright" height="250" src="<?php echo plugin_dir_url(__FILE__) . 'images/robot.png' ?>" />
			<?php
		}
		
		
		
		// Sanitization callback
		public static function sanitize( $options ) 
		{
			// If we have options lets sanitize them
			if ( $options ) 
			{
				// Checkbox
				if ( ! empty( $options['checkbox_example'] ) )
				{
					$options['checkbox_example'] = 'on';
				}
				else 
				{
					unset( $options['checkbox_example'] ); // Remove from options if not checked
				}

				// Input
				if ( ! empty( $options['input_example'] ) )
				{
					$options['input_example'] = sanitize_text_field( $options['input_example'] );
				}
				else
				{
					unset( $options['input_example'] ); // Remove from options if empty
				}
				// Select
				if ( ! empty( $options['select_example'] ) )
				{
					$options['select_example'] = sanitize_text_field( $options['select_example'] );
				}
			}
			// Return sanitized options
			return $options;
		}
		
	}
}
new instructables();

?>