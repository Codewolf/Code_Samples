<?php

namespace LicencingController;

use Licencing\ControllerBase;
use Licencing\GlobalFunction;

/**
 * Class License
 *
 * This class handles the Licensing Page.
 *
 * @package LicencingController
 */
class License extends ControllerBase
{

    private $_action;

    /**
     * @var \Licencing\core\DBPDO Database Resource.
     */
    private $_db;

    /**
     * License Page constructor.
     */
    public function __construct()
    {
        $this->_db                    = $GLOBALS['db'];
        $this->_action                = ($_GET[0] ?? 'Generate');
        $this->options['active_page'] = 'License/' . $this->_action;
        $this->options['page_action'] = strtolower($this->_action);
        $this->_processAction();
    }

    /**
     * Process the page action.
     *
     * @return void
     */
    private function _processAction()
    {
        switch ($this->options['page_action']) {
            case "history":
                $this->_fetchClientList();
                break;

            case "generate":
            default:
                $this->_fetchClientList();
                $this->_createGenerationPage();
                break;
        }
    }

    /**
     * Populate the twig options for the Generation page.
     *
     * @return void
     */
    private function _createGenerationPage()
    {
        $this->options['modules'] = $this->_db->fetchAllQuery("SELECT * FROM modules_available WHERE enabled_by_default=0");
    }

    /**
     * Fetch the list of clients and their managers.
     *
     * @return void
     */
    private function _fetchClientList()
    {
        $this->options['clients'] = $this->_db->fetchAllQuery(
            "SELECT
                      cl.id,
                      cl.client_id,
                      convert_from(decrypt(cl.client_name, '" . KEY . "', 'aes'), 'SQL_ASCII') AS client_name,
                      concat_ws(
                          ' ',
                          convert_from(decrypt(mb.firstname, '" . KEY . "', 'aes'), 'SQL_ASCII'),
                          convert_from(decrypt(mb.lastname, '" . KEY . "', 'aes'), 'SQL_ASCII')
                      )                                                                                                 AS managed_by
                    FROM clients cl
                      LEFT JOIN users mb ON cl.managed_by = mb.id"
        );
    }
}