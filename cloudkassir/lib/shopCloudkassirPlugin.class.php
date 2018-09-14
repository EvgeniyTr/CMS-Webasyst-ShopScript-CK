<?php

class shopCloudkassirPlugin extends shopPlugin
{
    const RECEIPT_TYPE_INCOME = 'Income';
    const RECEIPT_TYPE_INCOME_RETURN = 'IncomeReturn';

    const LOG_FILE = 'cloudkassir.log';

    const API_URL = 'https://api.cloudpayments.ru/';

    /**
     * Возвращает список возможных действий для заказа
     *
     * @return array
     */
    public static function settingAvailableOrderActions()
    {
        $workflow = new shopWorkflow();
        $actions = $workflow->getAvailableActions();

        $data = [];
        foreach ($actions as $key => $action) {
            if (isset($action['state'])) {
                $data[] = [
                    'value' => $key,
                    'title' => $action['name']
                ];
            }
        }

        return $data;
    }

    /**
     * Возвращает список возможных способов оплаты
     *
     * @return array
     */
    public static function settingAvailablePayments()
    {
        $model = new shopPluginModel();

        $data = [];
        foreach ($model->listPlugins(shopPluginModel::TYPE_PAYMENT) as $plugin) {
            $data[] = [
                'value' => $plugin['id'],
                'title' => $plugin['name']
            ];
        }

        return $data;
    }

    /**
     * Обработка статусов заказов
     *
     * @param $params
     * @return bool
     */
    public function processOrderStatus($params)
    {
        $action_id = ifset($params['action_id']);

        $receipt_type = $this->getReceiptType($action_id);
        if (!$receipt_type) {
            return false;
        }

        $order = $this->getOrder(ifset($params['order_id']));
        if (!$order) {
            return false;
        }

        if (!$this->isAllowReceiptType($order, $receipt_type)) {
            return false;
        }

        if (!$this->isAllowPayment($order)) {
            return false;
        }

        try {
            $response = $this->requestOrderReceipt($order, $receipt_type);
            if ($response) {
                $this->storeReceiptMark($order, $receipt_type);
                $this->logHistory($order, $receipt_type);
            }
        } catch (Exception $e) {
            waLog::log('Failed process order ' . $params['order_id'] . ': ' . $e->getMessage(), self::LOG_FILE);
            return false;
        }

        return true;
    }

    /**
     * @param shopOrder $order
     * @param $type
     * @return array|bool
     * @throws Exception
     */
    private function requestOrderReceipt($order, $type)
    {
        $response = $this->makeRequest('kkt/receipt', [
            'Inn' => $this->getSettings('inn'),
            'Type' => $type,
            'CustomerReceipt' => $this->getReceiptData($order),
            'InvoiceId' => shopHelper::encodeOrderId($order->getId()),
            'AccountId' => $order->contact->get('phone', 'default'),
        ]);

        return $response;
    }

    /**
     * @param shopOrder $order
     * @return array
     */
    private function getReceiptData($order)
    {
        $receipt_data = array(
            'Items' => array(),
            'taxationSystem' => $this->getSettings('taxation_system'),
            'email' => $order->contact->get('email', 'default'),
            'phone' => preg_replace('/\D/', '', $order->contact->get('phone', 'default'))
        );

        $default_vat = $this->getSettings('vat');
        foreach ($order->items as $item) {
            $item_price = floatval($item['price']);
            $item_vat = $default_vat;
            if (!is_null($item['tax_percent'])) {
                if (!intval($item['tax_included'])) {
                    $item_price *= (floatval($item['tax_percent']) + 100) / 100;
                }
                $item_vat = sprintf('%d', $item['tax_percent']);
                if (!in_array($item_vat, ['0', '10', '18'])) {
                    $item_vat = $default_vat;
                }
            }
            $receipt_data['Items'][] = [
                'label' => $item['name'],
                'price' => $item_price,
                'quantity' => floatval($item['quantity']),
                'amount' => $item_price * floatval($item['quantity']),
                'vat' => $item_vat
            ];
        }

        $shipping_cost = floatval($order->shipping);
        if ($shipping_cost) {
            $receipt_data['Items'][] = [
                'label' => $order->shipping_name,
                'price' => $shipping_cost,
                'quantity' => 1,
                'amount' => $order->shipping,
                'vat' => $this->getSettings('vat_delivery')
            ];
        }

        return $receipt_data;
    }

