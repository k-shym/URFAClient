<?php

namespace UrfaClient\Exception;

class UrfaExpiredSessionException extends UrfaClientException
{

    /**
     * Message key to be used by the translation component.
     *
     * @return string
     */
    public function getMessageKey()
    {
        return 'Expired Session';
    }
}
