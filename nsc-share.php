<?php
/**
 * Plugin Name: NSC Share
 * Description: This is for artists to share others and include themselves. 
 * Version: 0.0.0
 * Author: Nathaniel Norton
 * License: used with permission of author
 */

// Creates nsc-shares type
function nsc_shares_init() {
    $args = array(
      'label' => 'NSC Shares',
        'public' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => true,
        'rewrite' => array('slug' => 'nsc-shares'),
        'query_var' => true,
        'menu_icon' => 'dashicons-share',
        'supports' => array(
            'title',
            'revisions',)
        );
    register_post_type( 'nsc-shares', $args );
}
add_action( 'init', 'nsc_shares_init' );


// Create meta-fields for nsc-shares

function nsc_shares_add_meta_boxes( $post ){
	add_meta_box( 'nsc_shares_meta_box', __( 'Share to grow your friends art, your art, and your community:' ), 'nsc_shares_build_meta_box', 'nsc-shares', 'normal', 'high' );
}
add_action( 'add_meta_boxes_nsc-shares', 'nsc_shares_add_meta_boxes' );


//Creating the actual meta fields for the embed link and description
 function nsc_shares_build_meta_box( $post ){
	// make sure the form request comes from WordPress
	wp_nonce_field( basename( __FILE__ ), 'nsc_shares_meta_box_nonce' );
}

// Store custom field meta box data
function nsc_shares_save_meta_box_data( $post_id ){
	// verify meta box nonce
	if ( !isset( $_POST['nsc_shares_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['nsc_shares_meta_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	// return if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}
  // Check the user's permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}
}
add_action( 'save_post_nsc-shares', 'nsc_shares_save_meta_box_data' );

add_action ('wp_loaded', 'artist_share_process');

function artist_share($the_id){
	if( current_user_can('editor') || current_user_can('administrator') || current_user_can('author') ){
	//print_r($the_id);
	?>
	<div class="artist_share_button">
	<form id="artist_share" name="artist_share" method="post" action="">
	<input type="hidden" name="shared_id" id="shared_id" value="<?php echo $the_id; ?>" />
	<input type="submit" value="Art Share" name="submit" />
	<input type="hidden" name="art_share_action" value="post" />
	</form>
	</div>
	<?php
	wp_nonce_field( 'artist_share' );
	}
}

function artist_share_process(){	
	if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['art_share_action'] )) {
		
		$shared_id = $_POST['shared_id'];
		$shared_title = get_the_title($shared_id);
		$current_user = wp_get_current_user();
		$sharing_title = $shared_title.' shared by '.$current_user->display_name;
		
		//Check to see if the author has shared this before
		global $post;
		$args = array(
		'post_author'	=> $current_user->ID,
		'post_type'		=> 'nsc-shares',
		'meta_query' => array(
			array(
				'key' => 'shared_id',
				'value' => $shared_id,
				)
			),
		);
		$existing_posts = get_posts($args);
		if(!empty($existing_posts)){
		//print_r($existing_posts);
			foreach($existing_posts as $post){
				$redirect_link = get_permalink();
				wp_redirect( $redirect_link );
				exit;
			}
		}else{
		
		$args = array(
		'post_author'	=> $current_user->ID,
		'post_title'	=> $sharing_title,
		'post_type'		=> 'nsc-shares',
		'post_status'	=> 'publish',
		'meta_input'	=> array(
			'shared_id'	=> $shared_id,
			),
		);
		$sharing_id = wp_insert_post($args);
		
		$sharing_url = get_permalink($sharing_id);
		//print_r($sharing_url);
		wp_redirect( $sharing_url );
		exit;
		}
	}
	//do_action('wp_insert_post', 'wp_insert_post');
}

function artist_share_div($post){
	$current_user = get_current_user_id();
	if(is_user_logged_in() && $current_user == $post->post_author) {
		?>
		<div class="artist_share_box">
		<?php fb_share_button(get_permalink()); tw_share_button(get_permalink(), $post->title);  tmblr_share_button(); rddt_share_button(); ?>
		</div>
		<?php
	}
}


//The social media buttons lineup
function nsc_social($post_id, $the_post_link, $the_title){
?>
	<div class="share_box">
<?php
	fb_button($the_post_link);
	tw_button($the_post_link, $title);
	tmblr_share_button();
	rddt_button($the_post_link, $title);
	artist_share($post_id);
?>
	</div>
<?php
}

//Add FB js
function fb_js(){
?> <div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.0&appId=129208993815511&autoLogAppEvents=1';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script><?php
}
add_action( 'wp_enqueue_scripts', 'fb_js' );

//Create FB button for the "share" posts
function fb_share_button($the_post_link){
?>
<div class="fb-share-button" data-href="<?php echo $the_post_link;?>" data-layout="button" data-size="large" data-mobile-iframe="true"><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fdevelopers.facebook.com%2Fdocs%2Fplugins%2F&amp;src=sdkpreparse" class="fb-xfbml-parse-ignore">Share</a></div>
<?php
}

//Create FB button for regular posts
function fb_button($the_post_link){
?>
<div class="fb-share-button" data-href="<?php echo $the_post_link;?>" data-layout="button" data-size="small" data-mobile-iframe="true"><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fdevelopers.facebook.com%2Fdocs%2Fplugins%2F&amp;src=sdkpreparse" class="fb-xfbml-parse-ignore">Share</a></div>
<?php
}

//Create Twitter button for "share" posts
function tw_share_button($the_post_link, $title){
?>
<a href="https://twitter.com/share" class="twitter-share-button" data-size="large" data-text="<?php echo $title; ?>" data-url="<?php echo $the_post_link; ?>" data-show-count="false">Tweet</a><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
<?php
}

//Create Twitter button
function tw_button($the_post_link, $title){
?>
<a href="https://twitter.com/share" class="twitter-share-button" data-text="<?php echo $title; ?>" data-url="<?php echo $the_post_link; ?>" data-show-count="false">Tweet</a><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
<?php
}

//Create Reddit button for "share" posts
function rddt_share_button(){
?>
<a href="//www.reddit.com/submit" onclick="window.location = '//www.reddit.com/submit?url=' + encodeURIComponent(window.location); return false"> <img src="//www.redditstatic.com/spreddit10.gif" alt="submit to reddit" border="0" /> </a>
<?php
}

//Create Reddit button
function rddt_button($the_post_link, $title){
?>
<script type="text/javascript">
  reddit_url = $the_post_link;
  reddit_title = $title;
</script>
<script type="text/javascript" src="//www.redditstatic.com/button/button1.js"></script>
<?php
}


//Create Tumblr share button (move js to js, add formatting for what's shared)
function tmblr_share_button(){
?>
<a class="tumblr-share-button" href="https://www.tumblr.com/share"></a>
<script id="tumblr-js" async src="https://assets.tumblr.com/share-button.js"></script>
<?php
}

 ?>