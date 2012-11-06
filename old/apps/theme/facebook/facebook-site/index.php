<?php
/**
 * @page Facebook Site
 * @package Grey Suit Retail
 */

global $user;

// Instantiate Classes
$fb = new FB( '114243368669744', 'bad9a248b9126bdd62604ccd909f8d2d', 'op-facebook-site' );
$fs = new Facebook_Site;
$v = new Validator;

// Get User
$user = $fb->user;

// Set Validation
$v->add_validation( 'tFBConnectionKey', 'req', _('The "Facebook Connection Key" field is required') );

// Make sure they are validly editing the app
if ( isset( $_REQUEST['app_data'] ) ) {
	// Get App Data
	$app_data = url::decode( $_REQUEST['app_data'] );
	$other_user_id = security::decrypt( $app_data['uid'], 'SecREt-Us3r!' );
	$page_id = security::decrypt( $app_data['pid'], 'sEcrEt-P4G3!' );
}

// Make sure it's a valid request
if( $other_user_id == $user && $page_id ) {
	if( nonce::verify( $_POST['_nonce'], 'connect-to-field' ) ) {
		$errs = $v->validate();
		
		// if there are no errors
		if( empty( $errs ) )
			$success = $fs->connect( $page_id, $_POST['tFBConnectionKey'] );
	}
}

// Get the website
if( $page_id )
	$website = $fs->get_connected_website( $page_id );

add_footer('<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({appId: "114243368669744", status: true, cookie: true,
             xfbml: true});
	FB.setSize({ width: 720, height: 500 });
  };
  (function() {
    var e = document.createElement("script"); e.async = true;
    e.src = document.location.protocol +
      "//connect.facebook.net/en_US/all.js";
    document.getElementById("fb-root").appendChild(e);
  }());
</script>');
$title = _('Facebook Site') . ' | ' . _('Online Platform');
get_header('facebook/');
?>

<div id="header">
    <div id="logo">
        <a href="http://www.greysuitapps.com/" title="Grey Suit Apps"><img src="https://www.greysuitapps.com/fb/images/trans.gif" alt="Grey Suit Apps" /></a>
    </div><!-- #logo -->

    <?php if( $success && $website ) { ?>
    <div class="success">
        <p><?php echo _('Your information has been successfully updated!'); ?></p>
    </div>
    <?php
    }

    if( isset( $errs ) )
        echo "<p class='error'>$errs</p>";

    if( !$page_id ) {
    ?>
	<div id="nav">
            <ul>
                <li id="nav-apps"><a id="aTabApps" class="fb-tab" href="#" title="Apps"><img src="https://www.greysuitapps.com/fb/images/trans.gif" alt="Apps" /></a></li>
                <li id="nav-pricing"><a id="aTabPricing" class="fb-tab" href="#" title="Pricing"><img src="https://www.greysuitapps.com/fb/images/trans.gif" alt="Pricing" /></a></li>
                <!-- <li id="nav-faqs"><a id="aTabFaqs" class="fb-tab" href="#" title="FAQs"><img src="https://www.greysuitapps.com/fb/images/trans.gif" alt="FAQs" /></a></li> -->
            </ul>
        </div><!-- #nav -->
    </div><!-- #header -->
    <div id="content">
    	<div id="apps" class="fb-tab-wrapper">
	        <div id="apps-header">
	            <div id="apps-header-1">
	                <h2>1. Purchase the Apps</h2>
	                <p>You'll be redirected to GreySuitApps.com<br />
	                to choose your monthly plan.</p>
	            </div>

	            <div id="apps-header-2">
	                <h2>2. Return to Facebook</h2>
	                <p>After you make your purchase you'll<br />
	                head back here to begin.</p>
	            </div>

	            <div id="apps-header-3">
	                <h2>3. Get Started!</h2>
	                <p>Start using your new apps now to<br />
	                get your message out there!</p>
	            </div>
	        </div><!-- #apps-header -->
	        <div id="apps-container" class="clear">

	            <?php theme_inc( 'facebook/app-sidebar', true); ?>

	            <div id="apps-content" class="clear">
	               <div id="apps-icon">
                    <img src="https://www.greysuitapps.com/fb/images/icons/fb-home.png" alt="<?php echo _('Facebook Site'); ?>" />
	                </div>
	                <div id="apps-desc">
	                    <h1>Facebook Site</h1>
	                    <p><a href="#" onclick="top.location.href='http://www.greysuitapps.com/pricing/'" title="Purchase this App"><img src="https://www.greysuitapps.com/fb/images/buttons/purchase-app.png" alt="Purchase this App" /></a></p>
	                    <p><a href="#" onclick="top.location.href='http://www.facebook.com/add.php?api_key=114243368669744&pages=1'"title="Install this App" class="install-app"><img src="https://www.greysuitapps.com/fb/images/trans.gif" alt="Install this App" /></a></p>
	                    <p class="sml-text">gives you access to ALL apps</p>
	                </div>
	            </div>
	        </div><!-- #apps-container .clear -->
	    </div> <!-- #apps -->

        <?php theme_inc( 'facebook/price-tab', true ); ?>
        <?php theme_inc( 'facebook/faq-tab', true ); ?>

	<?php } else { ?>
	</div>
    <div id="content">
        <form name="fConnect" id="fConnect" method="post" action="/facebook/facebook-site/">
        <table cellpadding="0" cellspacing="0">
            <tr>
                <td width="220" class="align-right"><strong><?php echo _('Website'); ?>:</strong></td>
                <td><?php echo ( $website ) ? $website['title'] : 'N/A'; ?></td>
            </tr>
            <tr>
                <td class="align-right"><label for="tFBConnectionKey"><?php echo _('Facebook Connection Key'); ?>:</label></td>
                <td><input type="text" class="tb" name="tFBConnectionKey" id="tFBConnectionKey" value="<?php echo ( $website ) ? $website['key'] : ''; ?>" /> <strong><?php echo ( $website ) ? '<span class="success">(' . _('Connected') . ')</span>' : '<span class="error">(' . _('Not Connected') . ')</span>'; ?></strong></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><input type="submit" class="button" value="<?php echo _('Connect'); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="app_data" value="<?php echo $_REQUEST['app_data']; ?>" />
	    <?php nonce::field('connect-to-field'); ?>
	</form>
	<?php } ?>
</div>

<?php get_footer('facebook/'); ?>