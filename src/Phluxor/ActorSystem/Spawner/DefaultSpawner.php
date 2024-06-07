<?php

declare(strict_types=1);

namespace Phluxor\ActorSystem\Spawner;

use Phluxor\ActorSystem;
use Phluxor\ActorSystem\Props;
use Phluxor\ActorSystem\SpawnResult;
use Phluxor\ActorSystem\Context\SpawnerInterface;

class DefaultSpawner implements ActorSystem\SpawnFunctionInterface
{
    /**
     * @param ActorSystem $actorSystem
     * @param string $id
     * @param Props $props
     * @param SpawnerInterface $parentContext
     * @return SpawnResult
     */
    public function __invoke(
        ActorSystem $actorSystem,
        string $id,
        Props $props,
        SpawnerInterface $parentContext
    ): SpawnResult {
        $context = new ActorSystem\ActorContext(
            actorSystem: $actorSystem,
            props: $props,
            parent: $parentContext->self()
        );
        $mailbox = $props->produceMailbox();
        $dispatcher = $props->getDispatcher();
        $process = new ActorSystem\ActorProcess($mailbox);
        $addResult = $actorSystem->getProcessRegistry()->add($process, $id);
        if (!$addResult->isAdded()) {
            return new SpawnResult(
                $addResult->getPid(),
                new ActorSystem\Exception\SpawnErrorException(
                    'Actor with id ' . $id . ' already exists'
                )
            );
        }
        $context->setSelf($addResult->getPid());
        $props->initialize($context);
        $mailbox->registerHandlers($context, $dispatcher);
        $mailbox->postSystemMessage(new ActorSystem\Message\Started());
        $mailbox->start();
        return new SpawnResult(
            $addResult->getPid(),
            null
        );
    }
}