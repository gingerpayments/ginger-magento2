<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Model;

use Ginger\Payments\Model\Ginger as GingerModel;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class GingerConfigProvider implements ConfigProviderInterface
{

    private $methodCodes = [
        'ginger_methods_bancontact',
        'ginger_methods_banktransfer',
        'ginger_methods_creditcard',
        'ginger_methods_ideal',
        'ginger_methods_sofort'
    ];

    private $methods = [];
    private $escaper;
    private $assetRepository;
    private $scopeConfig;
    private $storeManager;
    private $gingerModel;

    /**
     * GingerConfigProvider constructor.
     *
     * @param Ginger                $gingerModel
     * @param PaymentHelper         $paymentHelper
     * @param AssetRepository       $assetRepository
     * @param ScopeConfigInterface  $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Escaper               $escaper
     */
    public function __construct(
        GingerModel $gingerModel,
        PaymentHelper $paymentHelper,
        AssetRepository $assetRepository,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Escaper $escaper
    ) {
        $this->gingerModel = $gingerModel;
        $this->escaper = $escaper;
        $this->assetRepository = $assetRepository;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * Config Data for checkout
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                if ($code == 'ginger_methods_ideal') {
                    $config['payment']['issuers'] = $this->getIssuers($code);
                }
                if ($code == 'ginger_methods_banktransfer') {
                    $config['payment']['mailingAddress'][$code] = $this->getMailingAddress($code);
                }
            }
        }

        return $config;
    }

    /**
     * Instruction data
     *
     * @param $code
     *
     * @return string
     */
    public function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    /**
     * @return bool
     */
    public function getIssuers()
    {
        if ($issuers = $this->gingerModel->getIssuers()) {
            return $issuers;
        }
        return false;
    }

    /**
     * @param $code
     *
     * @return string
     */
    public function getMailingAddress($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getMailingAddress()));
    }

    /**
     * Get Store Config Value
     *
     * @param $path
     *
     * @return mixed
     */
    public function getStoreConfig($path)
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }
}
