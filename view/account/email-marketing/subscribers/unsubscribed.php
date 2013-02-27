<?php
/**
 * @package Grey Suit Retail
 * @page Unsubscriberd | Email Marketing
 *
 * Declare the variables we have available from other sources
 * @var Resources $resources
 * @var Template $template
 * @var User $user
 * @var bool $logged_in
 */

echo $template->start( _('Unsubscribed'), '../sidebar' );
?>
<table ajax="/email-marketing/subscribers/list-all/?s=0<?php if ( isset( $_GET['elid'] ) ) echo '&elid=' . $_GET['elid']; ?>" perPage="30,50,100">
    <thead>
        <tr>
            <th width="30%" sort="1"><?php echo _('Email'); ?></th>
            <th width="30%"><?php echo _('Name'); ?></th>
            <th width="20%"><?php echo _('Phone'); ?></th>
            <th width="20%"><?php echo _('Date Unsubscribed'); ?></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<?php echo $template->end(); ?>