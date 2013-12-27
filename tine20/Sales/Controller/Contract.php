<?php
/**
 * contract controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * contract controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Contract extends Sales_Controller_NumberableAbstract
{
    /**
     * the number gets prefixed zeros until this amount of chars is reached
     *
     * @var integer
     */
    protected $_numberZerofill = 6;
    
    /**
     * the prefix for the invoice
     *
     * @var string
     */
    protected $_numberPrefix = 'V-';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Contract();
        $this->_modelName = 'Sales_Model_Contract';
    }    
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Contract
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Contract
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /****************************** overwritten functions ************************/

    /**
     * get by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet
     */
    public function get($_id)
    {
        $sharedContracts = $this->getSharedContractsContainer();
        return parent::get($_id, $sharedContracts->getId());
    }
    

    /**
     * @see Tinebase_Controller_Record_Abstract::update()
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE)
     {
        if ($_duplicateCheck) {
            $this->_checkNumberUniquity($_record, true);
        }
        $this->_checkNumberType($_record);
        return parent::update($_record, $_duplicateCheck);
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Sales_Model_Contract
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        // add container
        $_record->container_id = self::getSharedContractsContainer()->getId();

        if (Sales_Config::getInstance()->get(Sales_Config::CONTRACT_NUMBER_GENERATION, 'auto') == 'auto') {
            // add number if configured auto
            $this->_addNextNumber($_record);
        } else {
            // check uniquity if not autogenerated
            $this->_checkNumberUniquity($_record, false);
        }
        // check type
        $this->_checkNumberType($_record);
        
        return parent::create($_record);
    }

    /**
     * Checks if number is unique if manual generated
     *
     * @param Tinebase_Record_Interface $r
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _checkNumberType($record)
    {
        $number = $record->number;
    
        if (empty($number)) {
            throw new Tinebase_Exception_Record_Validation('Please use a contract number!');
        } elseif ((Sales_Config::getInstance()->get('contractNumberValidation', 'integer') == 'integer') && (! is_numeric($number))) {
            throw new Tinebase_Exception_Record_Validation('Please use a decimal number as contract number!');
        }
    }
    
    /**
     * get (create if it does not exist) container for shared contracts
     * 
     * @return Tinebase_Model_Container|NULL
     * 
     * @todo use Tinebase_Container::createSystemContainer()
     */
    public static function getSharedContractsContainer()
    {
        $sharedContracts = NULL;
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
        
        try {
            $sharedContractsId = Sales_Config::getInstance()->get(Sales_Model_Config::SHAREDCONTRACTSID);
            $sharedContracts = Tinebase_Container::getInstance()->get($sharedContractsId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $newContainer = new Tinebase_Model_Container(array(
                'name'              => 'Shared Contracts',
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'backend'           => 'Sql',
                'application_id'    => $appId,
                'model'             => 'Sales_Model_Contract'
            ));
            $sharedContracts = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, TRUE);
            
            Sales_Config::getInstance()->set(Sales_Model_Config::SHAREDCONTRACTSID, $sharedContracts->getId());
            
            // add grants for groups
            $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
            $adminGroup = $groupsBackend->getDefaultAdminGroup();
            $userGroup  = $groupsBackend->getDefaultGroup();
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_EDIT
            ), TRUE);
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
                Tinebase_Model_Grants::GRANT_ADD,
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_EDIT,
                Tinebase_Model_Grants::GRANT_DELETE,
                Tinebase_Model_Grants::GRANT_ADMIN
            ), TRUE);
        }
        
        return $sharedContracts;
    }
    
    /**
     * sets the last billed date to the next date by interval and returns the updated contract
     * 
     * @param Sales_Model_Contract $contract
     * @return Sales_Model_Contract 
     */
    public function updateLastBilledDate(Sales_Model_Contract $contract)
    {
        // update last billed information -> set last_autobill to the date the invoice should have
        // been created and not to the current date, so we can calculate the interval properly
        $lastBilled = $contract->last_autobill ? clone $contract->last_autobill : NULL;
        
        if ($lastBilled === NULL) {
            // begin / end
            if ($contract->billing_point == 'begin') {
                // set billing date to start date
                $contract->last_autobill = clone $contract->start_date;
            } else {
                $contract->last_autobill = clone $contract->start_date;
                $contract->last_autobill->addMonth($contract->interval);
            }
        } else {
            $contract->last_autobill->addMonth($contract->interval);
        }
        
        $this->update($contract);
    }
    
    /**
     * returns all billable contracts for the specified date. If a invoice has 
     * been created for the interval already, the contract will not be returned.
     * relations are returned
     * 
     * @param Tinebase_DateTime $date
     * @return Tinebase_Record_RecordSet
     */
    public function getBillableContracts(Tinebase_DateTime $date)
    {
        $dateBig = clone $date;
        $dateBig->addSecond(2);
        
        $dateSmall = clone $date;
        $dateSmall->subSecond(2);
        
        $ids = $this->_backend->getBillableContractIds($date);
        
        $filter = new Sales_Model_ContractFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $ids),
        ), 'AND');

        $contracts = $this->search($filter, NULL, /* get relations = */ TRUE);
        
        foreach($contracts as $contract) {
            if ($contract->end_date && $contract->last_autobill > $contract->end_date) {
                $contracts->removeRecord($contract);
                continue;
            }
            
            $lastBilled = $contract->last_autobill === NULL ? NULL : clone $contract->last_autobill;
        
            if ($lastBilled) {
                $nextBill = $lastBilled->addMonth($contract->interval);
            } else {
                $nextBill = clone $contract->start_date;
                
                if ($contract->billing_point == 'end') {
                    $nextBill->addMonth($contract->interval);
                }
            }
            
            if (($contract->end_date !== NULL) && $nextBill->isLater($contract->end_date)) {
                $nextBill = clone $contract->end_date;
            }
            
            $nextBill->setTime(0,0,0);
            
            if ($nextBill->isLater($dateBig)) {
                $contracts->removeRecord($contract);
            }
        }
        
        return $contracts;
    }
}
