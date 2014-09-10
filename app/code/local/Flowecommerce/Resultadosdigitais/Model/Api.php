<?php

class Flowecommerce_Resultadosdigitais_Model_Api
{

    const API_URL = 'http://www.rdstation.com.br/api/1.2/conversions';

    protected $_helper = null;
    protected $_httpClient = null;

    public function addLeadConversion($conversionIdentifier, Flowecommerce_Resultadosdigitais_Model_Requestdata $data)
    {
        try {
            if ($this->validateLead($conversionIdentifier, $data)) {
                $data_query = http_build_query($data);

                # Refactor para utilizar zend_http_client
                # Lembrar de parâmetros que estavam sendo setados de maneira tosquíssima
                $leadHttpClient = $this->_getHttpClient();
                $leadHttpClient
                    ->resetParameters()
                    ->setUri(self::API_URL)
                    ->setMethod(Zend_Http_Client::POST)
                    ->setParameterPost($this->_prepareParams($conversionIdentifier, $data));

                $response = $leadHttpClient->request();
                $responseBody = $response->getBody();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    protected function _getHelper() {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::helper('resultadosdigitais');
        }
        return $this->_helper;
    }

    protected function _getHttpClient() {
        if (!$this->_httpClient instanceof Varien_Http_Client) {
            $this->_httpClient = new Varien_Http_Client();
        }
        return $this->_httpClient;
    }

    protected function _prepareParams($conversionIdentifier, Flowecommerce_Resultadosdigitais_Model_Requestdata $data) {
        $return = array();

        # formatando valores da requisição
        foreach ($data->getData() as $key => $value) {
            $return[$key] = $value;
        }

        # valores adicionais, adicionaidos por último para garantir que não são sobrescritos pelo que for enviado à API
        $return['token_rdstation'] = $this->_getHelper()->getToken();
        $return['identificador'] = $conversionIdentifier;

        # Utmz
        $tracker = Mage::getModel('resultadosdigitais/googleanalytics_tracker');
        if ($utmz = $tracker->getUtmZString()) {
            $return['utmz'] = $utmz;
        }

        return $return;
    }

    public function validateLead($conversionIdentifier, Flowecommerce_Resultadosdigitais_Model_Requestdata $data) {
        $return = true;

        # Verificando existência de parâmetros obrigatórios
        $token = $this->_getHelper()->getToken();
        if (!$token || !$conversionIdentifier || !$data->getEmail()) {
            $return = false;
        }

        return $return;
    }
}