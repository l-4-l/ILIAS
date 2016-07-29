<?php
namespace CaT\TableRelations\Tables;

use CaT\Filter as Filters;

class DerivedField extends Filters\Predicates\Field implements AbstractDerivedField{

	protected $derived_from = array();
	public function __construct(Filters\PredicateFactory $f, $name, \Closure $postprocess, $fields = array()) {
		$this->derived_from = $fields;
		$this->postprocess = $postprocess;
		parent::__construct($f, $name);
	}

	/**
	 * Get all fields from which this field is derived.
	 *
	 * @return	AbstractTableField[]
	 */
	public function derivedFrom() {
		return $this->derived_from;
	}

	/**
	 * Get the postprocessing-function to be used by interpreter.
	 *
	 * @return	closure 
	 */
	public function postprocess() {
		return $this->postprocess;
	}

	public function name_simple() {
		return $this->name();
	}
}