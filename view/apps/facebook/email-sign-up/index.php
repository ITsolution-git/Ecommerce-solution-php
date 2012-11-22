<?php
/**
 * @page Email Sign Up
 * @package Grey Suit Retail
 *
 * @var int $app_id
 * @var bool $success
 * @var array $website
 */

?>

<div id="header">
    <div id="logo">
        <a href="http://www.greysuitapps.com/" title="Grey Suit Apps"><img src="https://www.greysuitapps.com/fb/images/trans.gif" alt="Grey Suit Apps" /></a>
    </div><!-- #logo -->


    <?php if ( $success && $website ) { ?>
    <div class="success">
        <p><?php echo _('Your information has been successfully updated!'); ?></p>
    </div>
    <?php
    }

    if ( isset( $errs ) )
        echo "<p class='error'>$errs</p>";

    if ( !$page_id ) {
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

            <?php require VIEW_PATH . 'facebook/app-sidebar.php'; ?>

            <div id="apps-content" class="clear">
                <div id="apps-icon">
                    <img src="https://www.greysuitapps.com/fb/images/icons/email-sign-up.png" alt="Email Sign Up" />
                </div>
                <div id="apps-desc">
                    <h1>Email Sign Up</h1>
                    <h3>Collect the names and email addresses from fans who opt-in to your email marketing campaign</h3>
                    <ul>
                        <li>Build up a database of thousands of subscribers</li>
                        <li>Send timely, targeted email blasts and get in front of your competition</li>
                        <li>Blast your latest promotions and advertisements to your legion of subscribers</li>
                    </ul>
                    <p><a href="#" onclick="top.location.href='http://www.greysuitapps.com/pricing/'" title="Purchase this App"><img src="https://www.greysuitapps.com/fb/images/buttons/purchase-app.png" alt="Purchase this App" /></a></p>
                    <p><a href="#" onclick="top.location.href='http://www.facebook.com/add.php?api_key=<?php echo $app_id; ?>&pages=1'" title="Install this App" class="install-app"><img src="https://www.greysuitapps.com/fb/images/trans.gif" alt="Install this App" /></a></p>
                    <p class="sml-text">gives you access to ALL apps</p>
                </div>
            </div>
        </div><!-- #apps-container .clear -->
    </div><!-- #install -->

    <?php require VIEW_PATH . 'facebook/price-tab.php'; ?>
    <?php require VIEW_PATH . 'facebook/faq-tab.php'; ?>
    <?php } else { ?>
    </div> <!-- end #header -->
    <div id="content">
        <form name="fConnect" id="fConnect" method="post" action="/facebook/current-ad/">
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

<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({appId: "<?php echo $app_id; ?>", status: true, cookie: true,
             xfbml: true});
	FB.setSize({ width: 720, height: 500 });
  };
  (function() {
    var e = document.createElement("script"); e.async = true;
    e.src = document.location.protocol +
      "//connect.facebook.net/en_US/all.js";
    document.getElementById("fb-root").appendChild(e);
  }());
</script>