<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: router.proto

namespace Phluxor\Router\ProtoBuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>router.AdjustPoolSize</code>
 */
class AdjustPoolSize extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>int32 change = 1;</code>
     */
    protected $change = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $change
     * }
     */
    public function __construct($data = NULL) {
        \Phluxor\Router\Metadata\ProtoBuf\Router::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>int32 change = 1;</code>
     * @return int
     */
    public function getChange()
    {
        return $this->change;
    }

    /**
     * Generated from protobuf field <code>int32 change = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setChange($var)
    {
        GPBUtil::checkInt32($var);
        $this->change = $var;

        return $this;
    }

}
