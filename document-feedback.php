<?php
/**
 * Plugin Name: Document Feedback
 * Plugin URI: http://wordpress.org/extend/plugins/document-feedback/
 * Description: Close the loop &mdash; get feedback from readers on the documentation you write
 * Version: 1.3
 * Author: Daniel Bachhuber, Automattic
 * Author URI: http://automattic.com/
 * License: GPLv2 or later
 *
 * @package Document_Feedback
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! class_exists( 'Document_Feedback' ) ) {
	require_once __DIR__ . '/class-document-feedback.php';
}

/**
 * Plugin loader
 */
function document_feedback() {
	return Document_Feedback::get_instance();
}
add_action( 'plugins_loaded', 'document_feedback' );