    /**
     * @param string $location
     * @param array $request
     * @return array
     * @throws Exception
     */
    private function makeRequest($location, $request = array())
    {
        $net = new waNet([
            'format' => waNet::FORMAT_JSON,
            'authorization' => true,
            'login' => $this->getSettings('public_id'),
            'password' => $this->getSettings('secret_key'),
        ]);

        try {
            $response = $net->query(self::API_URL . $location, $request, waNet::METHOD_POST);
        } catch (waException $e) {
            throw new waException($e->getMessage() . ', Response: ' . $net->getResponse(true), $e->getCode());
        }

        if (!ifset($response['Success'])) {
            throw new Exception('Failed request ' . print_r($response, true));
        }

        return $response;
    }

    /**
     * @param $order_id
     * @return shopOrder|false
     */
    private function getOrder($order_id)
    {
        try {
            $order = new shopOrder($order_id);
        } catch (Exception $e) {
            waLog::log('Order not found: ' . $order_id, self::LOG_FILE);

            return false;
        }

        return $order;
    }

    /**
     * @param $action_id
     * @return string|false
     */
    private function getReceiptType($action_id)
    {
        $actions_income = (array)$this->getSettings('actions_income');
        $actions_income_return = (array)$this->getSettings('actions_income_return');

        $receipt_type = false;
        if (in_array($action_id, $actions_income)) {
            $receipt_type = self::RECEIPT_TYPE_INCOME;
        } elseif (in_array($action_id, $actions_income_return)) {
            $receipt_type = self::RECEIPT_TYPE_INCOME_RETURN;
        }
        return $receipt_type;
    }

    /**
     * @param shopOrder $order
     * @return bool
     */
    private function isAllowPayment($order)
    {
        $allow_payments = (array)$this->getSettings('payments');

        $params = $order->params;
        return in_array(ifset($params['payment_id']), $allow_payments);
    }

    /**
     * @param shopOrder $order
     * @param string $receipt_type
     * @return bool
     */
    private function isAllowReceiptType($order, $receipt_type)
    {
        $params = $order->params;
        $last_receipt = ifset($params['cloudkassir.last_receipt'], '');

        $is_allow = false;
        switch ($receipt_type) {
            case self::RECEIPT_TYPE_INCOME:
                $is_allow = empty($last_receipt);
                break;
            case self::RECEIPT_TYPE_INCOME_RETURN:
                $is_allow = $last_receipt === self::RECEIPT_TYPE_INCOME;
                break;
        }

        return $is_allow;
    }

    /**
     * @param shopOrder $order
     * @param $receipt_type
     */
    private function storeReceiptMark($order, $receipt_type)
    {
        $param_model = new shopOrderParamsModel();
        $param_model->set($order->id, [
            'cloudkassir.last_receipt' => $receipt_type
        ], false);
    }

    /**
     * @param shopOrder $order
     * @param $receipt_type
     * @return bool
     */
    private function logHistory($order, $receipt_type)
    {
        $titles = [
            self::RECEIPT_TYPE_INCOME => 'прихода',
            self::RECEIPT_TYPE_INCOME_RETURN => 'возврат прихода',
        ];
        if (!isset($titles[$receipt_type])) {
            return false;
        }

        $text = sprintf('Формирование чека %s', $titles[$receipt_type]);
        $log_model = new shopOrderLogModel();
        $log_model->add([
            'order_id' => $order->id,
            'action_id' => '',
            'contact_id' => null,
            'before_state_id' => $order->state_id,
            'after_state_id' => $order->state_id,
            'text' => $text,
        ]);

        return true;
    }

}
