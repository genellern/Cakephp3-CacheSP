<?php
namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Database\Schema\Table;
use Cake\Database\Query;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\Cache\Cache;
use Cake\Database\Expression\FunctionExpression;

class CacheSPBehavior extends Behavior {

	/**
	 * Behavior settings
	 *
	 * @access public
	 * @var array
	 */
	public $settings = [];

	/**
	 * Default values for settings.
	 *
	 * @access private
	 * @var array
	 */
	private $defaults = [];
	private $_tableObject = NULL;

	/**
	 * Initialize method
	 *
	 * @see \Cake\ORM\Behavior::initialize()
	 */
	public function initialize(array $config) {
		$this->_tableObject = $this->_table;
		$this->settings = array_merge($this->defaults, $config);
		parent::initialize($this->settings);
	}

	public function callSP($spName, $paramValues = null) {
		$values = $this->_arrangeParameters($spName, $paramValues);
		if (! empty($values)) {
			$values = "'" . join("', '", $values) . "'";
		} else {
			$values = '';
		}

		$conn = $this->_tableObject->connection();
		return $conn->execute("CALL {$spName} ($values)");
	}

	private function _getParameters($spName) {
		if (($parameters = Cache::read($spName)) === false) {
			$query = $this->_tableObject->query();
			$query = $query->select('information_schema.parameters.PARAMETER_NAME')->from('information_schema.parameters')->where([
				'SPECIFIC_NAME' => $spName
			]);
			$parameters = [];
			foreach ($query as $parameter) {
				$parameters[] = $parameter->information_schema['parameters'];
			}
			$this->_saveParameters($spName, $parameters);
		}
		return $parameters;
	}

	private function _arrangeParameters($spName, $paramValues) {
		$values = [];
		$parameters = $this->_getParameters($spName);
		foreach ($parameters as $parameter) {

			$values[] = $paramValues[$parameter];
		}
		return $values;
	}

	private function _saveParameters($spName, $parameters) {
		Cache::write($spName, $parameters);
	}
}