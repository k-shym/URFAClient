<?php

include __DIR__ . '/init.php';

/********** Админ **********/

$api = URFAClient::init(array(
    'login'    => 'init',
    'password' => 'init',
    'address'  => 'localhost',
    'timeout'  => 10,
    'log'      => TRUE,
));

/*
<function name="rpcf_liburfa_list" id="0x0040">
    <input/>
    <output>
        <integer name="size" />
        <for name="i" from="0" count="size">
            <string name="module" array_index="i" />
            <string name="version" array_index="i" />
            <string name="path" array_index="i" />
        </for>
    </output>
</function>
*/
$result = $api->rpcf_liburfa_list();
/*
Array
(
    [size] => Array
        (
            [0] => Array
                (
                    [module] => liburfa-card
                    [version] => 5.3-002-update5-bsd9
                    [path] => liburfa-card
                )

            [1] => Array
                (
                    [module] => liburfa-dealer
                    [version] => 5.3-002-update5-bsd9
                    [path] => liburfa-dealer
                )

            [2] => ...

        )
)
*/

/*
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
*/
$result = $api->rpcf_add_user_new(array(
    'login'=>'test',
    'password'=>'test',
    'parameters_count' => array(),
    'groups_count' => array(),
));
/*
Array
(
    [user_id] => 13
    [basic_account] => 13
)
*/

print_r(URFAClient::trace_log());
/*
Array
(
    [0] => 2014.07.28 15:30:31 INFO: rpcf_liburfa_list( Array ( ) ) -> Array ( [size] => Array ( [0] => Array ( [module] => liburfa-card [version] => 5.3-002-update5-bsd9 [path] => liburfa-card ) [1] => Array ( [module] => liburfa-dealer [version] => 5.3-002-update5-bsd9 [path] => liburfa-dealer ) [2] => ... ) )
    [1] => 2014.07.28 15:30:32 INFO: rpcf_add_user_new( Array ( [login] => test [password] => test [parameters_count] => Array ( ) [groups_count] => Array ( ) ) ) -> Array ( [user_id] => 13 [basic_account] => 13 )
)
*/

/********** Пользователь **********/

$api = URFAClient::init(array(
    'login'    => 'test',
    'password' => 'test',
    'address'  => 'bill.example.org',
    'admin'    => FALSE,
));

/*
<function name="rpcf_user5_change_password" id="-0x4021">
    <input>
        <string name="old_password" />
        <string name="new_password" />
        <string name="new_password_ret" />
    </input>
    <output>
        <integer name="result" />
    </output>
</function>
*/
$result = $api->rpcf_user5_change_password(array(
    'old_password' => 'test',
    'new_password' => 'test_new',
    'new_password_ret' => 'test_new',
));
/*
Array
(
    [result] => 1
)
*/
