<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Model\Methods;

use Ginger\Payments\Model\Ginger;

class Bancontact extends Ginger
{

    protected $_code = 'ginger_methods_bancontact';

    /**
     * @param $order
     *
     * @return array|mixed
     */
    public function startTransaction($order)
    {
        $orderId = $order->getId();
        $storeId = $order->getStoreId();

        $client = $this->loadGingerClient($storeId);
        $transaction = $client->createBancontactOrder(
            ($order->getBaseGrandTotal() * 100),
            $order->getOrderCurrencyCode(),
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
}
