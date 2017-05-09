<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Model\Methods;

use Ginger\Payments\Model\Ginger;

class Ideal extends Ginger
{

    protected $_code = 'ginger_methods_ideal';

    /**
     * @param $order
     *
     * @return array|mixed
     */
    public function startTransaction($order)
    {
        $issuer = null;
        $orderId = $order->getId();
        $storeId = $order->getStoreId();

        $additionalData = $order->getPayment()->getAdditionalInformation();
        if (isset($additionalData['issuer'])) {
            $issuer = $additionalData['issuer'];
        }

        $client = $this->loadGingerClient($storeId);
        $transaction = $client->createIdealOrder(
            ($order->getBaseGrandTotal() * 100),
            $order->getOrderCurrencyCode(),
            $issuer,
            $this->gingerHelper->getDescription($order, $this->_code),
            $orderId,
            $this->gingerHelper->getReturnUrl(),
            null,
            null,
            ['plugin' => $this->gingerHelper->getPluginVersion()],
            $this->gingerHelper->getWebhookUrl()
        )->toArray();

        $this->gingerHelper->addTolog('transaction', $transaction);

        if ($transaction && !$this->gingerHelper->getError($transaction)) {
            $message = __('Ginger Order ID: %1', $transaction['id']);
            $status = $this->gingerHelper->getStatusPending($this->_code, $storeId);
            $order->addStatusToHistory($status, $message, false);
            $order->setGingerTransactionId($transaction['id']);
            $order->save();
        }

        return $transaction;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     *
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('issuer', $data['selected_issuer']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['selected_issuer'])) {
                $selectedIssuer = $additional_data['selected_issuer'];
                $this->getInfoInstance()->setAdditionalInformation('issuer', $selectedIssuer);
            }
        }
        return $this;
    }
}
