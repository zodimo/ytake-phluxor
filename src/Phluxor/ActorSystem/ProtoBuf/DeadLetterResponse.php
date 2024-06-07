<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: actor.proto

namespace Phluxor\ActorSystem\ProtoBuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>actor.DeadLetterResponse</code>
 */
class DeadLetterResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.actor.PID target = 1;</code>
     */
    protected $target = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Phluxor\ActorSystem\ProtoBuf\PID $target
     * }
     */
    public function __construct($data = NULL) {
        \Phluxor\Metadata\ProtoBuf\Actor::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.actor.PID target = 1;</code>
     * @return \Phluxor\ActorSystem\ProtoBuf\PID|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function hasTarget()
    {
        return isset($this->target);
    }

    public function clearTarget()
    {
        unset($this->target);
    }

    /**
     * Generated from protobuf field <code>.actor.PID target = 1;</code>
     * @param \Phluxor\ActorSystem\ProtoBuf\PID $var
     * @return $this
     */
    public function setTarget($var)
    {
        GPBUtil::checkMessage($var, \Phluxor\ActorSystem\ProtoBuf\PID::class);
        $this->target = $var;

        return $this;
    }

}
