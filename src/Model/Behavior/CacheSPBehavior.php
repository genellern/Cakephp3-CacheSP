<?php
/**
 *
 * @author Geneller Naranjo This behavior caches parameters so that order and sql string are no longer at use and
 * provides an easier way to call SPs.
 */
namespace CacheSP\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Database\Schema\Table;
use Cake\Database\Query;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\Cache\Cache;
use Cake\Database\Expression\FunctionExpression;

class CacheSPBehavior extends Behavior
{
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

    private $tableObject = null;

    /**
     * Initialize method
     *
     * @see \Cake\ORM\Behavior::initialize()
     */
    public function initialize(array $config)
    {
        $this->tableObject = $this->_table;
        $conn = $this->tableObject->connection();
        $this->settings = array_merge($this->defaults, $config);
        parent::initialize($this->settings);
    }

    /**
     * Call the procedure with its parameters.
     *
     * @param String $spName SP name
     * @param Array|null $paramValues Array with values to the SP. the array index is the parameter name in the
     * description of the SP the value is the value to be passed.
     * @return \Cake\Database\StatementInterface executed statement
     */
    public function callSP($spName, $paramValues = null)
    {
        $values = $this->arrangeParameters($spName, $paramValues);
        $values = join(", ", $values);
        $conn = $this->tableObject->connection();
        return $conn->execute("CALL {$spName} ($values)");
    }

    /**
     * Get parameters from cache, if they're not cached already, then it creates parameters in cache.
     *
     * @param Strng $spName
     * @return Array $parameters
     */
    private function getParameters($spName)
    {
        if (($parameters = Cache::read($spName)) === false) {
            $query = $this->tableObject->query();
            $conn = $this->tableObject->connection();
            $config = $conn->config();
            $query = $query->select('information_schema.parameters.PARAMETER_NAME')
                ->from('information_schema.parameters')
                ->where(
                    ['SPECIFIC_NAME' => $spName, 'SPECIFIC_SCHEMA' => $config['database']]
                );
            $parameters = [];
            foreach ($query as $parameter) {
                $parameters[] = $parameter->information_schema['parameters'];
            }
            $this->saveParameters($spName, $parameters);
        }
        return $parameters;
    }

    /**
     * Arrange parameters in the order described in the SQL definition of the procedure.
     *
     * @param String $spName
     * @param Array $paramValues
     * @return Array $values
     */
    private function arrangeParameters($spName, $paramValues)
    {
        $values = [];
        $parameters = $this->getParameters($spName);
        foreach ($parameters as $parameter) {
            if ($paramValues[$parameter] instanceof FunctionExpression) {
                $values[] = ($paramValues[$parameter])->sql(new \Cake\Database\ValueBinder);
            } else {
                $values[] = "'" . $paramValues[$parameter] . "'";
            }
        }
        return $values;
    }

    /**
     * Save parameters to cache, key is the SP name.
     *
     * @param String $spName
     * @param Array $parameters
     */
    private function saveParameters($spName, $parameters)
    {
        Cache::write($spName, $parameters);
    }
}
