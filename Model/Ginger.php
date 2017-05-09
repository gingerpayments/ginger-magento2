<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Locale\Resolver;
use Ginger\Payments\Helper\General as GingerHelper;

class Ginger extends AbstractMethod
{

    public $gingerHelper;
    public $resolver;

    protected $_supportedCurrencyCodes = ['EUR'];
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_isOffline = false;
    protected $_canRefund = true;

    private $objectManager;
    private $checkoutSession;
    private $storeManager;
    private $scopeConfig;
    private $order;
    private $orderSender;
    private $invoiceSender;
    private $orderRepository;
    private $searchCriteriaBuilder;

    /**
     * KassaCompleet constructor.
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param ObjectManagerInterface     $objectManager
     * @param GingerHelper               $gingerHelper
     * @param Resolver                   $resolver
     * @param CheckoutSession            $checkoutSession
     * @param StoreManagerInterface      $storeManager
     * @param Order                      $order
     * @param OrderSender                $orderSender
     * @param InvoiceSender              $invoiceSender
     * @param OrderRepository            $orderRepository
     * @param SearchCriteriaBuilder      $searchCriteriaBuilder
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ObjectManagerInterface $objectManager,
        GingerHelper $gingerHelper,
        Resolver $resolver,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Order $order,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->objectManager = $objectManager;
        $this->gingerHelper = $gingerHelper;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
        $this->resolver = $resolver;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Extra checks for method availability
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {

        if ($quote == null) {
            $quote = $this->checkoutSession->getQuote();
        }

        if (!$this->gingerHelper->isAvailable($quote->getStoreId())) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $order->setIsNotified(false);

        $status = $this->gingerHelper->getStatusPending($this->_code, $order->getId());
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);
    }

    /**
     * @param $transactionId
     * @param $type
     *
     * @return mixed|string
     */
    public function processTransaction($transactionId, $type)
    {
        $msg = '';

        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('OrderId not set')];
            $this->gingerHelper->addTolog('error', $msg);
            return $msg;
        }

        $order = $this->getOrderIdByTransactionId($transactionId);
        if (!$order) {
            $msg = ['error' => true, 'msg' => __('Order not found')];
            $this->gingerHelper->addTolog('error', $msg);
            return $msg;
        }

        $method = $order->getPayment()->getMethodInstance()->getCode();
        $storeId = $order->getStoreId();

        $client = $this->loadGingerClient($storeId);
        $transaction = $client->getOrder($transactionId)->toArray();

        if (empty($transaction)) {
            $msg = ['error' => true, 'msg' => __('Transaction not found')];
            $this->gingerHelper->addTolog('error', $msg);
            return $msg;
        }

        if (array_key_exists('error', $transaction)) {
            $msg = ['error' => true, 'msg' => __('Transaction Error')];
            $this->gingerHelper->addTolog('error', $msg);
            return $msg;
        }

        $paymentStatus = isset($transaction['status']) ? $transaction['status'] : null;

        switch ($paymentStatus) {
            case "processing":
                if (!$order->getEmailSent()) {
                    $payment = $order->getPayment();

                    $mailingAddress = $payment->getMethodInstance()->getMailingAddress();
                    $grandTotal = number_format(($transaction['transactions'][0]['amount'] / 100), 2, '.', '');
                    $grandTotal = $order->getBaseCurrencyCode() . ' ' . $grandTotal;
                    $reference = $transaction['transactions'][0]['payment_method_details']['reference'];
                    $mailingAddress = str_replace('%AMOUNT%', $grandTotal, $mailingAddress);
                    $mailingAddress = str_replace('%REFERENCE%', $reference, $mailingAddress);
                    $mailingAddress = str_replace('\n', PHP_EOL, $mailingAddress);

                    $payment->setAdditionalInformation(
                        'mailing_address',
                        $mailingAddress
                    );

                    $payment->setTransactionId($transactionId);
                    $payment->setCurrencyCode($order->getBaseCurrencyCode());
                    $payment->setIsTransactionClosed(false);
                    $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);
                    $order->save();

                    $status = $this->gingerHelper->getStatusPending($method, $storeId);
                    $this->orderSender->send($order);
                    $message = __('New order email sent');
                    $order->addStatusToHistory($status, $message, true)->setEmailSent(true)->save();
                }

                $msg = [
                    'success'  => true,
                    'status'   => $paymentStatus,
                    'order_id' => $order->getEntityId(),
                    'type'     => $type
                ];

                break;

            case "cancelled":
                $this->cancelOrder($order);
                $msg = [
                    'success'  => false,
                    'status'   => $paymentStatus,
                    'order_id' => $order->getEntityId(),
                    'type'     => $type

                ];

                break;

            case "completed":
                $amount = ($transaction['transactions'][0]['amount'] / 100);
                $payment = $order->getPayment();
                if (!$payment->getIsTransactionClosed()) {
                    $payment->setTransactionId($transactionId);
                    $payment->setCurrencyCode($order->getBaseCurrencyCode());
                    $payment->setIsTransactionClosed(true);
                    $payment->registerCaptureNotification($amount, true);
                    if ($method == 'ginger_methods_cod') {
                        $mailingAddress = $payment->getMethodInstance()->getMailingAddress();
                        $grandTotal = number_format(($transaction['transactions'][0]['amount'] / 100), 2, '.', '');
                        $grandTotal = $order->getBaseCurrencyCode() . ' ' . $grandTotal;
                        $mailingAddress = str_replace('%AMOUNT%', $grandTotal, $mailingAddress);
                        $payment->setAdditionalInformation(
                            'mailing_address',
                            $mailingAddress
                        );
                    }
                    $order->save();

                    $invoice = $payment->getCreatedInvoice();
                    $status = $this->gingerHelper->getStatusProcessing($method, $storeId);
                    $sendInvoice = $this->gingerHelper->sendInvoice($method, $storeId);
                    if ($invoice && !$order->getEmailSent()) {
                        $this->orderSender->send($order);
                        $message = __('New order email sent');
                        $order->addStatusToHistory($status, $message, true)->setEmailSent(true)->save();
                    }
                    if ($invoice && !$invoice->getEmailSent() && $sendInvoice) {
                        $this->invoiceSender->send($invoice);
                        $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
                        $order->addStatusToHistory($status, $message, true)->save();
                    }
                }
                $msg = [
                    'success'  => true,
                    'status'   => $paymentStatus,
                    'order_id' => $order->getEntityId(),
                    'type'     => $type
                ];

                break;
        }

        $this->gingerHelper->addTolog('success', $msg);
        return $msg;
    }

    /**
     * Get order by TransactionId
     *
     * @param $transactionId
     *
     * @return mixed
     */
    public function getOrderIdByTransactionId($transactionId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('ginger_transaction_id', $transactionId, 'eq')
            ->create();
        $orderList = $this->orderRepository->getList($searchCriteria);
        $order = $orderList->getFirstItem();

        if ($order) {
            return $order;
        } else {
            $this->gingerHelper->addTolog('error', __('No order found for transaction id %1', $transactionId));

            return false;
        }
    }

    /**
     * @param $storeId
     *
     * @return bool|mixed
     */
    public function loadGingerClient($storeId)
    {
        $apiKey = $this->gingerHelper->getApiKey($storeId);
        if (!$apiKey) {
            return false;
        }

        try {
            $client = $this->objectManager->create('GingerPayments\Payment\Ginger');
            $client = $client->createClient($apiKey);
        } catch (\Exception $e) {
            $this->gingerHelper->addTolog('error', 'Function: loadGingerClient: ' . $e->getMessage());
            return false;
        }

        return $client;
    }

    /**
     * Cancel order
     *
     * @param $order
     *
     * @return bool
     */
    public function cancelOrder($order)
    {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment = __("The order was canceled");
            $this->gingerHelper->addTolog('info', $order->getIncrementId() . ' ' . $comment);
            $order->registerCancellation($comment)->save();

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getIssuers()
    {
        $storeId = $this->storeManager->getStore()->getId();

        try {
            $client = $this->loadGingerClient($storeId);
            $issuers = $client->getIdealIssuers()->toArray();
        } catch (\Exception $e) {
            $this->gingerHelper->addTolog('error', 'Function: getIssuers: ' . $e->getMessage());
            return false;
        }

        return $issuers;
    }

    /**
     * @param $order
     *
     * @return array
     */
    public function getCustomerData($order)
    {
        $customer = $order->getBillingAddress();
        return [
            'merchant_customer_id' => $order->getCustomerId(),
            'email_address'        => $customer->getEmail(),
            'first_name'           => $customer->getFirstname(),
            'last_name'            => $customer->getLastname(),
            'address_type'         => $customer->getAddressType(),
            'address'              => implode(' ', $customer->getStreet()),
            'postal_code'          => $customer->getPostcode(),
            'country'              => $customer->getCountryId(),
            'phone_numbers'        => [$customer->getTelephone()],
            'user_agent'           => $this->gingerHelper->getUserAgent(),
            'ip_address'           => $order->getRemoteIp(),
            'forwarded_ip'         => $order->getXForwardedFor(),
            'locale'               => $this->resolver->getLocale()
        ];
    }
}
