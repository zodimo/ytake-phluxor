<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: router.proto

namespace Phluxor\Router\Metadata\ProtoBuf;

class Router
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \Phluxor\Metadata\ProtoBuf\Actor::initOnce();
        $pool->internalAddGeneratedFile(
            "\x0A\x81\x02\x0A\x0Crouter.proto\x12\x06router\"\$\x0A\x09AddRoutee\x12\x17\x0A\x03PID\x18\x01 \x01(\x0B2\x0A.actor.PID\"'\x0A\x0CRemoveRoutee\x12\x17\x0A\x03PID\x18\x01 \x01(\x0B2\x0A.actor.PID\" \x0A\x0EAdjustPoolSize\x12\x0E\x0A\x06change\x18\x01 \x01(\x05\"\x0C\x0A\x0AGetRoutees\"#\x0A\x07Routees\x12\x18\x0A\x04PIDs\x18\x01 \x03(\x0B2\x0A.actor.PIDB=\xCA\x02\x17Phluxor\\Router\\ProtoBuf\xE2\x02 Phluxor\\Router\\Metadata\\ProtoBufb\x06proto3"
        , true);

        static::$is_initialized = true;
    }
}

