<?php

namespace UrfaClient\Exception;

class UrfaAuthException extends UrfaClientException
{

    /**
     * Message key to be used by the translation component.
     *
     * @return string
     */
    public function getMessageKey()
    {
        return 'An authentication exception occurred.';
    }
}
