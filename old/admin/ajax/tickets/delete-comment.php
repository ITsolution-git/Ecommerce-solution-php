<?php
/**
 * @page Delete Comment
 * @package Grey Suit Retail
 * @subpackage Graphs
 */
 
if ( isset( $_POST['_nonce'] ) && nonce::verify( $_POST['_nonce'], 'delete-comment' ) ) {
	if ( !$user ) {
		echo json_encode( array( 'result' => false, 'error' => _('You must be signed in to delete a ticket comment.') ) );
		exit;
	}
	
	$tc = new Ticket_Comments;
	
	$result = $tc->delete( $_POST['tcid'] );
	
	// If there was an error, let them know
	echo json_encode( array( 'result' => $result, 'error' => _('An error occurred while trying to delete the ticket comment. Please refresh the page and try again.') ) );
} else {
	echo json_encode( array( 'result' => false, 'error' => _('A verification error occurred. Please refresh the page and try again.') ) );
}