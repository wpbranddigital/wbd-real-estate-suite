<?php
/**
 * Roles and capabilities.
 *
 * @package Wbdres\RealEstate
 */

namespace Wbdres\RealEstate\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Real Estate Agent role and custom capabilities.
 */
final class Roles {

	/**
	 * Primitive capabilities for a capability type.
	 *
	 * @param string $singular Singular.
	 * @param string $plural   Plural.
	 * @return array
	 */
	public static function type_caps( $singular, $plural ) {
		return array(
			"edit_{$plural}",
			"edit_others_{$plural}",
			"edit_private_{$plural}",
			"edit_published_{$plural}",
			"publish_{$plural}",
			"read_private_{$plural}",
			"delete_{$plural}",
			"delete_private_{$plural}",
			"delete_published_{$plural}",
			"delete_others_{$plural}",
		);
	}

	/**
	 * Add role and capabilities (idempotent).
	 */
	public static function add() {
		$all_caps = array_merge(
			self::type_caps( 'wbd_property', 'wbd_properties' ),
			self::type_caps( 'wbd_agent', 'wbd_agents' )
		);

		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $all_caps as $cap ) {
				$role->add_cap( $cap );
			}
			$role->add_cap( 'wbdres_manage_leads' );
			$role->add_cap( 'wbdres_view_own_leads' );
			if ( 'administrator' === $role_name ) {
				$role->add_cap( 'manage_wbdres' );
			}
		}

		$agent_caps = array(
			'read'                            => true,
			'upload_files'                    => true,
			'edit_wbd_properties'             => true,
			'edit_published_wbd_properties'   => true,
			'publish_wbd_properties'          => true,
			'delete_wbd_properties'           => true,
			'delete_published_wbd_properties' => true,
			'edit_wbd_agents'                 => true,
			'edit_published_wbd_agents'       => true,
			'wbdres_view_own_leads'           => true,
		);

		$role = get_role( 'wbdres_agent' );
		if ( ! $role ) {
			add_role( 'wbdres_agent', __( 'Real Estate Agent', 'wbd-real-estate-suite' ), $agent_caps );
		} else {
			foreach ( $agent_caps as $cap => $grant ) {
				$role->add_cap( $cap, $grant );
			}
		}
	}

	/**
	 * Remove role and capabilities (uninstall only).
	 */
	public static function remove() {
		$all_caps = array_merge(
			self::type_caps( 'wbd_property', 'wbd_properties' ),
			self::type_caps( 'wbd_agent', 'wbd_agents' ),
			array( 'wbdres_manage_leads', 'wbdres_view_own_leads', 'manage_wbdres' )
		);
		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $all_caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
		remove_role( 'wbdres_agent' );
	}
}
