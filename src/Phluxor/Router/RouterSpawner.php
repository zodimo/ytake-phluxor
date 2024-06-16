<?php

declare(strict_types=1);

namespace Phluxor\Router;

use Closure;
use Phluxor\ActorSystem;
use Phluxor\ActorSystem\Context\SpawnerInterface;
use Phluxor\ActorSystem\Props;
use Phluxor\ActorSystem\SpawnResult;
use Swoole\Coroutine\WaitGroup;

class RouterSpawner
{
    private function __construct()
    {
    }

    /**
     * @param ConfigInterface $config
     * @return Closure(ActorSystem, string, Props, SpawnerInterface): SpawnResult
     */
    public static function spawner(ConfigInterface $config): Closure
    {
        return function (
            ActorSystem $actorSystem,
            string $id,
            ActorSystem\Props $props,
            ActorSystem\Context\SpawnerInterface $parent
        ) use ($config): ActorSystem\SpawnResult {
            return self::spawn($actorSystem, $id, $config, $props, $parent);
        };
    }

    private static function spawn(
        ActorSystem $actorSystem,
        string $id,
        ConfigInterface $config,
        ActorSystem\Props $props,
        ActorSystem\Context\SpawnerInterface $parent
    ): ActorSystem\SpawnResult {
        $process = new Process($actorSystem);
        $result = $actorSystem->getProcessRegistry()->add($process, $id);
        if (!$result->isAdded()) {
            return new ActorSystem\SpawnResult(
                $result->getRef(),
                new ActorSystem\Exception\NameExistsException(
                    sprintf('Actor with id %s already exists', $id)
                )
            );
        }
        $props->configure(fn(ActorSystem\Props $props) => null);
        $process->setState($config->createRouterState());

        if ($config->routerType() == RouterType::GroupRouterType) {
            $wg = new WaitGroup();
            $wg->add(1);
            $spawner = new ActorSystem\Spawner\DefaultSpawner();

            $ref = $spawner(
                $actorSystem,
                sprintf("%s/router", $id),
                ActorSystem\Props::fromProducer(
                    fn(): ActorSystem\Message\ActorInterface => new GroupRouterActor(
                        $props,
                        $config,
                        $process->getState(),
                        $wg
                    )
                ),
                $parent
            );
            $process->setRouter($ref->getRef());
            $wg->wait();
        } else {
            $wg = new WaitGroup();
            $wg->add(1);
            $spawner = new ActorSystem\Spawner\DefaultSpawner();
            $ref = $spawner(
                $actorSystem,
                sprintf("%s/router", $id),
                ActorSystem\Props::fromProducer(
                    fn(): ActorSystem\Message\ActorInterface => new PoolRouterActor(
                        $props,
                        $config,
                        $process->getState(),
                        $wg
                    )
                ),
                $parent
            );
            $process->setRouter($ref->getRef());
            $wg->wait();
        }
        $process->setParent($parent->self());
        return new ActorSystem\SpawnResult($result->getRef(), null);
    }
}
