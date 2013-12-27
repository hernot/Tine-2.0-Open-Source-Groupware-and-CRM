<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for contracts
 *
 * @package     Sales
 * @subpackage  Backend
 */
class Sales_Backend_Contract extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sales_contracts';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sales_Model_Contract';

    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /**
     *
     * @param Tinebase_DateTime $date
     * @return array
     */
    public function getBillableContractIds(Tinebase_DateTime $date)
    {
        $dateBig = clone $date;
        $dateBig->addSecond(2);
    
        $dateSmall = clone $date;
        $dateSmall->subSecond(2);
    
        $be = new Sales_Backend_Contract();
        $db = $be->getAdapter();
    
        $sql = 'SELECT ' . $db->quoteIdentifier('id') . ' FROM ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'sales_contracts') .
        ' WHERE (' . $db->quoteInto($db->quoteIdentifier('end_date') . ' > ?', $dateSmall) . ' OR ' . $db->quoteIdentifier('end_date') . ' IS NULL ) ' .
        ' AND '   . $db->quoteInto($db->quoteIdentifier('interval') . ' > ?', 0) .
        ' AND '   . $db->quoteInto($db->quoteIdentifier('start_date') . ' < ?', $dateBig);
    
        return array_keys($db->fetchAssoc($sql));
    }
}
