<?php

namespace UrfaClient\Exception;

class UrfaClientException extends \RuntimeException implements \Serializable
{
    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $serialized = [
            $this->code,
            $this->message,
            $this->file,
            $this->line,
        ];

        return $this->doSerialize($serialized, \func_num_args() ? \func_get_arg(0) : null);
    }

    /**
     * @internal
     */
    protected function doSerialize($serialized, $isCalledFromOverridingMethod)
    {
        if (null === $isCalledFromOverridingMethod) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
            $isCalledFromOverridingMethod = isset($trace[2]['function'], $trace[2]['object']) && 'serialize' === $trace[2]['function'] && $this === $trace[2]['object'];
        }

        return $isCalledFromOverridingMethod ? $serialized : serialize($serialized);
    }


    /**
     * @param string $str
     */
    public function unserialize($str)
    {
        list(
            $this->code,
            $this->message,
            $this->file,
            $this->line
            ) = \is_array($str) ? $str : unserialize($str);
    }


    /**
     * Message key to be used by the translation component.
     *
     * @return string
     */
    public function getMessageKey()
    {
        return 'Urfa Client Exception.';
    }

    /**
     * Message data to be used by the translation component.
     *
     * @return array
     */
    public function getMessageData()
    {
        return [];
    }
}
