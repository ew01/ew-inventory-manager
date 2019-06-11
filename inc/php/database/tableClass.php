<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 3/1/2019
 * Time: 09:12
 * Name:
 * Desc:
 */





class ewim_tables{
	public function __construct() {
		global $wpdb;

		$this->ewim_games= $wpdb->prefix . 'ewim_games';
		$this->ewim_items= $wpdb->prefix . 'ewim_items';
		$this->ewim_ledger= $wpdb->prefix . 'ewim_ledger';
		$this->ewim_meta_data= $wpdb->prefix . 'ewim_meta_data';
		$this->ewim_posted= $wpdb->prefix . 'ewim_posted';
	}
}