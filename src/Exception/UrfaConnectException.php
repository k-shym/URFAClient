<?php

namespace UrfaClient\Exception;

class UrfaConnectException extends UrfaClientException
{

    /**
     * Message key to be used by the translation component.
     *
     * @return string
     */
    public function getMessageKey()
    {
        return 'Connection refused.';
    }

}
