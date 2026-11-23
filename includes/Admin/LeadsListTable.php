<?php
/**
 * Leads list table.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Admin;

use Wbdres\RealEstate\Leads\LeadRepository;
use Wbdres\RealEstate\Leads\LeadService;
use Wbdres\RealEstate\PostTypes\Registrar;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * LeadsListTable.
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
 */
final class LeadsListTable extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'lead',
				'plural'   => 'leads',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$cols = array(
			'cb'       => '<input type="checkbox" />',
			'name'     => __( 'Contact', 'wbd-real-estate-suite' ),
			'property' => __( 'Property', 'wbd-real-estate-suite' ),
			'type'     => __( 'Type', 'wbd-real-estate-suite' ),
			'status'   => __( 'Status', 'wbd-real-estate-suite' ),
			'agent'    => __( 'Agent', 'wbd-real-estate-suite' ),
			'date'     => __( 'Received', 'wbd-real-estate-suite' ),
		);
		return $cols;
	}

	/**
	 * Sortable.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'name'   => array( 'name', false ),
			'status' => array( 'status', false ),
			'type'   => array( 'type', false ),
			'date'   => array( 'created_at', true ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array();
		foreach ( LeadRepository::statuses() as $key => $label ) {
			/* translators: %s: status. */
			$actions[ 'status_' . $key ] = sprintf( __( 'Mark as: %s', 'wbd-real-estate-suite' ), $label );
		}
		$actions['export'] = __( 'Export to CSV', 'wbd-real-estate-suite' );
		if ( current_user_can( 'wbdres_manage_leads' ) ) {
			$actions['delete'] = __( 'Delete permanently', 'wbd-real-estate-suite' );
		}
		return $actions;
	}

	/**
	 * Status views.
	 *
	 * @return array
	 */
	protected function get_views() {
		$counts  = LeadRepository::counts( LeadService::scope() );
		$current = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$base    = admin_url( 'admin.php?page=wbdres-leads' );
		$views   = array(
			'all' => sprintf( '<a href="%s"%s>%s <span class="count">(%s)</span></a>', esc_url( $base ), '' === $current ? ' class="current" aria-current="page"' : '', esc_html__( 'All', 'wbd-real-estate-suite' ), esc_html( number_format_i18n( $counts['all'] ) ) ),
		);
		foreach ( LeadRepository::statuses() as $key => $label ) {
			$c = (int) ( $counts[ $key ] ?? 0 );
			if ( ! $c && $current !== $key ) {
				continue;
			}
			$views[ $key ] = sprintf( '<a href="%s"%s>%s <span class="count">(%s)</span></a>', esc_url( add_query_arg( 'status', $key, $base ) ), $current === $key ? ' class="current" aria-current="page"' : '', esc_html( $label ), esc_html( number_format_i18n( $c ) ) );
		}
		return $views;
	}

	/**
	 * Extra filters.
	 *
	 * @param string $which Position.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$agent = isset( $_GET['agent'] ) ? absint( $_GET['agent'] ) : 0;
		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="wbdres-filter-type">' . esc_html__( 'Filter by type', 'wbd-real-estate-suite' ) . '</label><select name="type" id="wbdres-filter-type"><option value="">' . esc_html__( 'All types', 'wbd-real-estate-suite' ) . '</option>';
		foreach ( LeadRepository::types() as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $type, $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		if ( current_user_can( 'wbdres_manage_leads' ) ) {
			$agents = get_posts(
				array(
					'post_type'      => Registrar::AGENT,
					'posts_per_page' => 100,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
			echo '<label class="screen-reader-text" for="wbdres-filter-agent">' . esc_html__( 'Filter by agent', 'wbd-real-estate-suite' ) . '</label><select name="agent" id="wbdres-filter-agent"><option value="">' . esc_html__( 'All agents', 'wbd-real-estate-suite' ) . '</option>';
			foreach ( $agents as $a ) {
				echo '<option value="' . esc_attr( $a->ID ) . '"' . selected( $agent, $a->ID, false ) . '>' . esc_html( get_the_title( $a ) ) . '</option>';
			}
			echo '</select>';
		}
		submit_button( __( 'Filter', 'wbd-real-estate-suite' ), '', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * Load items.
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'wbdres_leads_per_page', 20 );
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$args     = array(
			'status'    => $status,
			'type'      => isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '',
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'orderby'   => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at',
			'order'     => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc',
			'per_page'  => $per_page,
			'page'      => $this->get_pagenum(),
			'agent_ids' => LeadService::scope(),
		);
		if ( ! empty( $_GET['agent'] ) && current_user_can( 'wbdres_manage_leads' ) ) {
			$args['agent_id'] = absint( $_GET['agent'] );
		}
		$result                = LeadRepository::query( $args );
		$this->items           = $result['items'];
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'name' );
		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			)
		);
	}

	/**
	 * Checkbox.
	 *
	 * @param object $item Lead.
	 * @return string
	 */
	protected function column_cb( $item ) {
		/* translators: %s: name. */
		return '<label class="screen-reader-text" for="lead-' . esc_attr( $item->id ) . '">' . esc_html( sprintf( __( 'Select %s', 'wbd-real-estate-suite' ), $item->name ) ) . '</label><input type="checkbox" id="lead-' . esc_attr( $item->id ) . '" name="lead[]" value="' . esc_attr( $item->id ) . '" />';
	}

	/**
	 * Name column with row actions.
	 *
	 * @param object $item Lead.
	 * @return string
	 */
	protected function column_name( $item ) {
		$view    = add_query_arg(
			array(
				'page'   => 'wbdres-leads',
				'action' => 'view',
				'lead'   => (int) $item->id,
			),
			admin_url( 'admin.php' )
		);
		$actions = array(
			'view'  => '<a href="' . esc_url( $view ) . '">' . esc_html__( 'Open', 'wbd-real-estate-suite' ) . '</a>',
			'email' => '<a href="' . esc_url( 'mailto:' . $item->email ) . '">' . esc_html__( 'Email', 'wbd-real-estate-suite' ) . '</a>',
		);
		if ( $item->phone ) {
			$actions['call'] = '<a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $item->phone ) ) . '">' . esc_html__( 'Call', 'wbd-real-estate-suite' ) . '</a>';
		}
		$new = 'new' === $item->status ? ' <span class="wbdres-dot-new" title="' . esc_attr__( 'New', 'wbd-real-estate-suite' ) . '"></span>' : '';
		return '<a class="row-title" href="' . esc_url( $view ) . '">' . esc_html( $item->name ) . '</a>' . $new . '<br /><span class="wbdres-muted">' . esc_html( $item->email ) . ( $item->phone ? ' · ' . esc_html( $item->phone ) : '' ) . '</span>' . $this->row_actions( $actions );
	}

	/**
	 * Default column.
	 *
	 * @param object $item        Lead.
	 * @param string $column_name Column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'property':
				if ( ! $item->property_id ) {
					return '<span class="wbdres-muted">—</span>';
				}
				$title = get_the_title( (int) $item->property_id );
				$link  = get_edit_post_link( (int) $item->property_id );
				return $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>' : esc_html( $title );
			case 'type':
				$types = LeadRepository::types();
				$out   = esc_html( $types[ $item->type ] ?? $item->type );
				if ( 'viewing' === $item->type && $item->preferred_date ) {
					$out .= '<br /><span class="wbdres-muted">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->preferred_date ) ) ) . '</span>';
				}
				return $out;
			case 'status':
				return LeadsPage::status_badge( $item->status );
			case 'agent':
				return $item->agent_id ? esc_html( get_the_title( (int) $item->agent_id ) ) : '<span class="wbdres-muted">' . esc_html__( 'Unassigned', 'wbd-real-estate-suite' ) . '</span>';
			case 'date':
				$ts = strtotime( $item->created_at . ' UTC' );
				/* translators: %s: human time diff. */
				return '<time datetime="' . esc_attr( gmdate( 'c', $ts ) ) . '" title="' . esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) ) . '">' . esc_html( sprintf( __( '%s ago', 'wbd-real-estate-suite' ), human_time_diff( $ts ) ) ) . '</time>';
		}
		return '';
	}

	/**
	 * Empty state.
	 */
	public function no_items() {
		esc_html_e( 'No leads found. When visitors use your inquiry, viewing or contact forms, their messages appear here.', 'wbd-real-estate-suite' );
	}
}
