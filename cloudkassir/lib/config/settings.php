<?php
return array (
    'public_id' => array(
        'title' => 'Идентификатор сайта',
        'description' => 'Обязательный идентификатор сайта. Находится в ЛК CloudPayments',
        'control_type' => waHtmlControl::INPUT,
    ),
    'secret_key' => array(
        'title' => 'Секретный ключ',
        'description' => 'Обязательный секретный ключ. Находится в ЛК CloudPayments (Пароль для API)',
        'control_type' => waHtmlControl::PASSWORD,
    ),
    'inn' => array(
        'title' => 'ИНН',
        'description' => 'ИНН вашей организации или ИП, на который зарегистрирована касса',
        'control_type' => waHtmlControl::INPUT,
    ),
    'taxation_system' => array(
        'title' => 'Система налогообложения',
        'control_type' => waHtmlControl::SELECT ,
        'options' => array(
            array(
                'value' => 0,
                'title' => 'Общая система налогообложения',
            ),
            array(
                'value' => 1,
                'title' => 'Упрощенная система налогообложения (Доход)',
            ),
            array(
                'value' => 2,
                'title' => 'Упрощенная система налогообложения (Доход минус Расход)',
            ),
            array(
                'value' => 3,
                'title' => 'Единый налог на вмененный доход',
            ),
            array(
                'value' => 4,
                'title' => 'Единый сельскохозяйственный налог',
            ),
            array(
                'value' => 5,
                'title' => 'Патентная система налогообложения',
            ),
        ),
        'value' => '0'
    ),
    'vat' => array(
        'title' => 'Ставка НДС',
        'description' => 'Ставка НДС для всех товаров в чеке',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value' => '',
                'title' => 'НДС не облагается',
            ),
            array(
                'value' => '0',
                'title' => 'НДС 0%',
            ),
            array(
                'value' => '10',
                'title' => 'НДС 10%',
            ),
            array(
                'value' => '18',
                'title' => 'НДС 18%',
            ),
            array(
                'value' => '110',
                'title' => 'Расчетный НДС 18/118',
            ),
            array(
                'value' => '118',
                'title' => 'Расчетный НДС 18/118',
            ),
        ),
        'value' => ''
    ),
    'vat_delivery' => array(
        'title' => 'Ставка НДС для доставки',
        'description' => 'Отдельная ставка НДС для позииции доставки в чеке',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value' => '',
                'title' => 'НДС не облагается',
            ),
            array(
                'value' => '0',
                'title' => 'НДС 0%',
            ),
            array(
                'value' => '10',
                'title' => 'НДС 10%',
            ),
            array(
                'value' => '18',
                'title' => 'НДС 18%',
            ),
            array(
                'value' => '110',
                'title' => 'Расчетный НДС 18/118',
            ),
            array(
                'value' => '118',
                'title' => 'Расчетный НДС 18/118',
            ),
        ),
        'value' => ''
    ),
    'payments' => array(
        'title' => 'Способы оплаты',
        'description' => 'Отметьте способы оплаты для которых требуется печать чека',
        'control_type' => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopCloudkassirPlugin', 'settingAvailablePayments'),
        'value' => array(),
    ),
    'actions_income' => array(
        'title' => 'Действия заказа для оплаты (приход)',
        'description' => 'Действия с заказом при которых будет отправлен запрос на печать чека прихода',
        'control_type' => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopCloudkassirPlugin', 'settingAvailableOrderActions'),
        'value' => array('pay'),
    ),
    'actions_income_return' => array(
        'title' => 'Действия заказа для возврата (возврат прихода)',
        'description' => 'Действия с заказом при которых будет отправлен запрос на печать чека возврата прихода',
        'control_type' => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopCloudkassirPlugin', 'settingAvailableOrderActions'),
        'value' => array('refund'),
    ),
);
