[![PHP Version Require](https://badgen.net/packagist/php/k-shym/urfa-client/dev-main?color=purple)](https://packagist.org/packages/k-shym/urfa-client) [![Latest Version](https://badgen.net/packagist/v/k-shym/urfa-client/latest)](https://packagist.org/packages/k-shym/urfa-client) [![Total Downloads](https://badgen.net/packagist/dt/k-shym/urfa-client)](https://packagist.org/packages/k-shym/urfa-client)

URFAClient
==========

Универсальный PHP клиент для биллинговой системы NetUp UTM5 на основе api.xml

## Установка (composer)
```bash
composer require k-shym/urfa-client "^2.0"
```

## Зависимости
- UTM 5.2.1-008 >=
- PHP 5.4 >=
- Ext: JSON, OpenSSL, SimpleXML, Bcmath, Hash, Filter

## Описание параметров
| option    | default            | описание                                                                                                                                          |
|-----------|--------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| login*    | —                  | логин администратора или пользователя                                                                                                             |
| password* | —                  | пароль администратора или пользователя соответственно                                                                                             |
| address*  | —                  | адрес ядра UTM5                                                                                                                                   |
| port      | **11758**          | порт ядра UTM5                                                                                                                                    |
| timeout   | **30**             | время ожидания ответа от сервера                                                                                                                  |
| protocol  | **auto**           | **ssl** или **tls** (доступно с версии UTM-5.3-002-update16) или **auto** (доступно с версии UTM-5.3-005-update2, работает с OpenSSL 1.1)         |
| admin     | **true**           | указываем какой пользователь подключается, если TRUE предоставляет сертификат admin.crt для соединения, используется только для протокола **ssl** |
| api       | **api_53-006.xml** | путь до файла api.xml                                                                                                                             |

## CMD
```
bin/urfaclient -h

The options are as follows:
   [-a, --api <path> ]             Path to api.xml
   [-f, --function <name>]         Name function from api.xml
   [-t, --type <type>]             Type return (array, json, xml), default: array
   [-l, --list]                    List of functions from api.xml
   [-h, --help ]                   This help
   [-v, --version ]                Version URFAClient

```

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
bin/urfaclient -f rpcf_add_user_new -t json
```
```json
{
  "login": "",
  "password": "",
  "full_name": "",
  "is_juridical": 0,
  "jur_address": "",
  "act_address": "",
  "flat_number": "",
  "entrance": "",
  "floor": "",
  "district": "",
  "building": "",
  "passport": "",
  "house_id": 0,
  "work_tel": "",
  "home_tel": "",
  "mob_tel": "",
  "web_page": "",
  "icq_number": "",
  "tax_number": "",
  "kpp_number": "",
  "email": "",
  "bank_id": 0,
  "bank_account": "",
  "comments": "",
  "personal_manager": "",
  "connect_date": 0,
  "is_send_invoice": 0,
  "advance_payment": 0,
  "switch_id": 0,
  "port_number": 0,
  "binded_currency_id": 0,
  "parameters_count": [
    {
      "parameter_id": 0,
      "parameter_value": ""
    }
  ],
  "groups_count": [
    {
      "groups": 0
    }
  ],
  "is_blocked": 0,
  "balance": 0,
  "credit": 0,
  "vat_rate": 0,
  "sale_tax_rate": 0,
  "int_status": 0
}
```
На основе данного описания оставляем необходимые нам параметры, порядок параметров неважен.

Как было замечено, разработчики UTM5 не пришли к единому формату описания функций. Отсюда возник вопрос, какое имя давать параметру `for` для элементов массива?
Поэтому было принято решение, в качестве имени использовать имя атрибута счетчика `*_count`. В нашем случае будет так:
```php
[
    // ...
    'parameters_count' => [
        [
            'parameter_id' => 0,
            'parameter_value' => 'м',
        ],
        [
            'parameter_id' => 1,
            'parameter_value' => '13.06.2014',
        ],
    ],
    'groups_count' => [
        [
            'groups' => 1000,
        ],
        [
            'groups' => 1001,
        ],
    ],
    // ...
];
```
Если попадется элемент `error` будет выброшено исключение _XML Described error:_, а далее атрибуты ошибки.

C условиями `if` все просто, если истина, то заходим внутрь. И содержание обрабатывается, как описано выше.

В итоге, получаем минимальный набор параметров для создания пользователя:
```php
require __DIR__ . '/vendor/autoload.php';
use URFAClient\URFAClient;

$urfa = URFAClient::init([
    'login'    => 'init',
    'password' => 'init',
    'address'  => 'localhost',
]);

$result = $urfa->rpcf_add_user_new([
    'login'=>'test',
    'password'=>'test',
]);

$result = $urfa->rpcf_add_user_new('{
  "login": "test2",
  "password": "test2"
}');
```
В переменную `$result` попадут данные которые описаны в элементе `output`.

## Тесты
```
docker-compose up -d
docker exec -t urfa composer install
docker exec -t urfa vendor/bin/phpunit --coverage-text
```

## Возможные проблемы
- Тестировалось на версии биллинга UTM-5.3-003, UTM-5.4-004 и UTM-5.5-015
- Тестировались не все функции из api.xml
- Не реализована передача типа long для PHP x32
- При обновлении api.xml обязательно проверяйте используемые функции

По возникшим проблемам присылайте api.xml и полную версию ядра UTM5. Удачи!
