<?php
/**
 * @author Geneller Naranjo
 * This behavior caches parameters so that order and sql string are no longer at use and provides an easier way to call SPs.
 */
namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Database\Schema\Table;
use Cake\Database\Query;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Cache\Cache;

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

	/**
	 * Call the procedure with its parameters.
	 * @param String $spName SP name
	 * @param Array|null $paramValues Array with values to the SP.
	 * 	the array index is the parameter name in the description of the SP
	 *  the value is the value to be passed.
	 * @return \Cake\Database\StatementInterface executed statement
	 */
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

	/**
	 * Get parameters from cache, if they're not cached already, then it creates parameters in cache.
	 * @param Strng $spName
	 * @return Array $parameters
	 */
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

	/**
	 * Arrange parameters in the order described in the SQL definition of the procedure.
	 * @param String $spName
	 * @param Array $paramValues
	 * @return Array $values
	 */
	private function _arrangeParameters($spName, $paramValues) {
		$values = [];
		$parameters = $this->_getParameters($spName);
		foreach ($parameters as $parameter) {

			$values[] = $paramValues[$parameter];
		}
		return $values;
	}

	/**
	 * Save parameters to cache, key is the SP name.
	 * @param String $spName
	 * @param Array $parameters
	 */
	private function _saveParameters($spName, $parameters) {
		Cache::write($spName, $parameters);
	}
}
