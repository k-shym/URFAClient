URFAClient 2.0.0
==========

Универсальный PHP клиент для биллинговой системы NetUp UTM5 на основе api.xml

## Установка
`composer require zorn-v/urfa-client`

## Зависимости
- UTM 5.3-005 >=
- PHP 7.1 >=
- Ext: OpenSSL, SimpleXML, Bcmath, Hash, Filter

## Описание параметров
option  | default | описание
------------- | ------------- | -------------
login* | — | логин администратора или пользователя
password* | — | пароль администратора или пользователя соответственно
address* | — | адрес ядра UTM5
port | 11758 | порт ядра UTM5
timeout | 30 | время ожидания ответа от сервера
protocol | ssl | **ssl** или **tls** (доступно с версии UTM-5.3-002-update16) или **auto** (доступно с версии UTM-5.3-005-update2, работает с OpenSSL 1.1)
admin | TRUE | указываем какой пользователь подключается, если TRUE предоставляет сертификат admin.crt для соединения, используется только для протокола **ssl**
api | api_53-005.xml | путь до файла api.xml
log | FALSE | сборщик логов, если TRUE, перехватывает исключения из URFAClient_API

## Пример
Рассмотрим пример использования библиотеки на примере функции rpcf_add_user_new, у нас есть XML описание:
```xml
<function name="rpcf_add_user_new" id="0x2125">
    <input>
        <string name="login"/>
        <string name="password"/>
        <string name="full_name" default=""/>
        <integer name="is_juridical" default="0"/>
        <string name="jur_address" default=""/>
        <string name="act_address" default=""/>
        <string name="flat_number" default=""/>
        <string name="entrance" default=""/>
        <string name="floor" default=""/>
        <string name="district" default=""/>
        <string name="building" default=""/>
        <string name="passport" default=""/>
        <integer name="house_id" default="0"/>
        <string name="work_tel" default=""/>
        <string name="home_tel" default=""/>
        <string name="mob_tel" default=""/>
        <string name="web_page" default=""/>
        <string name="icq_number" default=""/>
        <string name="tax_number" default=""/>
        <string name="kpp_number" default=""/>
        <string name="email" default=""/>
        <integer name="bank_id" default="0"/>
        <string name="bank_account" default=""/>
        <string name="comments" default=""/>
        <string name="personal_manager" default=""/>
        <integer name="connect_date" default="0"/>
        <integer name="is_send_invoice" default="0"/>
        <integer name="advance_payment" default="0"/>

        <integer name="switch_id" default="0"/>
        <integer name="port_number" default="0"/>
        <integer name="binded_currency_id" default="810"/>

        <integer name="parameters_count" default="size(parameter_value)"/>
        <for name="i" from="0" count="size(parameter_value)">
            <integer name="parameter_id" array_index="i"/>
            <string name="parameter_value" array_index="i"/>
        </for>

        <integer name="groups_count" default="size(groups)"/>
        <for name="i" from="0" count="size(groups)">
            <integer name="groups" array_index="i"/>
        </for>

        <integer name="is_blocked" default="0"/>
        <double name="balance" default="0.0"/>
        <double name="credit" default="0.0"/>
        <double name="vat_rate" default="0.0"/>
        <double name="sale_tax_rate" default="0.0"/>
        <integer name="int_status" default="1"/>
    </input>
    <output>
        <integer name="user_id"/>
        <if variable="user_id" value="0" condition="eq">
            <integer name="error_code"/>
            <string name="error_description"/>
        </if>
        <if variable="user_id" value="0" condition="ne">
            <integer name="basic_account"/>
        </if>
    </output>
</function>
```
И так, нам нужно описать входные параметры (элемент input) в ассоциативный массив.
Если в элементе присутствует атрибут `default`, параметр считается необязательным.

Получаем полное описание параметров функции `rpcf_add_user_new` из api.xml:
```bash
php cmd.php -f rpcf_add_user_new
```
```php
array (
  'login' => '',
  'password' => '',
  'full_name' => '',
  'is_juridical' => 0,
  'jur_address' => '',
  'act_address' => '',
  'flat_number' => '',
  'entrance' => '',
  'floor' => '',
  'district' => '',
  'building' => '',
  'passport' => '',
  'house_id' => 0,
  'work_tel' => '',
  'home_tel' => '',
  'mob_tel' => '',
  'web_page' => '',
  'icq_number' => '',
  'tax_number' => '',
  'kpp_number' => '',
  'email' => '',
  'bank_id' => 0,
  'bank_account' => '',
  'comments' => '',
  'personal_manager' => '',
  'connect_date' => 0,
  'is_send_invoice' => 0,
  'advance_payment' => 0,
  'switch_id' => 0,
  'port_number' => 0,
  'binded_currency_id' => 0,
  'parameters_count' =>
  array (
    0 =>
    array (
      'parameter_id' => 0,
      'parameter_value' => '',
    ),
  ),
  'groups_count' =>
  array (
    0 =>
    array (
      'groups' => 0,
    ),
  ),
  'is_blocked' => 0,
  'balance' => 0,
  'credit' => 0,
  'vat_rate' => 0,
  'sale_tax_rate' => 0,
  'int_status' => 0,
)
```
На основе данного описания оставляем необходимые нам параметры, порядок параметров неважен.

