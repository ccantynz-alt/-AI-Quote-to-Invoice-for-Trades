<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Loader {

	private $actions = array();
	private $filters = array();

	public function add_action( $hook, $component, $callback, $priority = 10, $args = 1 ) {
		$this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'args' );
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $args = 1 ) {
		$this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'args' );
	}

	public function run() {
		foreach ( $this->filters as $f ) {
			add_filter( $f['hook'], array( $f['component'], $f['callback'] ), $f['priority'], $f['args'] );
		}
		foreach ( $this->actions as $a ) {
			add_action( $a['hook'], array( $a['component'], $a['callback'] ), $a['priority'], $a['args'] );
		}
	}
}
