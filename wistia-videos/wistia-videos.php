<?php
/*
Plugin Name: Wistia Videos Downloader
Plugin URI: wistia.devstetic.dev
Description: Download wistia videos from Account and send them to Dropbox
Author: Devstetic
Version: 1.0
*/

/**
   * Add Post Type: Wistia Videos.
   */


function register_my_cpts_wistia_video() {
  
  $labels = [
    "name" => __( "Wistia Videos", "twentytwenty" ),
    "singular_name" => __( "Wistia Video", "twentytwenty" ),
  ];

  $args = [
    "label" => __( "Wistia Videos", "twentytwenty" ),
    "labels" => $labels,
    "description" => "",
    "public" => true,
    "publicly_queryable" => true,
    "show_ui" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "rest_controller_class" => "WP_REST_Posts_Controller",
    "has_archive" => false,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "delete_with_user" => false,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "rewrite" => [ "slug" => "wistia_video", "with_front" => true ],
    "query_var" => true,
    "supports" => [ "title", "editor", "thumbnail" ],
  ];

  register_post_type( "wistia_video", $args );
}

add_action( 'init', 'register_my_cpts_wistia_video' );

/**
   * Add Taxonomy: Video Categories.
   */

function register_my_taxes_video_cat() {

  $labels = [
    "name" => __( "Video Categories", "twentytwenty" ),
    "singular_name" => __( "Video Category", "twentytwenty" ),
  ];

  $args = [
    "label" => __( "Video Categories", "twentytwenty" ),
    "labels" => $labels,
    "public" => true,
    "publicly_queryable" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => [ 'slug' => 'video_cat', 'with_front' => true, ],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "rest_base" => "video_cat",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "show_in_quick_edit" => false,
    ];
  register_taxonomy( "video_cat", [ "wistia_video" ], $args );
}
add_action( 'init', 'register_my_taxes_video_cat' );


/* Add Options Pages */
function create_options_pages() {
  add_submenu_page( 'edit.php?post_type=wistia_video', 'Import Videos', 'Import Videos',
    'manage_options', 'import_videos', 'import_videos_page_content');
  add_submenu_page( 'edit.php?post_type=wistia_video', 'Send to Dropbox', 'Send to Dropbox',
    'manage_options', 'send_dropbox', 'send_dropbox_page_content');
}

add_action('admin_menu', 'create_options_pages');


/* Custom page functions */

function import_videos_page_content() { ?>

    <style>
      .wistia_video_page_import_videos #wpbody {
        background: #fff;
        padding: 20px;
      }

      .getvideos label {
        font-size: 16px;
        font-weight: 300;
      }

      .getvideos input[type="text"] {
        font-size: 16px;
        color: #000;
        padding: 5px 10px;
        margin-bottom: 10px;
        margin-top: 10px;
      }

      .getvideos input[type="submit"] {
        font-size: 16px;
        color: #fff;
        font-weight: bold;
        background: #0691F2;
        padding: 10px 25px;
        border: 0;
        box-shadow: 0;
        border-radius: 5px;
        cursor: pointer;
      }

      .getvideos input[type="submit"]:hover {
        background: #2A2D35;
      }

    </style>
    <div>
    <?php screen_icon(); ?>
    <h2>Import Videos</h2>
    <form method="post" class="getvideos">
      <label for="token">Account Access Token</label><br>
      <input type="text" size="100" class="token" name="token" Placeholder="Insert Access Token"><br>
      <input type="submit" Value="Get Videos">
    </form>
    </div>
 <?php 

 if(isset($_POST['token'])) {
  $token = $_POST['token'];
  $accurl = 'https://api.wistia.com/v1/account.json?access_token='.$token;
  //echo 'Videos importing...';
  $accjson = file_get_contents($accurl);

  $accdata = json_decode($accjson);
  $accname = $accdata->name;
  $accid = $accdata->id;

  $mcount = $accdata->mediaCount;
  echo "<p>Importing data from account <strong>" . $accname . "</strong> with id <strong>" . $accdata->id . "</strong> and access token <strong>" . $token . "</strong></p>";

  ?>
  <?php

  if($mcount <= 100) {
    $medurl = 'https://api.wistia.com/v1/medias.json?access_token='.$token;

    $medjson = file_get_contents($medurl);
    $meddata = json_decode($medjson);

    foreach ($meddata as $item) {
      $name = $item->name;
      $urlbin = $item->assets[0]->url;

      $url = substr($urlbin, 0, -4);

      $args = array(
        'post_title' => $name,
        'post_content' => $url,
        'post_status' => 'publish',
        'post_type' => 'wistia_video',
        'tax_input' => array(
            'video_cat' => $accname,
        ),
        'meta_input' => array(
            'video_url' => $url,
            'account_id' => $accid,
            'account_name' => $accname,
        ),
      );

      $post = wp_insert_post($args);

    }

    echo "<h4>Successfully Imported " . $mcount . " Videos!</h4>";

  } else {
    $counter = ceil($mcount / 100);

    $catcounter = ceil($counter / 3);

    if ($catcounter > 1) {
      for ($j = 0; $j < $catcounter; $j++) {


          $offset = $j * 3;
          $nextchk = $offset + 3;

          if ($nextchk > $counter) {
            $next = $counter;
          } else {
            $next = $nextchk;
          }

          $realoff = $offset + 1;

        $accnamecur = $accname . '-' . $j;

        for ($i = $realoff; $i <= $next; $i++) {  
          $medurl = 'https://api.wistia.com/v1/medias.json?page='.$i.'&access_token='.$token;        
          
          $medjson = file_get_contents($medurl);
          $meddata = json_decode($medjson);   

          foreach ($meddata as $item) {
            $name = $item->name;
            $urlbin = $item->assets[0]->url;
    
            $url = substr($urlbin, 0, -4);
    
            $args = array(
              'post_title' => $name,
              'post_content' => $url,
              'post_status' => 'publish',
              'post_type' => 'wistia_video',
              'tax_input' => array(
                  'video_cat' => $accnamecur,
              ),
              'meta_input' => array(
                  'video_url' => $url,
                  'account_id' => $accid,
                  'account_name' => $accname,
              ),
            );
    
            $post = wp_insert_post($args);
            
          }
        }
      }
    } else {
      for ($i = 1; $i <= $counter; $i++) {

        $medurl = 'https://api.wistia.com/v1/medias.json?page='.$i.'&access_token='.$token;
  
        $medjson = file_get_contents($medurl);
        $meddata = json_decode($medjson);
  
        foreach ($meddata as $item) {
          $name = $item->name;
          $urlbin = $item->assets[0]->url;
  
          $url = substr($urlbin, 0, -4);
  
          $args = array(
            'post_title' => $name,
            'post_content' => $url,
            'post_status' => 'publish',
            'post_type' => 'wistia_video',
            'tax_input' => array(
                'video_cat' => $accname,
            ),
            'meta_input' => array(
                'video_url' => $url,
                'account_id' => $accid,
                'account_name' => $accname,
            ),
          );
  
          $post = wp_insert_post($args);
          
        }
      }
    }    
    echo "<h4>Successfully Imported " . $mcount . " Videos!</h4>";
  ?>
<?php }

}
}

