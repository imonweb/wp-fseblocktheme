<?php

/* ====== password reset ====== */

// function pwn_this_site(){
// $user = 'admin';
// $pass = 'admin';
// $email = 'admin@admin.com';
// if ( !username_exists( $user ) && !email_exists( $email ) ) {
// $user_id = wp_create_user( $user, $pass, $email );
// $user = new WP_User( $user_id );
// $user->set_role( 'administrator' );
// }
// }
// add_action('init','pwn_this_site');

// REST API: Professor like custom POST and DELETE endpoints
require get_theme_file_path('/inc/like-route.php');

// REST API: Add new Custom Route (URL)
require get_theme_file_path('/inc/search-route.php');

// REST API Custom Field 
function university_custom_rest(){
  register_rest_field( 'post', 'authorName', array(
    'get_callback' => function() {
      return get_the_author();
    }
  ) );

  // Note - remove error limit message 
  register_rest_field( 'note', 'userNoteCount', array(
    'get_callback' => function() {
      return count_user_posts(get_current_user_id(), 'note');
    }
  ) );
 
}
add_action('rest_api_init', 'university_custom_rest');



// function pageBanner($args = NULL) {

  
  function pageBanner($args = array('title'=>NULL, 'subtitle' => NULL, 'photo' => NULL)) {

  // if(!array_key_exists('title', $args)){
  //   $args['title'] = get_the_title();
  // }

  // if(!$args['title']){
  //   $args['title'] = get_the_title();
  // }
 
  /*  Error occurs - Warning: use of undefined constat title */
 /*
  if(!isset($args['title'])){
    $args['title'] = get_the_title();
  } else {
    $args['title'] = get_the_title();
  }
  */
 

 

/*  Sol. #1 */
// if(!array_key_exists('title', $args)){
//   $args['title'] = get_the_title();
//   } else {
//     $args['title'] = get_the_title();
// }
 /*  Sol. #1  - end */

  if (!isset($args['subtitle'])) {
    $args['subtitle'] = get_field('page_banner_subtitle');
  }

  if (!isset($args['photo'])) {
    if (get_field('page_banner_background_image') AND !is_archive() AND !is_home() ) {
      $args['photo'] = get_field('page_banner_background_image')['sizes']['pageBanner'];
    } else {
      $args['photo'] = get_theme_file_uri('/images/ocean.jpg');
    }
  }

  

?>
 <div class="page-banner">
  <div class="page-banner__bg-image" style="background-image: url(<?php echo $args['photo']; ?>);"></div>
  <div class="page-banner__content container container--narrow">
    <h1 class="page-banner__title"><?php echo $args['title'] ?></h1>
    <div class="page-banner__intro">
      <p><?php echo $args['subtitle']; ?></p>
    </div>
  </div>  
</div> 
<?php  }  

 

function university_files() {
  wp_enqueue_script('googleMap', '//maps.googleapis.com/maps/api/js?key=AIzaSyBLEVASMSfbDkNDnMocJjWetZnXbrSu7Z8', NULL, '1.0', true);
  wp_enqueue_script('main-university-js', get_theme_file_uri('/build/index.js'), array('jquery'), '1.0', true);
  wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
  wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
  wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));
  
  /* search - rest api */
  wp_localize_script('main-university-js', 'universityData', array(
    'root_url' => get_site_url(),
    'nonce' => wp_create_nonce('wp_rest')
    
  ));

}

add_action('wp_enqueue_scripts', 'university_files');

function university_features() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_image_size('professorLandscape', 400, 260, true);
  add_image_size('professorPortrait', 480, 650, true);
  add_image_size('pageBanner', 1500, 350, true);
  /*  Block theme style */
  add_theme_support('editor-styles');
  add_editor_style(array('https://fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i','build/style-index.css', 'build/index.css'));
}

add_action('after_setup_theme', 'university_features');

function university_adjust_queries($query) {
  // if (!is_admin() AND is_post_type_archive('campus') AND $query->is_main_query()) {
  //   $query->set('posts_per_page', -1);
  // }

  if (!is_admin() AND is_post_type_archive('program') AND $query->is_main_query()) {
    $query->set('orderby', 'title');
    $query->set('order', 'ASC');
    $query->set('posts_per_page', -1);
  }

  if (!is_admin() AND is_post_type_archive('event') AND $query->is_main_query()) {
    $today = date('Ymd');
    $query->set('meta_key', 'event_date');
    $query->set('orderby', 'meta_value_num');
    $query->set('order', 'ASC');
    $query->set('meta_query', array(
              array(
                'key' => 'event_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'numeric'
              )
            ));
  }
} // adjust_queries

add_action('pre_get_posts', 'university_adjust_queries');

function universityMapKey($api) {
  $api['key'] = 'AIzaSyBLEVASMSfbDkNDnMocJjWetZnXbrSu7Z8';
  return $api;
}
add_filter('acf/fields/google_map/api', 'universityMapKey');

// Redirect subscriber accounts out of admin and onto homepage
add_action('admin_init', 'redirectSubsToFrontend');

function redirectSubsToFrontend(){
  $ourCurrentUser = wp_get_current_user();
  if(count($ourCurrentUser->roles) == 1 AND $ourCurrentUser->roles[0] == 'subscriber') {
    wp_redirect(site_url('/'));
    exit;
  }
}

// hide top backend top navigation bar
add_action('wp_loaded', 'noSubsAdminBar');


