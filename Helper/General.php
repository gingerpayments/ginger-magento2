<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Config\Model\ResourceModel\Config;
use Ginger\Payments\Logger\GingerLogger;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Ginger_Payments';
    const XML_PATH_MODULE_ACTIVE = 'payment/ginger_general/enabled';
    const XML_PATH_APIKEY = 'payment/ginger_general/apikey';
    const XML_PATH_DEBUG = 'payment/ginger_general/debug';
    const XML_PATH_ACCOUNT_DETAILS = 'payment/ginger_methods_banktransfer/account_details';

    private $storeManager;
    private $resourceConfig;
    private $urlBuilder;
    private $moduleList;
    private $logger;

    /**
     * General constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Config                $resourceConfig
     * @param ModuleListInterface   $moduleList
     * @param GingerLogger          $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $resourceConfig,
        ModuleListInterface $moduleList,
        GingerLogger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->moduleList = $moduleList;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Availabiliy check, on Active, API Client & API Key
     *
     * @param $storeId
     *
     * @return bool
     */
    public function isAvailable($storeId)
    {
        $active = $this->getStoreConfig(self::XML_PATH_MODULE_ACTIVE);
        if (!$active) {
            return false;
        }

        $apiKey = $this->getApiKey($storeId);
        if (!$apiKey) {
            return false;
        }

        return true;
    }

    /**
     * Get admin value by path and storeId
     *
     * @param     $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStoreConfig($path, $storeId = 0)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns API key
     *
     * @param $storeId
     *
     * @return bool|mixed
     */
    public function getApiKey($storeId)
    {
        return $this->getStoreConfig(self::XML_PATH_APIKEY, $storeId);
    }

    /**
     * Write to log
     *
     * @param $type
     * @param $data
     */
    public function addTolog($type, $data)
    {
        $debug = $this->getStoreConfig(self::XML_PATH_DEBUG);
        if ($debug) {
            if ($type == 'error') {
                $this->logger->addErrorLog($type, $data);
            } else {
                $this->logger->addInfoLog($type, $data);
            }
        }
    }

    /***
     * @param $order
     *
     * @return mixed
     */
    public function getMethodCode($order)
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        $methodCode = str_replace('ginger_methods_', '', $method);
        return $methodCode;
    }

    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->urlBuilder->getUrl('ginger/checkout/success/');
    }

    /**
     * Webhook Url Builder
     *
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->urlBuilder->getUrl('ginger/checkout/webhook/');
    }

    /**
     * @param     $method
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusProcessing($method, $storeId = 0)
    {
        $path = 'payment/' . $method . '/order_status_processing';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * @param     $method
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusPending($method, $storeId = 0)
    {
        $path = 'payment/' . $method . '/order_status_pending';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * @param     $method
     * @param int $storeId
     *
     * @return int
     */
    public function sendInvoice($method, $storeId = 0)
    {
        $path = 'payment/' . $method . '/invoice_notify';
        return (int)$this->getStoreConfig($path, $storeId);
    }

    /**
     * @param     $method
     * @param int $storeId
     *
     * @return int
     */
    public function generateInvoice($method, $storeId = 0)
    {
        $path = 'payment/' . $method . '/generate_invoice';
        return (int)$this->getStoreConfig($path, $storeId);
    }

    /**
     * Process order transaction description
     *
     * @param $order
     * @param $method
     *
     * @return mixed
     */
    public function getDescription($order, $method)
    {
        $storeId = $order->getStoreId();
        $incrementId = $order->getIncrementId();
        $path = 'payment/' . $method . '/description';
        $description = $this->getStoreConfig($path, $storeId);
        return str_replace('%id%', $incrementId, $description);
    }

    /**
     * Get extension version for API
     *
     * @return mixed
     */
    public function getPluginVersion()
    {
        return 'Magento2-' . $this->getExtensionVersion();
    }

    /**
     * Returns current version of the extension for admin display
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }

    /**
     * Customer user agent for API
     *
     * @return mixed
     */
    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Find error in transacion
     *
     * @param $transaction
     *
     * @return bool
     */
    public function getError($transaction)
    {
        if (!empty($transaction['error'])) {
            return $transaction['error']['value'];
        }
        if ($transaction['transactions'][0]['status'] == 'error') {
            return $transaction['transactions'][0]['reason'];
        }
        return false;
    }

    /**
     * Return account details for Banktransfer method
     *
     * @return mixed
     */
    public function getAccountDetails()
    {
        return $this->getStoreConfig(self::XML_PATH_ACCOUNT_DETAILS);
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getCompanyName($storeId)
    {
        return $this->getStoreConfig('general/store_information/name', $storeId);
    }
}
