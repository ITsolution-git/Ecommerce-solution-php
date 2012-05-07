<?php
/**
 * @page Add Edit Subscriber
 * @package Grey Suit Retail
 */

// Get current user
global $user;

// If user is not logged in
if ( !$user )
	login();

// Secure the section
if ( !$user['website']['mobile_marketing'] )
    url::redirect('/');

$m = new Mobile_Marketing;

// Get the mobile subscriber id if there is one
$mobile_subscriber_id = ( isset( $_GET['msid'] ) ) ? $_GET['msid'] : false;

$v = new Validator();
$v->form_name = 'fAddEditSubscriber';
$v->add_validation( 'tPhone', 'req' , _('The "Phone" field is required') );
$v->add_validation( 'tPhone', 'minlen=10' , _('The "Phone" field must be at least 10 digits') );
$v->add_validation( 'tPhone', 'phone' , _('The "Phone" field may only contain a valid phone number') );
$v->add_validation( 'cbMobileLists', 'req', _('You must choose at least one list') );

// Add validation
add_footer( $v->js_validation() );

// Initialize variable
$success = false;

// Make sure it's a valid request
if ( isset( $_POST['_nonce'] ) && nonce::verify( $_POST['_nonce'], 'add-edit-subscriber' ) ) {
	$errs = $v->validate();
	
	// if there are no errors
	if ( empty( $errs ) ) {
        // We only want the number
        $phone = preg_replace( '/[^0-9]/', '', $_POST['tPhone'] );

		if ( $mobile_subscriber_id ) {
			// Update subscriber
			$success = $m->update_subscriber( $mobile_subscriber_id, $phone, $_POST['cbMobileLists'] );
		} else {
			// Add subscriber
			if ( $subscriber = $m->subscriber_exists( $phone ) && '2' == $subscriber['status'] ) {
				$errs .= _('This subscriber has been unsubscribed by the user.') . '<br />';
				$success = false;
			} else {
				$success = $m->create_subscriber( $phone, $_POST['cbMobileLists'] );
				
				if ( !$success )
					$errs .= _('An error occurred while adding subscriber.') . '<br />';
			}
		}
	}
}

// Get mobile lists
$mobile_lists = $m->get_mobile_lists();

// Get the subscriber if necessary
if ( $mobile_subscriber_id ) {
	$subscriber = $m->get_subscriber( $mobile_subscriber_id );
} else {
	// Initialize variable
	$subscriber = array(
		'phone' => ''
		, 'mobile_lists' => ''
	);
}

$selected = "mobile_marketing";
$sub_title = ( $mobile_subscriber_id ) ? _('Edit Subscriber') : _('Add Subscriber');
$title = "$sub_title | " . _('Mobile Marketing') . ' | ' . TITLE;
get_header();
?>

<div id="content">
	<h1><?php echo $sub_title; ?></h1>
	<br clear="all" /><br />
	<?php get_sidebar( 'mobile-marketing/', 'subscribers', 'add_edit_mobile_subscribers' ); ?>
	<div id="subcontent">
		<?php if ( $success ) { ?>
		<div class="success">
			<p><?php echo ( $mobile_subscriber_id ) ? _('Your subscriber has been updated successfully!') : _('Your subscriber has been added successfully!'); ?></p>
			<p><?php echo '<a href="/mobile-marketing/subscribers/" title="', _('Subscribers'), '">', _('Click here to view your subscribers'), '</a>.'; ?></p>
		</div>
		<?php 
		}
		
		// Allow them to edit the entry they just created
		if ( $success && !$mobile_subscriber_id )
			$mobile_subscriber_id = $success;
		
		if ( isset( $errs ) )
				echo "<p class='red'>$errs</p>";
		?>
		<form name="fAddEditSubscriber" action="/mobile-marketing/subscribers/add-edit/<?php if ( $mobile_subscriber_id ) echo "?msid=$mobile_subscriber_id"; ?>" method="post">
			<?php nonce::field( 'add-edit-subscriber' ); ?>
			<table cellpadding="0" cellspacing="0">
				<tr><td colspan="2" class="title"><strong><?php echo _('Basic Information'); ?></strong></td></tr>
				<tr>
					<td><label for="tPhone"><?php echo _('Phone'); ?>:</label></td>
					<td><input type="text" class="tb" name="tPhone" id="tPhone" maxlength="20" value="<?php echo ( !$success && isset( $_POST['tPhone'] ) ) ? $_POST['tPhone'] : $subscriber['phone']; ?>" /></td>
				</tr>
                <tr>
                    <td class="top"><label><?php echo _('Lists'); ?></label>:</td>
                    <td>
                        <p>
                            <?php
                            $selected_mobile_lists = ( !$success && isset( $_POST['cbMobileLists'] ) ) ? $_POST['cbMobileLists'] : array_keys( $subscriber['mobile_lists'] );

                            foreach ( $mobile_lists as $ml ) {
                                $checked = ( in_array( $ml['mobile_list_id'], $selected_mobile_lists ) ) ? ' checked="checked"' : '';
                            ?>
                            <input type="checkbox" class="cb" name="cbMobileLists[]" id="cbMobileList<?php echo $ml['mobile_list_id']; ?>" value="<?php echo $ml['mobile_list_id']; ?>"<?php echo $checked; ?> /> <label for="cbMobileList<?php echo $ml['mobile_list_id']; ?>"><?php echo $ml['name']; ?></label>
                            <br />
                            <?php } ?>
                        </p>
					</td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" class="button" value="<?php echo ( $mobile_subscriber_id ) ? _('Update Subscriber') : _('Add Subscriber'); ?>" /></td>
				</tr>
			</table>
		</form>
		<br /><br />
	</div>
	<br /><br />
</div>

<?php get_footer(); ?>