function send_dropbox_page_content() { ?>
  <style>
      .wistia_video_page_send_dropbox #wpbody {
        background: #fff;
        padding: 20px;
      }

      .senddrop label {
        font-size: 16px;
        font-weight: 300;
      }

      .senddrop select {
        font-size: 16px;
        color: #000;
        width: 500px;
        padding: 5px 10px;
        margin-bottom: 10px;
        margin-top: 10px;
      }

      .senddrop input[type="submit"] {
        font-size: 16px;
        color: #fff;
        font-weight: bold;
        background: #0691F2;
        padding: 10px 25px;
        border: 0;
        box-shadow: 0;
        border-radius: 5px;
        cursor: pointer;
      }

      .senddrop input[type="submit"]:hover {
        background: #2A2D35;
      }

    </style>
  <div>
    <?php 

    screen_icon(); 

    $vidcats = get_terms( array(
        'taxonomy' => 'video_cat',
        'hide_empty' => false
    ) );

    ?>
    <h2>Send Videos To Dropbox</h2>
    <form method="post" class="senddrop">
      <label for="vidcat">Select Video Category</label><br>
      <select name="vidcat" id="vidcat">
        <?php foreach ($vidcats as $vidcat): ?>
            <option value="<?php echo $vidcat->slug; ?>"><?php echo $vidcat->name; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="submit" Value="Push to Dropbox">
    </form>


  </div>

  <?php if(isset($_POST['vidcat'])) {
    $vidcat = $_POST['vidcat'];

    //$vcat = get_term_by('slug', $vidcat, 'video_cat');

    $pargs = array(
      'post_type' => 'wistia_video',
      'posts_per_page' => -1,
      'tax_query' => array(
        array(
          'taxonomy' => 'video_cat',
          'field' => 'slug',
          'terms' => $vidcat
        )
      )
    );

    $videos = get_posts($pargs);

    foreach ($videos as $video) {

      $vidname = $video->post_title;     

      $vidurl = get_post_meta( $video->ID, 'video_url', true );
      $dbtoken = '';

      echo '<p>'. $vidname . 'is uploading...</p>';

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, "https://api.dropboxapi.com/2/files/save_url");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"path\": \"/".$vidcat."/".$vidname.".mp4\",\"url\": \"".$vidurl."\"}");
      curl_setopt($ch, CURLOPT_POST, 1);


      $headers = array();
      $headers[] = "Authorization: Bearer ".$dbtoken;
      $headers[] = "Content-Type: application/json";
      //
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
      if (curl_errno($ch)) {
          echo 'Error '.curl_error($ch);
      }else{
        echo '<h4>Video '.$vidname.' Uploaded to DropBox</h4>';
      }
      curl_close ($ch);
      }

    }
}
