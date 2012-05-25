<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * employee filter Class
 * @package     HumanResources
 */
class HumanResources_Model_FreeTimeFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'HumanResources_Model_FreeTimeFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'HumanResources';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'HumanResources_Model_FreeTime';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'         => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('n_given', 'n_family', 'title'))),
        'employee_id'   => array('filter' => 'Tinebase_Model_Filter_ForeignId',
            'options' => array(
                'filtergroup'       => 'HumanResources_Model_EmployeeFilter', 
                'controller'        => 'HumanResources_Controller_Employee', 
            )
            ),
        'created_by'    => array('filter' => 'Tinebase_Model_Filter_User')
    );
}
