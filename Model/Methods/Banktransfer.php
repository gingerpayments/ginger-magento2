<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Model\Methods;

use Ginger\Payments\Model\Ginger;

class Banktransfer extends Ginger
{

    protected $_code = 'ginger_methods_banktransfer';
    protected $_infoBlockType = 'Ginger\Payments\Block\Info\Banktransfer';

    /**
     * @param $order
     *
     * @return array|mixed
     */
    public function startTransaction($order)
    {
        $orderId = $order->getId();
        $storeId = $order->getStoreId();
        $customer = $this->getCustomerData($order);

        $client = $this->loadGingerClient($storeId);
        $transaction = $client->createSepaOrder(
            ($order->getBaseGrandTotal() * 100),
            $order->getOrderCurrencyCode(),
            [],
            $this->gingerHelper->getDescription($order, $this->_code),
            $orderId,
            $this->gingerHelper->getReturnUrl(),
            null,
            $customer,
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
     * @return string
     */
    public function getMailingAddress()
    {
        $paymentBlock = '';
        if ($accountDetails = $this->gingerHelper->getAccountDetails()) {
            $paymentBlock .= __('Amount:') . ' ' . '%AMOUNT%' . "\n";
            $paymentBlock .= __('Reference:') . ' ' . '%REFERENCE%' . "\n";
            $paymentBlock .= __('IBAN:') . ' ' . $accountDetails['iban'] . "\n";
            $paymentBlock .= __('BIC:') . ' ' . $accountDetails['bic'] . "\n";
            $paymentBlock .= __('Account holder:') . ' ' . $accountDetails['holder'] . "\n";
            $paymentBlock .= __('City:') . ' ' . $accountDetails['city'];
        }
        return $paymentBlock;
    }
}
