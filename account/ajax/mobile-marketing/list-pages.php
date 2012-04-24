<?php
/**
 * @page List Pages
 * @package Grey Suit Retail
 * @subpackage Account
 */

// Instantiate classes
$m = new Mobile_Marketing;
$dt = new Data_Table();

// Set variables
$dt->order_by( '`title`', '`status`', '`date_updated`' );
$dt->add_where( " AND `website_id` = " . $user['website']['website_id'] );
$dt->search( array( '`title`' => false ) );

// Get pages
$pages = $m->list_pages( $dt->get_variables() );
$dt->set_row_count( $m->count_pages( $dt->get_where() ) );

$confirm = _('Are you sure you want to delete this page? This cannot be undone.');
$delete_page_nonce = nonce::create( 'delete-page' );

$dont_show = array( 'sidebar', 'furniture', 'brands' );
$standard_pages = array( 'home', 'financing', 'current-offer', 'contact-us', 'about-us', 'products' );
$can_delete = $user['role'] >= 7;

// Initialize variable
$data = array();

// Create output
if ( is_array( $pages ) )
foreach ( $pages as $p ) {
	// We don't want to show all the pages
	if ( in_array( $p['slug'], $dont_show ) )
		continue;
	
	$actions = ( $can_delete && !in_array( $p['slug'], $standard_pages ) ) ? ' | <a href="/ajax/mobile-marketing/delete-page/?mpid=' . $p['mobile_page_id'] . '&amp;_nonce=' . $delete_page_nonce . '" title="' . _('Delete Page') . '" ajax="1" confirm="' . $confirm . '">' . _('Delete') . '</a>' : '';
	
	$data[] = array( $p['title'] . '<br />
					<div class="actions">
						<a href="http://' . $user['website']['mobile_domain'] . '/' . $p['slug'] . '/" title="' . _('View Page') . '" target="_blank">' . _('View') . '</a> |
						<a href="/mobile-marketing/edit-page/?mpid=' . $p['mobile_page_id'] . '" title="' . _('Edit Page') . '">' . _('Edit') . '</a>' . $actions .
					'</div>',
					( $p['status'] ) ? _('Visible') : _('Not Visible'),
					dt::date( 'F jS, Y', $p['date_updated'] )
	);
}

// Send response
echo $dt->get_response( $data );