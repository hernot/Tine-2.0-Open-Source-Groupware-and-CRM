<?php
/**
 * class to hold contract data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contract data
 *
 * @package     Sales
 */
class Sales_Model_Contract extends Tinebase_Record_Abstract
{
    /**
     * relation type: customer
     *
     */
    const RELATION_TYPE_CUSTOMER = 'CUSTOMER';
    
    /**
     * relation type: responsible
     *
     */
    const RELATION_TYPE_RESPONSIBLE = 'RESPONSIBLE';
    
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Contract',
        'recordsName'       => 'Contracts', // ngettext('Contract', 'Contracts', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        
        'containerProperty' => 'container_id',

        'containerName'    => 'Contracts',
        'containersName'    => 'Contracts',
        'containerUsesFilter' => FALSE,
        
        'titleProperty'     => 'title',
        'appName'           => 'Sales',
        'modelName'         => 'Contract',
        'filterModel' => array(
//             'costcenter'           => array(
//                 'filter' => 'Sales_Model_ContractCostCenterFilter',
//                 'jsConfig' => array('filtertype' => 'sales.contractcostcenter'),
//                 'label' => 'CostCenter', // _('CostCenter')
//             ),
//             'customer'          => array(
//                 'filter' => 'Sales_Model_ContractCustomerFilter',
//                 'jsConfig' => array('filtertype' => 'sales.contractcustomer'),
//                 'label' => 'Customer', // _('Customer')
//             ),
            'customer'          => array(
                'filter' => 'Sales_Model_ContractCustomerFilter',
                'label' => 'Customer', // _('Customer')
                'options' => array(
                    'related_model'     => 'Sales_Model_Customer',
                    'filtergroup'       => 'Sales_Model_CustomerFilter'
                ),
                'jsConfig' => array('filtertype' => 'sales.contractcustomer')
            ),
        ),
        
        'filterModel' => array(
            'costcenter' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Cost Center', // _('Cost Center')
                'options' => array(
                    'controller' => 'Sales_Controller_CostCenter',
                    'filtergroup' => 'Sales_Model_CostCenterFilter',
                    'own_filtergroup' => 'Sales_Model_ContractFilter',
                    'own_controller' => 'Sales_Controller_Contract',
                    'related_model' => 'Sales_Model_CostCenter',
                ),
                'jsConfig' => array('filtertype' => 'sales.contractcostcenter')
            ),
        ),
        
        'fields'            => array(
            'number' => array(
                'label' => 'Number', //_('Number')
                'type'  => 'string',
                'queryFilter' => TRUE,
            ),
            'title' => array(
                'label'   => 'Title', // _('Title')
                'type'    => 'string',
                'queryFilter' => TRUE,
            ),
            'description' => array(
                'label'   => 'Description', // _('Description')
                'type'    => 'text',
                'queryFilter' => TRUE,
            ),
            'parent_id'       => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Contract',
                    'idProperty'  => 'id',
                )
            ),
            'last_autobill' => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'datetime',
            ),
            'billing_address_id' => array(
                'label'      => 'Billing Address', // _('Billing Address')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Address',
                    'idProperty'  => 'id',
                )
            ),
            'billing_point' => array(
                'label' => 'Billing Point', // _('Billing Point')
                'type'  => 'string',
                'default' => 'end',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE)
            ),
            'interval' => array(
                'label'      => 'Billing Interval', // _('Billing Interval')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'integer',
                'default'    => 1
            ),
            'start_date' => array(
                'type' => 'date',
                'label'      => 'Start Date',    // _('Start Date')
            ),
            'end_date' => array(
                'type' => 'date',
                'label'      => 'End Date',    // _('End Date')
            ),
        )
    );
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        // a contract may have one responsible and one customer but many partners
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'RESPONSIBLE', 'degree' => 'sibling', 'text' => 'Responsible', 'max' => '1:0'), // _('Responsible')
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '1:0'),  // _('Customer')
            array('type' => 'PARTNER', 'degree' => 'sibling', 'text' => 'Partner', 'max' => '0:0'),  // _('Partner')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Tasks', 'relatedModel' => 'Task', 'config' => array(
            array('type' => 'TASK', 'degree' => 'sibling', 'text' => 'Task', 'max' => '0:0'),
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Product', 'config' => array(
            array('type' => 'PRODUCT', 'degree' => 'sibling', 'text' => 'Product', 'max' => '0:0'),
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'CostCenter', 'config' => array(
            array('type' => 'LEAD_COST_CENTER', 'degree' => 'sibling', 'text' => 'Lead Cost Center', 'max' => '1:0'), // _('Lead Cost Center')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Timetracker', 'relatedModel' => 'Timeaccount', 'config' => array(
            array('type' => 'TIME_ACCOUNT', 'degree' => 'sibling', 'text' => 'Time Account', 'max' => '0:1'), // _('Time Account')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Customer', 'config' => array(
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '1:0'), // _('Customer')
            ), 'defaultType' => ''
        ),
    );
}