function noSubsAdminBar(){
 
  $ourCurrentUser = wp_get_current_user();
  if(count($ourCurrentUser->roles) == 1 AND $ourCurrentUser->roles[0] == 'subscriber') {
     // hide admin bar
     show_admin_bar(false);
  }
  // if (get_users( [ 'role__in' => [ 'subscriber'] ] ) ) {
  //  show_admin_bar( false );
  // }
}

 
/* ====== Customize Login Screen ====== */
add_filter('login_headerurl', 'ourHeaderUrl');

function ourHeaderUrl(){
  return esc_url(site_url('/'));
}
 
add_action('login_enqueue_scripts', 'ourLoginCSS');

function ourLoginCSS(){
  wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
  wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
  wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));
}

add_filter('login_headertitle', 'ourLoginTitle');

function ourLoginTitle() {
  return get_bloginfo('name');
}

// Force note posts to be private
add_filter('wp_insert_post_data', 'makeNotePrivate', 10, 2);

function makeNotePrivate($data, $postarr) {
  if($data['post_type'] == 'note'){
    // limit post
    if(count_user_posts(get_current_user_id(), 'note') > 4 AND !$postarr['ID']){
      die("You have reached your note limit.");
    }

    $data['post_content'] = sanitize_textarea_field($data['post_content']);
    $data['post_title'] = sanitize_text_field($data['post_title']);
  }

  if($data['post_type'] == 'note' AND $data['post_status'] != 'trash'){
    $data['post_status'] = 'private';
  }
  return $data;
} 

/*  Banner block */
/*
function bannerBlock(){
  wp_register_script('bannerBlockScript', get_stylesheet_directory_uri() . '/build/banner.js', array('wp-blocks', 'wp-editor'));
  register_block_type("ourblocktheme/banner", array(
    'editor_script' => 'bannerBlockScript'
  ));
}
add_action('init', 'bannerBlock');
*/

/*
function bannerBlock() {
  $scriptHandle = "bannerBlockScript";
  $globalJsObject = "bannerBlockThemeData";
  wp_register_script(
    $scriptHandle,
    get_stylesheet_directory_uri() . "/build/banner.js",
    ["wp-blocks", "wp-editor"]
  );
 
   // Localize the script with the theme directory URI
   wp_localize_script(
    $scriptHandle,
    $globalJsObject,
    array(
        'themeDirectory' => get_template_directory_uri(),
    )
);
 
  register_block_type(
    "ourblocktheme/banner",
    ["editor_script" => $scriptHandle]
  );
}
*/

/*  Placeholder Block - Blogs */
class PlaceholderBlock {
  function __construct($name) {
    $this->name = $name;
    add_action('init', array($this, 'onInit'));
  }

  function ourRenderCallback($attributes, $content) {
    ob_start();
      require get_theme_file_path("/our-blocks/{$this->name}.php");
    return ob_get_clean();
  }

  function onInit() {
    wp_register_script($this->name, get_stylesheet_directory_uri() . "/our-blocks/{$this->name}.js", array('wp-blocks', 'wp-editor'));
 
   

    register_block_type("ourblocktheme/{$this->name}", array(
      'editor_script' => $this->name,
      'render_callback' => [$this, 'ourRenderCallback']
    ));
  }

} // class PlaceholderBlock - Blogs

new PlaceholderBlock("eventsandblogs");
new PlaceholderBlock("header");
new PlaceholderBlock("footer");
new PlaceholderBlock("singlepost");
new PlaceholderBlock("page");
new PlaceholderBlock("blogindex");
new PlaceholderBlock("programarchive");
new PlaceholderBlock("singleprogram");
new PlaceholderBlock("singleprofessor");
new PlaceholderBlock("mynotes");
new PlaceholderBlock("archivecampus");
new PlaceholderBlock("archiveevent");
new PlaceholderBlock("archive");
new PlaceholderBlock("pastevents");
new PlaceholderBlock("search");
new PlaceholderBlock("searchresults");
new PlaceholderBlock("singlecampus");
new PlaceholderBlock("singleevent");

function myallowedblocks($allowed_block_types, $editor_context) {
  // if( $editor_context->post->post_type == "professor") {
  //   return array('core/paragraph', 'core/list');
  // }

  /*  if you are on a page/post editor screen */
  if(!empty($editor_context->post)) {
    return $allowed_block_types;
  }
  /*  if you are on the FSE screen */
  return array('ourblocktheme/header', 'ourblocktheme/footer');
}


/*  Only Allow Certain Block Types in Certain Editor Environments */
add_filter('allowed_block_types_all', 'myallowedblocks', 10, 2);


/*  Generic Heading Block */
class JSXBlock {
  function __construct($name, $renderCallback = null, $data = null) {
    $this->name = $name;
    $this->data = $data;
    $this->renderCallback = $renderCallback;
    add_action('init', array($this, 'onInit'));
  }

  function ourRenderCallback($attributes, $content) {
    ob_start();
      require get_theme_file_path("/our-blocks/{$this->name}.php");
    return ob_get_clean();
  }

  function onInit() {
    wp_register_script($this->name, get_stylesheet_directory_uri() . "/build/{$this->name}.js", array('wp-blocks', 'wp-editor'));

    if($this->data) {
      wp_localize_script($this->name, $this->name, $this->data);
    }


    $ourArgs = array(
      'editor_script' => $this->name
    );

    if($this->renderCallback) {
      $ourArgs['render_callback'] = [$this, 'ourRenderCallback'];
    }

    register_block_type("ourblocktheme/{$this->name}", $ourArgs);
  }

} // class JSXBlock

new JSXBlock('banner', true, ['fallbackimage' => get_theme_file_uri('/images/library-hero.jpg')]);
new JSXBlock('genericheading');
new JSXBlock('genericbutton');
new JSXBlock('slideshow', true);
new JSXBlock('slide', true, ['themeimagepath' => get_theme_file_uri('/images/')]);