Как было замечено, разработчики UTM5 не пришли к единому формату описания функций. Отсюда возник вопрос, какое имя давать параметру `for` для элементов массива?
Поэтому было принято решение, в качестве имени использовать имя атрибута счетчика `*_count`. В нашем случае будет так:
```php
array(
    // ...
    'parameters_count' => array(
        array(
            'parameter_id' => 0,
            'parameter_value' => 'м',
        ),
        array(
            'parameter_id' => 1,
            'parameter_value' => '13.06.2014',
        ),
    ),
    'groups_count' => array(
        array(
            'groups' => 1000,
        ),
        array(
            'groups' => 1001,
        ),
    ),
    // ...
)
```
Если попадется элемент `error` будет выброшено исключение _XML Described error:_, а далее атрибуты ошибки.

C условиями `if` все просто, если истина, то заходим внутрь. И содержание обрабатывается, как описано выше.

В итоге, получаем минимальный набор параметров для создания пользователя:
```php
include 'URFAClient/init.php';

$urfa = URFAClient::init(array(
    'login'    => 'init',
    'password' => 'init',
    'address'  => 'localhost',
));

$result = $urfa->rpcf_add_user_new(array(
    'login'=>'test',
    'password'=>'test',
));
```
В переменную `$result` попадут данные которые описаны в элементе output.

## Возможные проблемы
- Тестировалось на версии биллинга UTM-5.3-003-update15 и UTM-5.3-005-update2
- Тестировались не все функции из api.xml
- Не реализована передача типа long для PHP x32
- При обновлении api.xml обязательно проверяйте используемые функции

По возникшим проблемам присылайте лог(URFAClient::trace_log()), api.xml и полную версию ядра UTM5. Удачи!

## История изменений
**v2.0.0**
- Рефакторинг
- Использование стандарта PSR-4 для автозагрузки (PSR-4: Autoloader)
- Логирование (PSR-3: Logger Interface)
- Поддерживаемая версия php >= 7.1
- Поддерживаемая версия биллинга >= 5.3-005


**v1.3.1**
- Добавлен автоматический выбор протокола SSL соединения, работает с OpenSSL 1.1 и ядром UTM-5.3-005-update2

**v1.3.0**
- Доработан анализ узла `output`
- Добавлена обработка тэга `set`
- Просмотр списка функций и описание XML через cmd.php в файле api.xml
- Добавлены и обновлены api.xml

**v1.1.0**
- Добавлен консольный помощник cmd.php (описание функций api.xml в php array)
- Добавлены XML с описанием API из более ранних версий и отдельные тесты к ним
- Исправлена проблема при одновременном использовании от двух и более экземпляров библиотеки (с поддержкой и без поддержки IPv6)
- Добавлена поддержка протокола TLSv1 (доступно с версии UTM-5.3-002-update16)

**v1.0.11**
- Исправлена некорректная работа параметра timeout

**v1.0.10**
- Если данные от ядра биллинговой системы не получены, функции возвращают NULL
- Обновлен api.xml до версии ядра 5.3-002-update18

**v1.0.9**
- Входные параметры тега for стали необязательными
- Исправлено приведение типа при NULL значениях

**v1.0.8**
- Исправлена ошибка при использовании PHP 5.6 (SSLv3)

**v1.0.7**
- Исправлена проблема с проверкой сертификата при использовании PHP 5.6
- Обновлен api.xml до версии ядра 5.3-002-update12

**v1.0.6**
- Добавлен параметр timeout
- Добавлено время выполнения функций в логе

**v1.0.5**
- Преданные аргументы функциям приводятся к нужному типу данных (например для типа integer теперь можно передать строку _array('user_id' => '13')_ )
- Доработана поддержка получения типа long для PHP x32 x64
- Доработана поддержка отправки типа long для PHP x64

**v1.0.4**
- Исправлена ошибка получения отрицательного типа int при использовании PHP x64 и UTM5 x32

**v1.0.3**
- Обновлен api.xml до версии ядра 5.3-002-update9
- Исправлена ошибка для типа данных double
- Доработана поддержка типа данных long в элементе input

**v1.0.2**
- Поправлена поддержка IPv6
- Обновлен api.xml до версии ядра 5.3-002-update8

**v1.0.1**
- Поправлена обработка элемента output
